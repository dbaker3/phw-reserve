<?php
/**
* Contains the PHWReserveCalendar class
*
* @author David Baker
* @copyright 2015 Milligan College
* @since 1.0
*/


/**
* Calendar or View Room Availability class
*
* Allows user to select room and month to view current reservations that exist.
* Has option to create a reservation from each day listed. Logged in users see
* most reservation data. Non-logged in users only see dates and times.
*
* @since 1.0
*/
class PHWReserveCalendar {
   private $rooms;
   
   private $cal_room;
   private $cal_month;
   private $cal_month_timestamp;

   /**
   * Set available rooms setting
   * @param mixed $rooms Available rooms configured in plugin settings
   * @since 1.0
   */
   function __construct($rooms) {
      $this->rooms = $rooms;
   }
   
   
   /**
   * Set objects selected room and month properties
   * @since 1.0
   */
   public function set_fields($cal_room, $cal_month) {
      $this->cal_room = $cal_room;
      $this->cal_month = $cal_month;
   }
   
   
   /**
   * Displays HTML for room and month form
   *
   * @since 1.0
   */
   public function show_form() { ?>
      <div class="welshimer-form">
         <form>
            <p class="form">
            <label class="label" for="cal_room">Room:</label>
            <select class="text three-fourths" name='cal_room' id='cal_room' required>
               <?php foreach ($this->rooms as $room) { 
               echo "<option";
               if ($room == $this->cal_room) {echo " selected";};
               echo ">{$room}</option>";
               } ?>
            </select>
            </p>
            <p class="form">
            <label class="label" for="cal_month">Month:</label>
            <select class="text three-fourths" name='cal_month' id='cal_month' required>
               <?php for ($i = 0; $i < 12; $i++) {
               echo "<option";
               if (isset($this->cal_month) && date('n', strtotime('this month + ' . $i . " month")) == date('n', strtotime($this->cal_month))) {echo " selected";};
               echo ">" . date('F', strtotime('this month + ' . $i . " month")) . "</option>";
               } ?>
            </select>
            </p>
            <button class='submit full' type='submit' name='cal_view_cal' value='true'>View</button>
         </form>
      </div>
   <?php
   }


   /**
   * Calls methods to show existing reservations
   * @since 1.0
   * @todo check against transients
   * @todo change MySQL dependent query
   */
   public function show_reservations() {
      $results = $this->query_db();
      $this->print_reservations($results);
   }
   
   
   /**
   * Gets reservation data from tables
   *
   * Selects reservations and recurring reservations from their respective
   * tables then combines into one array, sorts it by starting datetime,
   * and returns the array
   *
   * @return mixed $results Reservation data for selected room and month
   * @since 1.0
   *
   */
   private function query_db() {
      $this->cal_month_timestamp = strtotime($this->cal_month);
      if (date('n') > date('n', $this->cal_month_timestamp)) {
         $this->cal_month_timestamp = strtotime(date('M', $this->cal_month_timestamp) . " +1 year");
      }
      global $wpdb;
      $wpdb->phw_reservations = "{$wpdb->prefix}phw_reservations";
      $query = "SELECT res_id, datetime_start, datetime_end, patron_name, patron_email, purpose, auth_code
                FROM {$wpdb->phw_reservations}
                WHERE 
                '{$this->cal_room}' = room AND
                FROM_UNIXTIME({$this->cal_month_timestamp}, '%c') = FROM_UNIXTIME(datetime_start, '%c')
                ORDER BY datetime_start";
      
      $result_res = $wpdb->get_results($query, ARRAY_A);

      // Recurring reservations from recur table
      $wpdb->phw_reservations_recur = "{$wpdb->prefix}phw_reservations_recur";
      $query = "SELECT {$wpdb->phw_reservations_recur}.res_id, 
                       {$wpdb->phw_reservations_recur}.recur_id,
                       {$wpdb->phw_reservations_recur}.r_datetime_start as datetime_start, 
                       {$wpdb->phw_reservations_recur}.r_datetime_end as datetime_end,
                       patron_name, patron_email, purpose, auth_code
                FROM {$wpdb->phw_reservations_recur}, {$wpdb->phw_reservations}
                WHERE 
                {$wpdb->phw_reservations_recur}.res_id = {$wpdb->phw_reservations}.res_id AND
                '{$this->cal_room}' = {$wpdb->phw_reservations}.room AND
                FROM_UNIXTIME({$this->cal_month_timestamp}, '%c') = FROM_UNIXTIME({$wpdb->phw_reservations_recur}.r_datetime_start, '%c')
                ORDER BY {$wpdb->phw_reservations_recur}.r_datetime_start";

      $result_rec = $wpdb->get_results($query, ARRAY_A);
      $merged = array_merge($result_res, $result_rec);
      
      if (!empty($merged))
         $merged = $this->subval_sort($merged, 'datetime_start');

      return $merged;
   }
 

