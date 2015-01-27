<?php
/*
   PHWReserveCalendar class
   David Baker, Milligan College 2015
*/

class PHWReserveCalendar {
   private $room

   function __construct($room) {
      $this->room = $room;
   }
   
   public function display_calendar() {
      html = "<table>";
      html .=  "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
      html .=  "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";     
      html .=  "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";     
      html .=  "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";     
      html .=  "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";         
      html .=  "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";         
      html .= "</table>";
      echo $html;
   }

}
