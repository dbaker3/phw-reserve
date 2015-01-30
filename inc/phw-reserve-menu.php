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
   public function display_menu() { ?>
      <div id='phwreserve-mainmenu' action='' method='get'>
         <ul>
            <li><a href="?res_new=true">Reserve a Room</a></li>
            <li><a href="?room_cal=true">View Room Availability</a></li>
            <li><a href="?res_edit=true">Change or Cancel a Reservation</a></li>
         </ul>
      </div> <?php
   }
}