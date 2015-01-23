<?php
/*
   PHWReserveReservation class
   David Baker, Milligan College 2015
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
   
   public function __construct($name, $email, $start, $end, $room, $purpose) {
      $this->patron_name = $name;
      $this->patron_email = $email;
      $this->datetime_start = $start;
      $this->datetime_end = $end;
      $this->room = $room;
      $this->purpose = $purpose;
   }
   
   public function check_time_conflict() {
      global $wpdb;
      $wpdb->phw_reservations = "{$wpdb->prefix}phw_reservations";
      $query = "SELECT res_id FROM {$wpdb->phw_reservations}
                WHERE {$this->datetime_start} < unix_timestamp(datetime_end)
                AND {$this->datetime_end} > unix_timestamp(datetime_begin)
                AND '{$this->room}' = room";
      $conflicting = $wpdb->query($query);
      if (!$conflicting) {
         // check transients for unconfirmed res requests
         $query = "SELECT `option_name` AS `name`,
                          `option_value` AS `value`
                   FROM {$wpdb->prefix}options
                   WHERE `option_name` LIKE `%transient_phw_%`";
         
      }
      
      return $conflicting;
   }

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

      // TODO: option for reply address
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
   
   public function insert_into_db() {
      // TODO: db insert
      
      // TODO: send final conf email
      $this->send_confirmed_email($res_id);
      echo "<p>Your reservation has been confirmed!</p>";
   }
   
   private function send_confirmed_email($res_id) {
   
   }
   
}