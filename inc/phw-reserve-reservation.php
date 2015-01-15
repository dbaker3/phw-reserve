<?php
/*
   PHWReserveReservation class
   David Baker, Milligan College 2015
*/

class PHWReserveReservationRequest {
   private reservation_id;
   private patron_name;
   private patron_email;
   private datetime_start;
   private datetime_end;
   private room;
   private purpose;
   
   function __construct($name, $email, $start, $end, $room, $purpose) {
      
      $this->patron_name = $name;
      $this->patron_email = $email;
      $this->datetime_start = $start;
      $this->datetime_end = $end;
      $this->room = $room;
      $this->purpose = $purpose;
      
      // TODO: Verify email address with conf. code

      // TODO: Confirm no time conflict

      // TODO: Add reservation to DB

      // TODO: Return success/fail boolean
   }
   
   function check_time_conflict() {}

   function verify_email() {}

   
}