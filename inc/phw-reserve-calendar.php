<?php
/*
   PHWReserveCalendar class
   David Baker, Milligan College 2015
*/

class PHWReserveCalendar {
   private $room;

   function __construct($room) {
      $this->room = $room;
   }
   
   public function display_calendar() { ?>
            <select name='room_cal' id='room_cal' required>
               <option>Select a room...</option>
               <?php foreach ($this->rooms as $room) { 
               echo "<option>{$room}</option>";
               } ?>
         </select>
         <select name='room_month' id='room_month' required>
            <?php for ($i = 0; $i < 12; $i++) {
               echo "<option>" . date('F', strtotime('this month + ' . $i . " month")) . "</option>";
            } ?>
         </select>
   <?php
      
   }

}
