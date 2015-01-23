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
      
      // TESTING
/*    echo "<br>".$action."<br>";
      echo $name."<br>";
      echo $email."<br>";
      echo date('n/j/Y g:i A e', $start)."<br>".$start."<br>";
      echo date('n/j/Y g:i A e', $end)."<br>".$end."<br>";
      echo $room."<br>";
      echo $purpose."<br>";
*/ 
     
      // TODO: Authenticate user by email auth code

      
      // TODO: Add reservation to DB

   }
   
   public function check_time_conflict() {
      global $wpdb;
      $wpdb->phw_reservations = "{$wpdb->prefix}phw_reservations";
      $query = "SELECT res_id FROM {$wpdb->phw_reservations}
                WHERE {$this->datetime_start} < unix_timestamp(datetime_end)
                AND {$this->datetime_end} > unix_timestamp(datetime_begin)
                AND '{$this->room}' = room";
      $conflicting = $wpdb->query($query);
      return $conflicting;
   }

   public function authenticate_user() {
      $auth_code = substr(md5(mt_rand()), -5);
      
      // TODO: email auth_code to user
      echo $auth_code;
      
      $transient_name = 'phw_' . time();
      $transient_data = array($this->patron_name,     // TODO: Keys
                              $this->patron_email, 
                              $this->datetime_start, 
                              $this->datetime_end, 
                              $this->room, 
                              $this->purpose, 
                              $auth_code);
      
      set_transient($transient_name, $transient_data, HOUR_IN_SECONDS);
      
      ?>
      <form action=<?php the_permalink() ?> method="post">
         <label for="auth_code">Authorization Code:* </label><input type="text" id="auth_code" name="auth_code" required />
         <input type="hidden" id="transient_name" name="transient_name" value="<?php echo $transient_name; ?>"/>
         <input type="submit" id="submit_auth" name="submit_auth" value="Submit" />
      <?php
   }
   
   public function insert_into_db() {
      // TODO: Code this!
      echo "Inserted!";
   }
   
 
   
}