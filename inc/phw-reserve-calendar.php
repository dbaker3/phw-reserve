<?php
/*
   PHWReserveCalendar class
   David Baker, Milligan College 2015
*/

class PHWReserveCalendar {
   private $rooms;
   private $selected_room;
   private $selected_month;

   function __construct($rooms) {
      $this->rooms = $rooms;
   }
   
   public function show_form() { ?>
      <div class="welshimer-form">
         <form>
            <p class="form">
            <label class="label" for="room_cal">Room:</label>
            <select class="text half" name='room_cal' id='room_cal' required>
               <?php foreach ($this->rooms as $room) { 
               echo "<option";
               if ($room == $_GET['room_cal']) {echo " selected";};
               echo ">{$room}</option>";
               } ?>
            </select>
            </p>
            <p class="form">
            <label class="label" for="room_month">Month:</label>
            <select class="text half" name='room_month' id='room_month' required>
               <?php for ($i = 0; $i < 12; $i++) {
               echo "<option";
               if (isset($_GET['room_month']) && date('n', strtotime('this month + ' . $i . " month")) == date('n', strtotime($_GET['room_month']))) {echo " selected";};
               echo ">" . date('F', strtotime('this month + ' . $i . " month")) . "</option>";
               } ?>
            </select>
            </p>
            <button class='submit three-fourths' type='submit' name='view_cal' value='true'>View</button>
         </form>
      </div>
   <?php
   }


   /**
   *
   *
   * @todo check against transients
   * @todo show more res info if logged in
   * @todo change MySQL dependent query
   */
   public function show_reservations() {
      $results = $this->query_db();
      $this->print_reservations($results);
   }
   
   
   private function query_db() {
      $this->selected_room = $_GET['room_cal'];
      $this->selected_month = strtotime($_GET['room_month']);
      if (date('n') > date('n', $this->selected_month)) {
         $this->selected_month = strtotime(date('M', $this->selected_month) . "+ 1 year");
      }
      global $wpdb;
      $wpdb->phw_reservations = "{$wpdb->prefix}phw_reservations";
      $query = "SELECT datetime_start, datetime_end, patron_name, patron_email, purpose
                FROM {$wpdb->phw_reservations}
                WHERE 
                '{$this->selected_room}' = room AND
                FROM_UNIXTIME({$this->selected_month}, '%c') = FROM_UNIXTIME(datetime_start, '%c')";
      return $wpdb->get_results($query, ARRAY_A);
   }
   
   
   private function print_reservations($results) {
      echo "<h4>Existing reservations for {$this->selected_room} during " . date('F Y', $this->selected_month) . "</h4>";
      $days_in_month = date('t', $this->selected_month);
      for ($i = 1; $i <= $days_in_month; $i++) {
         if (strtotime(date('n/', $this->selected_month) . $i . date('/Y', $this->selected_month)) < strtotime(date('n/j/Y'))) {
            continue;   // don't print dates before today
         }
         $the_date = strtotime(date('F', $this->selected_month) . " " . $i . " " . date('Y', $this->selected_month));
         echo "<div class='day-head'>" . date('F', $this->selected_month) . " {$i}<span class='make-res'><a href='?res_new=true&time_date={$the_date}'>make reservation</a></span></div>";
         echo "<ul>";
         foreach ($results as $res) {
            $res_date = date('MjY', $res['datetime_start']);
            $cur_date = date('M', $this->selected_month) . $i . date('Y', $this->selected_month);
            if ($res_date == $cur_date) {
               echo "<li>Reserved " . date('g:i a', $res['datetime_start']) . " - " . date('g:i a', $res['datetime_end']);
               if (is_user_logged_in()) {
                  echo " by " . $res['patron_name'] . " (" . $res['patron_email'] . ") for " . $res['purpose'];
               }
               echo "</li>";
            }
         }
         echo "</ul>";
      }
   }
   
}
