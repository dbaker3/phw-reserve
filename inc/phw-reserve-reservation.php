<?php
/**
* Contains PHWReserveReservationRequest class
* 
* @author David Baker
* @copyright 2015 Milligan College
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU Public License v2
* @since 1.0
*/


/**
* Reservation Requests
*
* Represents the reservation request. It verifies date/time, handles
* authorization of requestor, sends appropriate emails to requestor, and communicates
* with the WP database table, pwh_reservations.
*
* @since 1.0
*/
class PHWReserveReservationRequest {
   private $res_id;
   private $patron_name;
   private $patron_email;
   private $datetime_start;
   private $datetime_end;
   private $room;
   private $purpose;
   private $auth_code;
   private $recurs;
   private $recurs_until;
   private $recurs_on;
   private $wpdb;
  
   // Plugin settings
   private $temp_res_len;
   private $keep_old_len;
   private $email_cust;
   private $reply_to;
   
   /**
   * Sets up reservation properties
   *
   * @param string $name
   * @param string $email
   * @param string $start Unix timestamp of start date/time
   * @param string $end Unix timestamp of start date/time
   * @param string $room
   * @param string $purpose Reason requestor is reserving room
   * @param string $auth_code should be empty string
   * @param boolean $recurs
   * @param string $recurs_until
   * @param mixed array $recurs_on
   *
   * @since 1.0
   *
   * @return void
   */
   public function __construct($name = '', $email = '', $start = '', $end = '', 
                               $room = '', $purpose = '', $auth_code = '', 
                               $recurs = false, $recurs_until = '', $recurs_on = '') {
      // Prevent null values in DB								   
      if (is_null($recurs)) $recurs = false;
      if (is_null($recurs_until)) $recurs_until = '';
	  
      $this->res_id = 0;
      $this->patron_name = $name;
      $this->patron_email = $email;
      $this->datetime_start = $start;
      $this->datetime_end = $end;
      $this->room = $room;
      $this->purpose = $purpose;
      $this->auth_code = $auth_code;
      $this->recurs = $recurs;
      $this->recurs_until = $recurs_until;
      $this->recurs_on = json_encode($recurs_on);
  
      global $wpdb;
      $this->wpdb =& $wpdb;
      $this->wpdb->phw_reservations = "{$this->wpdb->prefix}phw_reservations";
      $this->wpdb->phw_reservations_recur = "{$this->wpdb->prefix}phw_reservations_recur";

      $this->load_plugin_settings();
   }

   
   /**
   * Sets reservation properties
   *
   * Use this if you didn't set properties with constructor. As of 1.0 only used
   * by PHWReservePageController::handle_edit_res_submission(). Might be able to 
   * get rid of this method if that one is restructured.
   *
   * @param int $res_id
   * @param string $name
   * @param string $email
   * @param int $start
   * @param int $end
   * @param string $room
   * @param string $purpose
   * @param string $auth
   * @since 1.0
   *
   */
   public function set_properties($res_id, $name, $email, $start, $end, $room, $purpose, 
                                  $auth, $recurs, $recurs_until, $recurs_on) {
      $this->res_id = $res_id;
      $this->patron_name = $name;
      $this->patron_email = $email;
      $this->datetime_start = $start;
      $this->datetime_end = $end;
      $this->room = $room;
      $this->purpose = $purpose;
      $this->auth_code = $auth;
      $this->recurs = $recurs;
      $this->recurs_until = $recurs_until;
      $this->recurs_on = json_encode($recurs_on);
   }
  

   /**
   * Loads plugin settings
   * @since 1.0
   */
   private function load_plugin_settings() {
      $option_name = PHWReserveSettings::get_option_name();
      $settings = get_option($option_name);
      $this->temp_res_len = $settings['temp_res_len'];
      $this->keep_old_len = $settings['keep_old_len'];
      $this->email_cust = $settings['email_cust'];
      $this->reply_to = $settings['reply_to'];
   }


