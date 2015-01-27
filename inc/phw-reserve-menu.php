<?php
/**
* Contains the PHWReserveMenu class
* @author David Baker
* @copyright 2015 Milligan College
* @since 1.0
*/


/**
* Displays main menu for plugin
* @since 1.0
*/
class PHWReserveMenu {
   private $rooms;

   /**
   * Sets room property
   * @since 1.0
   * @param mixed $rooms
   */
   function __construct($rooms) {
      $this->rooms = $rooms;
   }

   /**
   * Displays HTML for menu
   * @since 1.0
   * @return void
   * @todo Make prettier, more functional
   */
   public function display_menu() {
      $output =  "<div id='phwreserve-mainmenu'>";
         $output .= "<p>View Room Calendar: ";
         $output .= "<select id='room-selector'>";
            $output .= "<option>Select a room...</option>";
            foreach ($this->rooms as $room) {
            $output .= "<option>{$room}</option>";
            }
         $output .= "</select>";
         $output .= "</p>";
         $output .= "<p><a href='?res_new=true'>Reserve a Study Room</a></p>";     
         $output .= "<p><a href='?res_edit=true'>Change or Cancel a reservation</a></p>";
      $output .= "</div>";
      echo $output;
   }
}