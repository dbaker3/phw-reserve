<?php
/*
   PHWReserveCalendar class
   David Baker, Milligan College 2015
*/

class PHWReserveCalendar {
   private $rooms;

   function __construct($rooms) {
      $this->rooms = $rooms;
   }
   
   public function display_calendar() { ?>
      <div class="welshimer-form">
         <p>View Room Availability</p>
            <form>
            <select class="text half" name='room_cal' id='room_cal' required>
               <option>Select a room...</option>
               <?php foreach ($this->rooms as $room) { 
               echo "<option>{$room}</option>";
               } ?>
            </select>
            <select class="text half" name='room_month' id='room_month' required>
               <?php for ($i = 0; $i < 12; $i++) {
               echo "<option>" . date('F', strtotime('this month + ' . $i . " month")) . "</option>";
               } ?>
            </select>
            <button class='submit three-fourths' type='submit' name='view_cal' value='true'>View</button>
         </form>
      </div>
   <?php
      
   }

}