   /**
   * Returns true if requested time conflicts with an existing reservation
   *
   * Checks the database table phw_reservations for any confirmed reservations
   * that conflict with the requested date/time and room, and returns true if 
   * found. If false, checks for conflicts with as-of-yet confirmed requests 
   * that exist only as transients awaiting confirmation. Returns true if found.
   *
   * Will ignore the reservation if $res_id is set. This allows adjusting
   * the time of an existing reservation (via an edit) without it reporting a 
   * conflict.
   *
   * Also deletes expired transients prior to checking for conflicting transients
   * 
   * @since 1.0
   * @return boolean
   *
   * @todo check time conflict with Today's Hours widget if available
   */
   public function check_time_conflict($start = null, $end = null) {
      if ($start === null || $end === null) {
         $start = $this->datetime_start;
         $end = $this->datetime_end;
      }

      // against confirmed reservations
      $query = "SELECT res_id FROM {$this->wpdb->phw_reservations}
                WHERE %d < datetime_end
                AND %d > datetime_start
                AND %s = room
                AND %d <> res_id;";
      $query = $this->wpdb->prepare($query, $start, $end, $this->room, $this->res_id);
      $conflicting = $this->wpdb->query($query);
      
      // against unconfirmed reservations (transients)
      if (!$conflicting) {
         $this->delete_expired_transients();
         $query = "SELECT option_name
                   FROM {$this->wpdb->prefix}options
                   WHERE option_name LIKE '%transient_phwreserve%'";
         $results = $this->wpdb->get_results($query, ARRAY_A);
         foreach ($results as $result) {
            $option_name = $result['option_name'];
            $transient_name = str_replace('_transient_', '', $option_name);
            $transient_data = get_transient($transient_name);
            if ($start < $transient_data['datetime_end'] &&
                     $end > $transient_data['datetime_start'] &&
                     $this->room == $transient_data['room']) {
               $conflicting = true;
               break;
            }
         }
      }
      // against recurring reservations
      if (!$conflicting) {
         $query = "SELECT recur_id 
                   FROM (SELECT {$this->wpdb->phw_reservations_recur}.recur_id,
                                {$this->wpdb->phw_reservations_recur}.r_datetime_start,
                                {$this->wpdb->phw_reservations_recur}.r_datetime_end,
                                {$this->wpdb->phw_reservations}.room
                         FROM {$this->wpdb->phw_reservations},
                              {$this->wpdb->phw_reservations_recur}
                         WHERE {$this->wpdb->phw_reservations}.res_id =
                               {$this->wpdb->phw_reservations_recur}.res_id
                        ) AS recurring_reservations_set
                   WHERE %s = room
                   AND %d < r_datetime_end
                   AND %d > r_datetime_start";
         $query = $this->wpdb->prepare($query, $this->room, $start, $end);
         $conflicting = $this->wpdb->query($query);
      }

      return $conflicting;
   }


   /**
   * Checks each recur instance for time conflict
   *
   *
   */
   public function check_recur_time_conflict() {
      $recurring_dates = $this->get_recurring_dates(date('m/d/Y', $this->datetime_start),
                                             date('m/d/Y', $this->recurs_until),
                                             json_decode($this->recurs_on));
      $conflicting_datetimes = array();
      foreach ($recurring_dates as $recdate) {
         $r_datetime_start = strtotime(date("Ymd", $recdate) . 't' . date("His", $this->datetime_start));
         $r_datetime_end =  strtotime(date("Ymd", $recdate) . 't' . date("His", $this->datetime_end));

         if ($this->check_time_conflict($r_datetime_start, $r_datetime_end)) {
            array_push($conflicting_datetimes, date('n/d/Y', $r_datetime_start));
         };
      }
      return $conflicting_datetimes;
   }




