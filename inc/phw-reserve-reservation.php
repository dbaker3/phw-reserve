<?php
/**
* The PHWReserveReservation class
* 
* @author David Baker
* @copyright 2015 Milligan College
* @since 1.0
*/

class PHWReserveReservationRequest {
   private $reservation_id;
   private $patron_name;
   private $patron_email;
   private $datetime_start;
   private $datetime_end;
   private $room;
   private $purpose;
   private $auth_code;
   
   
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
   public function __construct($name, $email, $start, $end, $room, $purpose) {
      $this->patron_name = $name;
      $this->patron_email = $email;
      $this->datetime_start = $start;
      $this->datetime_end = $end;
      $this->room = $room;
      $this->purpose = $purpose;
   }

   
   /**
   * Returns true if requested time conflicts with an existing reservation
   *
   * Checks the database table phw_reservations for any confirmed reservations
   * that conflict with the requested date/time and room, and returns true if 
   * found. If false, checks for conflicts with as-of-yet confirmed requests 
   * that exist only as transients awaiting confirmation. Returns true if found.
   *
   * Also deletes expired transients prior to checking for conflicting transients
   * 
   * @since 1.0
   * @return boolean
   */
   public function check_time_conflict() {
      global $wpdb;

      // confirmed reservations
      $wpdb->phw_reservations = "{$wpdb->prefix}phw_reservations";
      $query = "SELECT res_id FROM {$wpdb->phw_reservations}
                WHERE {$this->datetime_start} < unix_timestamp(datetime_end)
                AND {$this->datetime_end} > unix_timestamp(datetime_begin)
                AND '{$this->room}' = room";
      $conflicting = $wpdb->query($query);

      // unconfirmed reservations
      if (!$conflicting) {
         $this->delete_expired_transients();
         $query = "SELECT option_name
                   FROM {$wpdb->prefix}options
                   WHERE option_name LIKE '%transient_phwreserve%'";
         $results = $wpdb->get_results($query, ARRAY_A);
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
      $this->auth_code = substr(md5(mt_rand()), -5);
      
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
   */
   private function send_auth_code_email($transient_name) {
      $conf_url = get_permalink() . '?transient=' . $transient_name . '&auth_code=' . $this->auth_code;
   	$emailTo = $this->patron_email;
		$subject =  'Confirm Room Reservation Request';
		$body = '<h4>Please click the following link to confirm your room reservation request made ' . date("F j, Y, g:i a") .'</h4>';
      $body .= "<p><a href='{$conf_url}'>{$conf_url}</a></p>";
		$body .= '<p><strong>Requested by: </strong>' . $this->patron_name . ' [' . $this->patron_email . ']</p>';
		$body .= '<p><strong>Date: </strong>' . date('D, M j, Y', $this->datetime_start) . ' from ' . date('g:i A', $this->datetime_start) . ' - ' . date('g:i A', $this->datetime_end) . '</p>';
		$body .= '<p><strong>For: </strong>' . $this->purpose . '</p>';
		$body .= '<p><strong>Room: </strong>' . $this->room . '</p>';

		$headers[] = 'From: Room Reservation Webform <library@milligan.edu>';
		$headers[] = 'Reply-To: ' . $patron_email;
		$headers[] = 'content-type: text/html';

		if (wp_mail( $emailTo, $subject, $body, $headers )) {
         echo "<h2>Please Confirm</h2>";
         echo "<p>An email has been sent to {$this->patron_email}. <strong>You must visit the link contained in the email to confirm your reservation</strong>.</p>";
         echo "<p><strong>Note:</strong> If you do not click the link your request will expire and the reservation will be canceled.</p>";
      }
   }

   
   /**
   * Insert reservation into phw_reservations table
   *
   * Saves the current reservation request into the table and calls the
   * send_confirmed_email() method 
   *
   * @since 1.0
   */
   public function insert_into_db() {
      // TODO: db insert
      
      // TODO: send final conf email
      $this->send_confirmed_email($res_id);
      echo "<p>Your reservation has been confirmed!</p>";
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
   * @todo write method code
   */
   private function send_confirmed_email($res_id) {
   
   }

   
   /**
   * Finds and deletes expired transients created by this plugin
   *
   * Each transient exists in 2 rows in the wp_options table. This method finds
   * and deletes both the 'user-created' transient row (e.g. _transient_phwreserve_1422302516)
   * and the WordPress generated row which contains the transient expiration 
   * date (e.g. _transient_timeout_phwreserve_1422302516) for expired transients
   *
   * @since 1.0
   *
   * @return void
   */
   private function delete_expired_transients() {
      global $wpdb;
      $cur_time = time();
      
      // find expired transients by their WP generated timeout row entries
      $querySelectTimeouts = "SELECT option_name, option_value
                              FROM {$wpdb->prefix}options
                              WHERE option_name LIKE '%transient_timeout_phwreserve%'
                              AND option_value < {$cur_time};";
      $results = $wpdb->get_results($querySelectTimeouts, ARRAY_A);
 
      // delete expired transient rows
      $queryDeleteExpired = "DELETE
                             FROM {$wpdb->prefix}options
                             WHERE ";
      $i = 0;
      $len = count($results);
      foreach ($results as $result) {
         $option_name = str_replace('_timeout', '', $result['option_name']);
         if ($i == 0)
            $queryDeleteExpired .= "option_name = '{$option_name}' ";
         elseif ($i == $len-1)
            $queryDeleteExpired .= "OR option_name = '{$option_name}';";
         else
            $queryDeleteExpired .= "OR option_name = '{$option_name}' ";
         $i++;
      }
      $wpdb->query($queryDeleteExpired);
      
      // delete transient expiration date rows
      $queryDeleteTimeouts = "DELETE
                              FROM {$wpdb->prefix}options
                              WHERE option_name LIKE '%transient_timeout_phwreserve%'
                              AND option_value < {$cur_time};";
      $wpdb->query($queryDeleteTimeouts);
   }
}