<?php
/*
   PHWReserveMenu class
   David Baker, Milligan College 2015
*/

class PHWReserveMenu {
   private $rooms;
   
   function __construct($rooms) {
      $this->rooms = explode("\n", $rooms);
      $this->display_menu();
   }

   function display_menu() {
      $output =  "<div id='phwreserve-mainmenu'>";
      $output .= "   <ul>";
      $output .= "      <li>View Room Calendar:";
      $output .= "         <select id='room-selector'>";
      $output .= "            <option>Select a room...</option>";
      foreach ($this->rooms as $room) {
         $output .= "         <option>{$room}</option>";
      }
      $output .= "         </select>";
      $output .= "      </li>";
      $output .= "      <li><a href='?res_new=true'>Reserve a Study Room</a></li>";     
      $output .= "      <li><a href='?res_edit=true'>Change or Cancel a reservation</a></li>";
      $output .= "   </ul>";
      $output .= "</div>";
      
      echo $output;
   }

}