   /**
   * Saves reservation request and prepares authorization code for emailing
   *
   * Generates an authorization code. Current reservation request is saved, along
   * with auth code, in a transient. 
   *
   * Calls the send_auth_code_email() method.
   * @since 1.0
   *
   * @return void
   */
   public function authenticate_user() {
      $this->create_auth_code();
      
      $transient_name = 'phwreserve_' . time();
      $transient_data = array('patron_name'    => $this->patron_name,
                              'patron_email'   => $this->patron_email, 
                              'datetime_start' => $this->datetime_start, 
                              'datetime_end'   => $this->datetime_end, 
                              'room'           => $this->room, 
                              'purpose'        => $this->purpose, 
                              'auth_code'      => $this->auth_code);
      set_transient($transient_name, $transient_data, ($this->temp_res_len * HOUR_IN_SECONDS));
      
      $this->send_auth_code_email($transient_name);
   }

   
   /**
   * Creates authorization code for a reservation
   *
   * Creates code and saves it to the auth_code property of the current 
   * PHWReserveReservationRequest object. Codes are the last 14 digits of an
   * MD5 hash of a pseudo-random number.
   *
   * @since 1.0
   */
   public function create_auth_code() {
      $this->auth_code = substr(md5(mt_rand()), -14);  
   }
   
   
   /**
   * Generates and sends email contaiing authorization URL to requestor.
   *
   * The URL contains the name of the transient containing both the reservation
   * data and the previously generated authorization code. The authorization code
   * is also embedded into the URL.
   *
   * The auth code is used by the page controller class to verify that the email
   * account entered into the form is accessible by the requestor.
   *
   * @since 1.0
   *
   * @uses wp_mail
   * @param string $transient_name 
   * @return void
   *
   */
   private function send_auth_code_email($transient_name) {
      $conf_url = get_permalink() . '?method=handle_auth_code_submission&amp;transient=' . $transient_name . '&amp;auth_code=' . $this->auth_code;
   	$emailTo = $this->patron_email;
		$subject =  'Please confirm your room reservation request';
		$body = '<h3>Please click the following link to confirm your room reservation request made ' . date("F j, Y, g:i a") .'</h3>';
      $body .= "<p><a href='{$conf_url}'>{$conf_url}</a></p>";
		$body .= '<p><strong>Requested by: </strong>' . $this->patron_name . ' [' . $this->patron_email . ']<br />';
		$body .= '<strong>Date: </strong>' . date('D, M j, Y', $this->datetime_start) . ' from ' . date('g:i A', $this->datetime_start) . ' - ' . date('g:i A', $this->datetime_end) . '<br />';
		$body .= '<strong>For: </strong>' . $this->purpose . '<br />';
		$body .= '<strong>Room: </strong>' . $this->room . '</p>';

		$headers[] = 'From: Room Reservation Webform <' . $this->reply_to . '>';
		$headers[] = 'Reply-To: ' . $this->reply_to;
		$headers[] = 'content-type: text/html';

		if (wp_mail( $emailTo, $subject, $body, $headers )) {
         echo "<h2>Please Confirm</h2>";
         echo "<p>An email has been sent to {$this->patron_email}. <strong>You must visit the link contained in the email to confirm your reservation</strong>.</p>";
         echo "<p><strong>Note:</strong> If you do not click the link your request will expire and the reservation will be canceled.</p>";
      }
   }

   
   /**
   * Insert reservation into phw_reservations table and email confirmation
   *
   * Saves the current reservation request into the table and calls the
   * send_confirmed_email() method 
   *
   * @since 1.0
   */
   public function insert_into_db() {
      $query_get_res_id = "SELECT res_id FROM {$this->wpdb->phw_reservations} 
                           WHERE datetime_start = %d 
                           AND room = %s";
      $query_get_res_id = $this->wpdb->prepare($query_get_res_id, $this->datetime_start, $this->room);
      if ($this->wpdb->get_results($query_get_res_id)) {
         echo "This reservation has been confirmed.";
      }
      else {
         $success = $this->wpdb->insert($this->wpdb->phw_reservations, 
                             array(
                                'patron_name'    => $this->patron_name,
                                'patron_email'   => $this->patron_email,
                                'datetime_start' => $this->datetime_start,
                                'datetime_end'   => $this->datetime_end,
                                'purpose'        => $this->purpose,
                                'room'           => $this->room,
                                'auth_code'      => $this->auth_code,
                                'recurs'         => $this->recurs,
                                'recurs_until'   => $this->recurs_until,
                                'recurs_on'      => $this->recurs_on
                             ));
         if (!$success) {
            echo "There was an error inserting data into the database. Please contact " . antispambot(get_option('admin_email')) . " with this error.";
            echo "<pre>";
            var_dump($this->patron_name);
            var_dump($this->patron_email);	
            var_dump($this->datetime_start);
            var_dump($this->datetime_end);	
            var_dump($this->purpose);
            var_dump($this->room);	
            var_dump($this->auth_code);
            var_dump($this->recurs);	
            var_dump($this->recurs_until);
            var_dump($this->recurs_on);
            echo "</pre>";            
            wp_die();
         }
        
         $res_id = $this->wpdb->get_results($query_get_res_id);
         $res_id = $res_id[0]->res_id;
        
         if ($this->recurs) {
            $this->insert_recurring_into_db($res_id); 
         }
        
         $this->send_confirmed_email($res_id);
      }
   }

   
   /**
   * Inserts recurring reservations into table
   * @since 1.0
   *
   */
   private function insert_recurring_into_db($res_id) {
      $recurring_dates = $this->get_recurring_dates(date('m/d/Y', $this->datetime_start),
                                             date('m/d/Y', $this->recurs_until),
                                             json_decode($this->recurs_on));

      foreach ($recurring_dates as $recdate) {
         $r_datetime_start = strtotime(date("Ymd", $recdate) . 't' . date("His", $this->datetime_start));
         $r_datetime_end =  strtotime(date("Ymd", $recdate) . 't' . date("His", $this->datetime_end));

         $success = $this->wpdb->insert($this->wpdb->phw_reservations_recur,
                                        array(
                                          'res_id'           => $res_id,
                                          'r_datetime_start' => $r_datetime_start,
                                          'r_datetime_end'   => $r_datetime_end
                                        ));
         if (!success) {
            echo "There was an error inserting data into the database. Please contact " . antispambot(get_option('admin_email')) . " with this error.";
            wp_die();
         }
      }
   }


