<?php
/**
* Contains the PHWReserveCalendar class
*
* @author David Baker
* @copyright 2015 Milligan College
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU Public License v2
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
   * Displays HTML for room and month form.
   * Checks room list for any included <optgroup> tags and deals with those
   * accordingly. 
   *
   * @since 1.0
   */
   public function show_form() { ?>
      <div class="welshimer-form">
         <form>
            <p class="form">
            <label class="label" for="cal_room">Room:</label>
            <select class="text three-fourths" name='room' id='cal_room' required>
               <?php foreach ($this->rooms as $room) { 
                  if (!preg_match("/<\/?optgroup[^<]*/", $room)) { 
                     echo "<option";
                     if ($room == $this->cal_room) {echo " selected";};
                     echo ">{$room}</option>";
                  } 
                  else echo $room; // "<optgroup label='Branch Location'>" and "</optgroup>"
               } ?>
            </select>
            </p>
            <p class="form">
            <label class="label" for="cal_month">Month:</label>
            <select class="text three-fourths" name='month' id='cal_month' required>
               <?php for ($i = 0; $i < 12; $i++) {
               echo "<option";
               if (isset($this->cal_month) && date('n', strtotime('first day of ' . date('F Y') . ' + ' . $i . " month")) == date('n', strtotime('first day of ' . $this->cal_month . ' ' . date('Y')))) {echo " selected";};
               echo ">" . date('F', strtotime('first day of ' . date('F Y') . ' + ' . $i . " month")) . "</option>";
               } ?>
            </select>
            </p>
            <button class='submit full' type='submit' name='method' value='handle_cal_request'>View</button>
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
      $this->cal_month_timestamp = strtotime('first day of ' . $this->cal_month . ' ' . date('Y'));
      if (date('n') > date('n', $this->cal_month_timestamp)) {
         $this->cal_month_timestamp = strtotime("first day of " . date('M', $this->cal_month_timestamp) . ' ' . (date('Y') + 1));
      }
      global $wpdb;
      $wpdb->phw_reservations = "{$wpdb->prefix}phw_reservations";
      $query = "SELECT res_id, datetime_start, datetime_end, patron_name, patron_email, purpose, auth_code
                FROM {$wpdb->phw_reservations}
                WHERE 
                %s = room AND
                FROM_UNIXTIME(%d, '%%c') = FROM_UNIXTIME(datetime_start, '%%c')
                ORDER BY datetime_start";
      $query = $wpdb->prepare($query, $this->cal_room, $this->cal_month_timestamp);
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
                %s = {$wpdb->phw_reservations}.room AND
                FROM_UNIXTIME(%d, '%%c') = FROM_UNIXTIME({$wpdb->phw_reservations_recur}.r_datetime_start, '%%c')
                ORDER BY {$wpdb->phw_reservations_recur}.r_datetime_start";
      $query = $wpdb->prepare($query, $this->cal_room, $this->cal_month_timestamp);
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
         echo "<div class='day-head'>" . date('F', $this->cal_month_timestamp) . " {$i} - " . date('l', $the_date) . "<span class='make-res'><a href='?method=handle_new_res_request&amp;date={$the_date}&amp;room=" . urlencode($this->cal_room) . "'>make reservation</a></span></div>";
         echo "<ul>";
         foreach ($results as $res) {
            $res_date = date('MjY', $res['datetime_start']);
            $cur_date = date('M', $this->cal_month_timestamp) . $i . date('Y', $this->cal_month_timestamp);
            if ($res_date == $cur_date) {
               echo "<li class='res-info'>";
               if (is_user_logged_in()) {
                  if (array_key_exists('recur_id', $res)) {
                     echo " <a href='?method=handle_del_res_submission&amp;res_id={$res['res_id']}&amp;auth_code={$res['auth_code']}' class='cal-button res-del res-del-series'>delete series</a> ";
                     echo " <a href='?method=handle_del_occur_submission&amp;recur_id={$res['recur_id']}&amp;res_id={$res['res_id']}&amp;auth_code={$res['auth_code']}' class='cal-button res-del res-del-occur'>delete instance</a> ";
                  } 
                  else
                     echo " <a href='?method=handle_del_res_submission&amp;res_id={$res['res_id']}&amp;auth_code={$res['auth_code']}' class='cal-button res-del res-del-single'>delete</a> ";
                     echo " <a href='?method=handle_edit_res_request&amp;res_id={$res['res_id']}&amp;auth_code={$res['auth_code']}' class='cal-button res-edit'>edit</a> ";
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
