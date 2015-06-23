<?php
/**
* Contains PHWReserveDBHandler class
* 
* @author David Baker
* @copyright 2015 Milligan College
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU Public License v2
* @since 1.1
*/


/**
* Database Handler
*
* Communicates with the WP database tables on behalf of other classes
* Use: PHWReserveDBHandler::get_instance()->some_method();
*
* @since 1.1
*/
class PHWReserveDBHandler {
   private static $instance; 
   private static $wpdb;
    
  
   /**
   * Initializes wpdb
   *
   * @since 1.1
   *
   * @return void
   */
   protected function __construct() {
      echo "<br>inside construct";
      global $wpdb;
      self::$wpdb =& $wpdb;
      self::$wpdb->phw_reservations = self::$wpdb->prefix . "phw_reservations";
      self::$wpdb->phw_reservations_recur = self::$wpdb->prefix . "phw_reservations_recur";
   }
   
   
   /**
   * Returns the Singleton instance of this class
   * 
   * @return PHWReserveDBHandler $instance
   * @since 1.1
   */
   public static function get_instance() {
      echo "<br>inside get_instance";
      static $instance = null;
      if (null === $instance) {
         $instance = new static();
      }

      return $instance;
   }


   /**
   * Check for conflicting confirmed reservations
   * 
   * @return boolean
   * @since 1.1
   */
   public static function chk_conflicting_confirmed_res($start, $end, $room, $res_id) {
      echo "<br>inside chk_conflicting_confirmed_res";
      $query = "SELECT res_id FROM " . self::$wpdb->phw_reservations .
               " WHERE %d < datetime_end
                AND %d > datetime_start
                AND %s = room
                AND %d <> res_id;";
      $query = self::$wpdb->prepare($query, $start, $end, $room, $res_id);
      var_dump($query);
      return self::$wpdb->query($query);
   }
   
   /**
   * Check for conflicting unconfirmed reservations (transients)
   * 
   * @return boolean
   * @since 1.1
   */
   public static function chk_conflicting_unconfirmed_res($start, $end, $room) {
      echo "<br>inside chk_conflicting_unconfirmed_res";
      $unconfirmed = self::get_unconfirmed_res();
      foreach ($unconfirmed as $result) {
         $option_name = $result['option_name'];
         $transient_name = str_replace('_transient_', '', $option_name);
         $transient_data = get_transient($transient_name);
         if ($start < $transient_data['datetime_end'] &&
                  $end > $transient_data['datetime_start'] &&
                  $room == $transient_data['room']) {
            return true;
         }
      }
      return false;
   }
   
   /**
   * Check conflicting recurring reservations
   * 
   * @return boolean
   * @since 1.1
   */
   public static function chk_conflicting_recurring_res($start, $end, $room) {
      echo "<br>inside chk_conflicting_recurring_res";
      $res_table = self::$wpdb->phw_reservations;
      $recur_table = self::$wpdb->phw_reservations_recur;
      $query = "SELECT recur_id 
                FROM (SELECT {$recur_table}.recur_id,
                             {$recur_table}.r_datetime_start,
                             {$recur_table}.r_datetime_end,
                             {$res_table}.room
                      FROM {$res_table},
                           {$recur_table}
                      WHERE {$res_table}.res_id =
                            {$recur_table}.res_id
                     ) AS recurring_reservations_set
                WHERE %s = room
                AND %d < r_datetime_end
                AND %d > r_datetime_start";
      $query = self::$wpdb->prepare($query, $room, $start, $end);
      return self::$wpdb->query($query);
   }
   

   /**
   * Returns array of unconfirmed (transients) reservations
   * 
   * @return mixed 
   * @since 1.1
   */
   public static function get_unconfirmed_res() {
      echo "<br>inside get_unconfirmed_res";
      self::delete_expired_transients();
      $query = "SELECT option_name
                FROM " . self::$wpdb->prefix . "options
                WHERE option_name LIKE '%transient_phwreserve%'";
      return self::$wpdb->get_results($query, ARRAY_A);
   }
 
   
   /**
   * Finds and deletes expired transients created by this plugin
   *
   * @since 1.0
   *
   * @return void
   */
   private static function delete_expired_transients() {
      $cur_time = time();
      $querySelectTimeouts = "SELECT option_name, option_value
                              FROM " . self::$wpdb->prefix . "options
                              WHERE option_name LIKE '%transient_timeout_phwreserve%'
                              AND option_value < {$cur_time};";
      $expiredTransients = self::$wpdb->get_results($querySelectTimeouts, ARRAY_A);
      if ($expiredTransients) {
         foreach ($expiredTransients as $transient) {
            $option_name = str_replace('_timeout', '', $transient['option_name']);
            delete_transient($option_name);
         } 
      }
   }
  
   
  /**
   * Returns res_id based on start date/time and room if exists
   * 
   * @since 1.1
   */
   public static function  get_res_id($datetime_start, $room) {
      $table = self::$wpdb->phw_reservations;
      $query_get_res_id = "SELECT res_id FROM {$table} 
                           WHERE datetime_start = %d 
                           AND room = %s";
      $query_get_res_id = self::$wpdb->prepare($query_get_res_id, $datetime_start, $room);
      return self::$wpdb->get_results($query_get_res_id);
   }

   /**
   * Inserts new reservation into database
   * 
   * Returns number of rows affected or false
   * 
   * @since 1.1
   */
   public static function insert_res($patron_name, $patron_email, $datetime_start,
                                     $datetime_end, $purpose, $room, $auth_code,
                                     $recurs, $recurs_until, $recurs_on ) {
      return self::$wpdb->insert(self::$wpdb->phw_reservations, 
                             array(
                                'patron_name'    => $patron_name,
                                'patron_email'   => $patron_email,
                                'datetime_start' => $datetime_start,
                                'datetime_end'   => $datetime_end,
                                'purpose'        => $purpose,
                                'room'           => $room,
                                'auth_code'      => $auth_code,
                                'recurs'         => $recurs,
                                'recurs_until'   => $recurs_until,
                                'recurs_on'      => $recurs_on
                             ));
   }

 /* @todo factor out db code from below methods 
*
*       |  
*       |
*       v
*/



   
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
