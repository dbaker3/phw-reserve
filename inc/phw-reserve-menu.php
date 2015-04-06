<?php
/**
* Contains the PHWReserveMenu class
*
* @author David Baker
* @copyright 2015 Milligan College
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU Public License v2
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
   * @param mixed $rooms From WP plugin settings
   */
   function __construct($rooms) {
      $this->rooms = $rooms;
   }

   /**
   * Displays HTML for menu
   *
   * Presents user with options to Reserve a room, view room availability, and if
   * user is logged in, change or cancel a reservation.
   *
   * @since 1.0
   * @return void
   */
   public function display_menu() { ?>
      <div class='welshimer-form' action=''>
         <form action='' method='GET'>
            <button class='submit three-fourths' type='submit' name='menu_res_new' value='true'>Reserve a Room</button>
            <button class='submit three-fourths' type='submit' name='menu_room_cal' value='true'>View Room Availability</button>
            <?php if (is_user_logged_in()) 
              // echo "<button class='submit three-fourths' type='submit' name='res_edit' value='true'>Change or Cancel a Reservation</button>";
            ?>
         </form>
      </div> <?php
   }
}
