<?php
/**
* Contains PHWReserveReservationRequest class
* 
* @author David Baker
* @copyright 2015 Milligan College
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
   private $res_id;     // only set when res is being edited
   private $patron_name;
   private $patron_email;
   private $datetime_start;
   private $datetime_end;
   private $room;
   private $purpose;
   private $auth_code;
   private $wpdb;
   
   
   /**
   * Sets up reservation properties
   *
   * @param string $name
   * @param string $email
   * @param string $start Unix timestamp of start date/time
   * @param string $end Unix timestamp of start date/time
   * @param string $room
   * @param string $purpose Reason requestor is reserving room
   *
   * @since 1.0
   *
   * @return void
   */
   public function __construct($name = '', $email = '', $start = '', $end = '', 
                               $room = '', $purpose = '', $auth_code='') {
      $this->res_id = 0;
      $this->patron_name = $name;
      $this->patron_email = $email;
      $this->datetime_start = $start;
      $this->datetime_end = $end;
      $this->room = $room;
      $this->purpose = $purpose;
      $this->auth_code = $auth_code;
      
      global $wpdb;
      $this->wpdb =& $wpdb;
      $this->wpdb->phw_reservations = "{$this->wpdb->prefix}phw_reservations";
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
   */
   public function set_properties($res_id, $name, $email, $start, $end, $room, $purpose, $auth) {
      $this->res_id = $res_id;
      $this->patron_name = $name;
      $this->patron_email = $email;
      $this->datetime_start = $start;
      $this->datetime_end = $end;
      $this->room = $room;
      $this->purpose = $purpose;
      $this->auth_code = $auth;
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
   * @todo should this be moved to the PHWReserveForm class?
   */
   public function check_time_conflict() {
      // confirmed reservations
      $query = "SELECT res_id FROM {$this->wpdb->phw_reservations}
                WHERE {$this->datetime_start} < datetime_end
                AND {$this->datetime_end} > datetime_start
                AND '{$this->room}' = room
                AND {$this->res_id} <> res_id";
      $conflicting = $this->wpdb->query($query);
      // unconfirmed reservations
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
            if ($this->datetime_start < $transient_data['datetime_end'] &&
                     $this->datetime_end > $transient_data['datetime_start'] &&
                     $this->room == $transient_data['room']) {
               $conflicting = true;
               break;
            }
         }
      }
      return $conflicting;
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
      $this->auth_code = substr(md5(mt_rand()), -14);
      
      $transient_name = 'phwreserve_' . time();
      $transient_data = array('patron_name'    => $this->patron_name,
                              'patron_email'   => $this->patron_email, 
                              'datetime_start' => $this->datetime_start, 
                              'datetime_end'   => $this->datetime_end, 
                              'room'           => $this->room, 
                              'purpose'        => $this->purpose, 
                              'auth_code'      => $this->auth_code);
      set_transient($transient_name, $transient_data, HOUR_IN_SECONDS);
      
      $this->send_auth_code_email($transient_name);
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
   * @todo option for reply address
   * @todo DRY email methods
   */
   private function send_auth_code_email($transient_name) {
      $conf_url = get_permalink() . '?transient=' . $transient_name . '&auth_code=' . $this->auth_code;
   	$emailTo = $this->patron_email;
		$subject =  'Please confirm your room reservation request';
		$body = '<h3>Please click the following link to confirm your room reservation request made ' . date("F j, Y, g:i a") .'</h3>';
      $body .= "<p><a href='{$conf_url}'>{$conf_url}</a></p>";
		$body .= '<p><strong>Requested by: </strong>' . $this->patron_name . ' [' . $this->patron_email . ']<br />';
		$body .= '<strong>Date: </strong>' . date('D, M j, Y', $this->datetime_start) . ' from ' . date('g:i A', $this->datetime_start) . ' - ' . date('g:i A', $this->datetime_end) . '<br />';
		$body .= '<strong>For: </strong>' . $this->purpose . '<br />';
		$body .= '<strong>Room: </strong>' . $this->room . '</p>';

		$headers[] = 'From: Room Reservation Webform <' . get_option('admin_email') . '>';
		$headers[] = 'Reply-To: ' . get_option('admin_email');
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
   *
   */
   public function insert_into_db() {
      $query_get_res_id = "SELECT res_id FROM {$this->wpdb->phw_reservations} 
                           WHERE datetime_start = '{$this->datetime_start}' 
                           AND room = '{$this->room}'";
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
                                'auth_code'      => $this->auth_code
                             ));
         if (!$success) {
            echo "There was an error inserting data into the database. Please contact " . antispambot(get_option('admin_email')) . " with this error.";
            wp_die();
         }
         $res_id = $this->wpdb->get_results($query_get_res_id);
         $this->send_confirmed_email($res_id[0]->res_id);
      }
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
   * @todo option for reply address
   */
   private function send_confirmed_email($res_id) {
      $conf_url = get_permalink() . '?res_edit=true&res_id=' . $res_id . '&auth=' . $this->auth_code;
    	$emailTo = $this->patron_email;
		$subject = 'Room Reservation Confirmation';
      $body = "<h3>Reservation Confirmed!</h3>";
      $body .= "<p>Your reservation request is complete. If someone is in the room when you arrive, please tell them that you have a reservation and politely ask them to leave. If you are uncomfortable doing this, please ask a library worker to assist you.</p>";
      $body .= "<h3>Reservation Details:</h3>";
  		$body .= '<p><strong>Requested by: </strong>' . $this->patron_name . ' [' . $this->patron_email . ']<br />';
		$body .= '<strong>Date: </strong>' . date('D, M j, Y', $this->datetime_start) . ' from ' . date('g:i A', $this->datetime_start) . ' - ' . date('g:i A', $this->datetime_end) . '<br />';
		$body .= '<strong>For: </strong>' . $this->purpose . '<br />';
		$body .= '<strong>Room: </strong>' . $this->room . '</p>';
      $body .= "<p>If you need to change or cancel your reservation, please visit this link:";
      $body .= "<br /><a href='{$conf_url}'>{$conf_url}</a></p>";

		$headers[] = 'From: Room Reservation Webform <' . get_option('admin_email') . '>';
		$headers[] = 'Reply-To: ' . get_option('admin_email');
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
   * @todo replace $res_id parameter with $this->res_id
   */
   public function get_res_data($res_id) {
      $query = "SELECT * FROM {$this->wpdb->phw_reservations} WHERE res_id = '{$res_id}'";
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
   * @todo replace $res_id parameter with $this->res_id  
   */
   public function get_res_auth_code($res_id) {
      $query = "SELECT auth_code FROM {$this->wpdb->phw_reservations} WHERE res_id = '{$res_id}'";
      $auth_code = $this->wpdb->get_row($query, ARRAY_A);
      if ($auth_code['auth_code']) 
         return $auth_code['auth_code'];
      else
         $this->no_id_match_error();
   }


   /**
   * Deletes a reservation from the table
   *
   * @param int $res_id Reservation ID 
   * @return void
   * @since 1.0
   *
   * @todo replace $res_id parameter with $this->res_id  
   */  
   public function del_res($res_id) {
      if ($this->wpdb->delete($this->wpdb->phw_reservations, array('res_id' => $res_id)))
         echo "Reservation has been cancelled.";
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
                                     'room'           => $this->room),
                              array( 'res_id'         => $this->res_id),
                              array('%d', '%d', '%s', '%s'),
                              array('%d')
                              );
                              
      if ($result) {
         echo "Your reservation has been updated.";
         $this->send_confirmed_email($this->res_id);
      }
      elseif ($result === 0) {
         echo "You did not make any changes to your reservation.";
      }
      else {
         echo "ERROR: Could not update reservation details. Please contact "
              . antispambot(get_option('admin_email')) . " with this error.";
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
}