   /**
   * Returns an array of dates that a recurring reservation occurs on
   *
   * @param $start_date string "mm/dd/yyyy" or "m/d/yy"
   * @param $end_date string see $start_date
   * @param $recurs_on mixed array of days of week reservation recurs on
   * @returns $recurring_dates mixed array of the dates the reservation recurs on
   * @since 1.0
   */
   private function get_recurring_dates($start_date, $end_date, $recurs_on) {
      $recurring_dates = array();
   
      $start = new DateTime($start_date);
      $end = new DateTime($end_date);
      $end->modify('+1 day');
      $one_day = new DateInterval('P1D');
      $period = new DatePeriod($start, $one_day, $end, DatePeriod::EXCLUDE_START_DATE);

      foreach ($period as $day) {
         //$the_date = date('m/d/y', $day->getTimestamp());
         $the_date = $day->getTimestamp();
         $day_of_week = strtolower(date('D', $day->getTimestamp())); 
   
         if (array_key_exists($day_of_week, $recurs_on)) 
            array_push($recurring_dates, $the_date);
      }
      return $recurring_dates;
   }

   /**
   * Sends confirmation email to requestor
   *
   * After reservation has been inserted into the table, this method sends a 
   * confirmation email to the requestor. The email contains a URL for the 
   * requestor to visit in order to change and/or delete the reservation
   *
   * @param int $res_id The reservation id matching the res_id column in table
   * @return void
   *
   * @todo option for message text
   */
   private function send_confirmed_email($res_id) {
      $conf_url = get_permalink() . '?method=handle_edit_res_request&amp;res_id=' . $res_id . '&amp;auth_code=' . $this->auth_code;
    	$emailTo = $this->patron_email;
		$subject = 'Room Reservation Confirmation';
      $body = "<h3>Reservation Confirmed!</h3>";
      $body .= "<p>{$this->email_cust}</p>";
      $body .= "<h3>Reservation Details:</h3>";
  		$body .= '<p><strong>Requested by: </strong>' . $this->patron_name . ' [' . $this->patron_email . ']<br />';
		$body .= '<strong>Date: </strong>' . date('D, M j, Y', $this->datetime_start) . ' from ' . date('g:i A', $this->datetime_start) . ' - ' . date('g:i A', $this->datetime_end) . '<br />';

      if ($this->recurs) {
         $days = array();
         foreach (json_decode($this->recurs_on) as $day => $value) {
            array_push($days, ucfirst($day)); 
         }
         $body .= '<strong>Recurs every: </strong>' . implode(", ", $days) . '<br />';
         $body .= '<strong>Recurs until: </strong>' . date('m/d/Y', $this->recurs_until) . '<br />';
      }

		$body .= '<strong>For: </strong>' . $this->purpose . '<br />';
		$body .= '<strong>Room: </strong>' . $this->room . '</p>';
      $body .= "<p>If you need to change or cancel your reservation, please visit this link:";
      $body .= "<br /><a href='{$conf_url}'>{$conf_url}</a></p>";

		$headers[] = 'From: Room Reservation Webform <' . $this->reply_to . '>';
		$headers[] = 'Reply-To: ' . $this->reply_to;
		$headers[] = 'content-type: text/html';
      
      if (wp_mail( $emailTo, $subject, $body, $headers )) {
         echo "<p>Your reservation has been confirmed!</p>";
         echo "<p>A confirmation email has been sent to {$this->patron_email}. The email contains a link to make changes or cancel your reservation should you need to do so.</p>";
      }
   }

   
   /**
   * Finds and deletes expired transients created by this plugin
   *
   * @since 1.0
   *
   * @return void
   */
   private function delete_expired_transients() {
      $cur_time = time();
      $querySelectTimeouts = "SELECT option_name, option_value
                              FROM {$this->wpdb->prefix}options
                              WHERE option_name LIKE '%transient_timeout_phwreserve%'
                              AND option_value < {$cur_time};";
      $expiredTransients = $this->wpdb->get_results($querySelectTimeouts, ARRAY_A);
      if ($expiredTransients) {
         foreach ($expiredTransients as $transient) {
            $option_name = str_replace('_timeout', '', $transient['option_name']);
            delete_transient($option_name);
         } 
      }
   }
   
   
   /**
   * Returns reservation data from table as array
   *
   * @param int $res_id Reservation ID 
   * @return mixed $res_data All of a reservation's data from the table
   * @since 1.0
   *
   */
   public function get_res_data($res_id) {
      $query = "SELECT * FROM {$this->wpdb->phw_reservations} WHERE res_id = %d";
      $query = $this->wpdb->prepare($query, $res_id);
      $res_data = $this->wpdb->get_row($query, ARRAY_A);
      if ($res_data)
         return $res_data;
      else
         $this->no_id_match_error();
   }
   
   
   /**
   * Returns authorization code of a reservation
   *
   * @param int $res_id Reservation ID 
   * @return string $auth_code A reservation's authorization code
   * @since 1.0
   *
   */
   public function get_res_auth_code($res_id) {
      $query = "SELECT auth_code FROM {$this->wpdb->phw_reservations} WHERE res_id = %d";
      $query = $this->wpdb->prepare($query, $res_id);
      $auth_code = $this->wpdb->get_row($query, ARRAY_A);
      if ($auth_code['auth_code']) 
         return $auth_code['auth_code'];
      else
         $this->no_id_match_error();
   }


