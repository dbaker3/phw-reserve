<?php
/**
* Contains the PHWReservePageController class
* @author David Baker
* @copyright 2015 Milligan College
* @since 1.0
*/


/**
* Handles requests to plugin via GET and POST data
*
* Receives requests through GET and POST variables. Interfaces with other
* classes to provide user interface.
* @since 1.0
*/
class PHWReservePageController {
   private $option_name = 'phwreserve_settings';
   private $rooms;
   private $valid_emails;

   // relevant session variables
   public $sv_room_cal = false;    // on main menu - View room calendar
   public $sv_new_res = false;     // on main menu - Reserve a room
   public $sv_edit_res = false;    // on main menu - Change/delete reservation
   
   public $sv_submit_new = false;  // on submitting form for new reservation
   public $sv_auth_code = false;   // awaiting input of authentication code

   
   /**
   * Class constructor
   * @since 1.0
   */
   function __construct() {
      $this->load_plugin_settings();
   }
   
   
   /**
   * Entry point
   * @since 1.0
   */
   public function init() {
      $this->load_session_vars();
      $this->handle_page_request();
   }
   
   /**
   * Loads settings
   * Loads plugin settings and places them into arrays
   * @since 1.0
   */
   private function load_plugin_settings() {
      $settings = get_option($this->option_name);
      $this->rooms = array_map('trim', explode("\n", $settings['rooms']));
      $this->valid_emails = array_map('trim', explode("\n", $settings['valid_emails']));
   }
   
   private function load_session_vars() {      // TODO: should I preload all of these or just test for them as needed?
      if (isset($_GET['room_cal'])) $this->sv_room_cal = $_GET['room_cal'];
      if (isset($_GET['res_new'])) $this->sv_new_res = $_GET['res_new'];
      if (isset($_GET['res_edit'])) $this->sv_edit_res = $_GET['res_edit'];
      if (isset($_POST['submit_new'])) $this->sv_submit_new = true;
      if (isset($_GET['auth_code'])) $this->sv_auth_code = $_GET['auth_code'];
   }
   
   /**
   * Sends control to proper class on page load
   *
   * Checks GETs and POSTs to see what the current state of user interaction is.
   * @since 1.0
   * @return void
   */
   private function handle_page_request() {
      // Selected - View Room Calendar
      if ($this->sv_room_cal) {
         $this->handle_view_cal_request();
      }
      
      // Selected - Reserve a Room
      elseif ($this->sv_new_res) {
         $this->handle_new_res_request();
      }
      
      // Selected - Change or Cancel Reservation
      elseif ($this->sv_edit_res) {
         $this->handle_edit_res_request();
      }
      
      // Submitted new reservation form
      elseif ($this->sv_submit_new) {
         $this->handle_new_res_submission();
      }
      
      // Submitted authentication code
      elseif ($this->sv_auth_code) {
         $this->handle_auth_code_submission();
      }
      
      // Initial Page Load
      else {
         $menu = new PHWReserveMenu($this->rooms);
         $menu->display_menu();
      }
   }
   
   /**
   * Handles submission of new reservation form
   * Validates user inputs, verifies that requested time doesn't conflict with
   * an existing reservation. Displays form with errors if there are any. If OK
   * and WP user logged in, inserts reservation into table. If OK and not logged
   * in, calls authenticate_user() to make sure they are who they say they are
   *
   * @since 1.0
   * @todo kinda smells...should time-check be moved to validate_inputs?
   */
   private function handle_new_res_submission() { 
      $form = new PHWReserveForm($this->rooms, $this->valid_emails);
      if ($form->validate_inputs()) {
         $begin_time = strtotime(date('n/j/y', $form->time_date_valid) . ' ' . date('G:i e', $form->time_start_valid));
         $end_time= strtotime(date('n/j/y', $form->time_date_valid) . ' ' . date('G:i e', $form->time_end_valid));
         $reservation = new PHWReserveReservationRequest($form->patron_name,
                        $form->patron_email, $begin_time, $end_time, $form->reserve_room, $form->patron_purpose);
         if ($reservation->check_time_conflict()) {
            $form->hasError = true;
            $form->timeStartError = "{$form->reserve_room} is already reserved during this time.";
            $form->timeEndError = "";
            $form->display_form();
         }
         else {
            if (is_user_logged_in())
               $reservation->insert_into_db();
            else
               $reservation->authenticate_user();
         }
      }
      else { // form validation error
         $form->display_form();  
      }
   }
   
   private function handle_new_res_request() {
      $form = new PHWReserveForm($this->rooms, $this->valid_emails);
      $form->display_form();
   }
   
   private function handle_edit_res_request() {
      $form = new PHWReserveForm($rooms, $emails);
      // Do edit specific methods
      $form->display_form();
   }
   
   function handle_view_cal_request() {
      $calendar = new $PHWReserveCalendar($this->sv_room_cal);
   }
   
   private function handle_auth_code_submission() {
      $transient_data = get_transient($_GET['transient']);     // TODO: load all of these session vars
      $auth_code = $transient_data['auth_code'];
      if ($auth_code == $_GET['auth_code']) {
         $reservation = new PHWReserveReservationRequest($transient_data['patron_name'], 
                                                         $transient_data['patron_email'], 
                                                         $transient_data['datetime_start'], 
                                                         $transient_data['datetime_end'], 
                                                         $transient_data['room'], 
                                                         $transient_data['purpose']);
         $reservation->insert_into_db();
      }
      else {
         echo '<p><strong>Error:</strong> Your authorization code does not match this reservation, or your email link has expired. If you belive you have incorrectly received this error, please contact ' . antispambot(get_option('admin_email')) . '</p>';
      }
      
      
   }
}
