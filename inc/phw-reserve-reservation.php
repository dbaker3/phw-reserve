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
   private $action;     // new or edit res
   
   function __construct($action, $name, $email, $start, $end, $room, $purpose) {
      $this->action = $action;
      $this->patron_name = $name;
      $this->patron_email = $email;
      $this->datetime_start = $start;
      $this->datetime_end = $end;
      $this->room = $room;
      $this->purpose = $purpose;
      
      // TESTING
      echo "<br>".$action."<br>";
      echo $name."<br>";
      echo $email."<br>";
      echo date('n/j/Y g:i A e', $start)."<br>".$start."<br>";
      echo date('n/j/Y g:i A e', $end)."<br>".$end."<br>";
      echo $room."<br>";
      echo $purpose."<br>";
      
      // TODO: Verify email address with conf. code

      // TODO: Confirm no time conflict
      echo $this->check_time_conflict();
      
      // TODO: Add reservation to DB

      // TODO: Return success/fail boolean
   }
   
   function check_time_conflict() {
      global $wpdb;
      $wpdb->phw_reservations = "{$wpdb->prefix}phw_reservations";
      $query = "SELECT res_id FROM {$wpdb->phw_reservations}
                WHERE {$this->datetime_start} < unix_timestamp(datetime_end)
                AND {$this->datetime_end} > unix_timestamp(datetime_begin)
                AND '{$this->room}' = room";
      echo $query;          
      $conflicting = $wpdb->query($query);
      return $conflicting;
   }

   function verify_email() {
      if (is_user_logged_in()) return true;
      
      
   }

   
}