   /**
   * Deletes a reservation from the table
   *
   * Removes reservation from main table as well as any existing
   * recurring instances in recur table
   *
   * @param int $res_id Reservation ID 
   * @return void
   * @since 1.0
   *
   */  
   public function del_res($res_id) {
      if ($this->recurs) {
         $this->del_all_recur($res_id);
      }

      if ($this->wpdb->delete($this->wpdb->phw_reservations, array('res_id' => $res_id)))
         echo "Reservation has been cancelled.";
      else
         $this->no_id_match_error();
   }


   /**
   * Deletes all occurences of recurring from recur table
   *
   * @param int $res_id Reservation ID
   * @since 1.0
   */
   private function del_all_recur($res_id) {
      if ($this->wpdb->delete($this->wpdb->phw_reservations_recur, array('res_id' => $res_id)))
         echo "OK. ";
      else
         $this->no_id_match_error();
   }


   /**
   * Deletes single instance in recurring reservation
   *
   * @param int $recur_id Recurrence ID
   * @since 1.0
   */
   public function del_recur($recur_id) {
      if ($this->wpdb->delete($this->wpdb->phw_reservations_recur, array('recur_id' => $recur_id)))
         echo "Instance has been cancelled.";
      else
         $this->no_id_match_error();
   }


   /**
   * Updates existing reservation in database 
   * @since 1.0
   */
   public function update_into_db() {
      $result = $this->wpdb->update($this->wpdb->phw_reservations,
                              array( 'datetime_start' => $this->datetime_start,
                                     'datetime_end'   => $this->datetime_end,
                                     'purpose'        => $this->purpose,
                                     'room'           => $this->room,
                                     'recurs'         => $this->recurs,
                                     'recurs_until'   => $this->recurs_until,
                                     'recurs_on'      => $this->recurs_on),
                              array( 'res_id'         => $this->res_id),
                              array('%d', '%d', '%s', '%s', '%d', '%d', '%s'),
                              array('%d')
                              );
      if ($result) {
         echo "Your reservation has been updated.";
         $this->send_confirmed_email($this->res_id);
      }
      else {
         echo "ERROR: Could not update reservation details. Please contact "
              . antispambot(get_option('admin_email')) . " with this error.";
      }

      if ($this->recurs) {
         // remove & re-add
         $this->del_all_recur($this->res_id);
         $this->insert_recurring_into_db($this->res_id);
      }
   }
   
   
   /**
   * Display no res_id match error
   *
   * @todo make more robust to work with more errors
   */
   private function no_id_match_error() {
      echo "ERROR: Reservation ID does not match existing reservation. Please contact "
           . antispambot(get_option('admin_email')) . " with this error.";
   }

   /**
   * Returns true if reservation is recurring
   *
   */
   public function is_recurring() {
      if (isset($this->recurs))
         return $this->recurs;
      else
         return false;
   }
}