   /**
   * Sorts multidimensional array by key
   *
   * Used by $this->query_db() to sort combined array of normal reservations
   * and recurring reservations. Gleaned from Adam S of firsttube.com
   */
   private function subval_sort($a,$subkey) {
      foreach($a as $k=>$v) {
         $b[$k] = strtolower($v[$subkey]);
      }
      asort($b);
      foreach($b as $key=>$val) {
         $c[] = $a[$key];
      }
      return $c;
   }


   /**
   * Displays reservation calendar
   *
   * @param mixed $results Reservation data for selected room and month
   * @since 1.0
   */
   private function print_reservations($results) {
      echo "<h4>Existing reservations for {$this->cal_room} during " . date('F Y', $this->cal_month_timestamp) . "</h4>";
      $days_in_month = date('t', $this->cal_month_timestamp);
      for ($i = 1; $i <= $days_in_month; $i++) {
         if (strtotime(date('n/', $this->cal_month_timestamp) . $i . date('/Y', $this->cal_month_timestamp)) < strtotime(date('n/j/Y'))) {
            continue;   // don't print dates before today
         }
         $the_date = strtotime(date('F', $this->cal_month_timestamp) . " " . $i . " " . date('Y', $this->cal_month_timestamp));
         echo "<div class='day-head'>" . date('F', $this->cal_month_timestamp) . " {$i} - " . date('l', $the_date) . "<span class='make-res'><a href='?cal_res_new=true&time_date={$the_date}'>make reservation</a></span></div>";
         echo "<ul>";
         foreach ($results as $res) {
            $res_date = date('MjY', $res['datetime_start']);
            $cur_date = date('M', $this->cal_month_timestamp) . $i . date('Y', $this->cal_month_timestamp);
            if ($res_date == $cur_date) {
               echo "<li class='res-info'>";
               if (is_user_logged_in()) {
                  if (array_key_exists('recur_id', $res)) {
                     echo " <a href='?cal_res_id={$res['res_id']}&amp;submit_del=true&amp;cal_auth={$res['auth_code']}' onclick='return confirm(\"Are you sure you want to delete this ENTIRE SERIES of recurring reservations?\")'  class='res-del'>delete series</a> ";
                     echo " <a href='?cal_recur_id={$res['recur_id']}&amp;cal_res_id={$res['res_id']}&amp;submit_del_occur=true&amp;cal_auth={$res['auth_code']}' onclick='return confirm(\"Are you sure you want to delete this SINGLE INSTANCE from the series of recurring reservations?\")'  class='res-del'>delete instance</a> ";
                  } 
                  else
                     echo " <a href='?cal_res_id={$res['res_id']}&amp;submit_del=true&amp;cal_auth={$res['auth_code']}' onclick='return confirm(\"Are you sure you want to delete this reservation?\")'  class='res-del'>delete</a> ";
               }
               echo "Reserved " . date('g:i a', $res['datetime_start']) . " - " . date('g:i a', $res['datetime_end']);
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
