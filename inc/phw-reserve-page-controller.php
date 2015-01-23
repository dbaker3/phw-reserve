<?php
/*
   PHWReservePageController class
   David Baker, Milligan College 2015
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

   function __construct() {
      $this->load_plugin_settings();
   }
   
   function init() {
      $this->load_session_vars();
      $this->handle_page_request();
   }
   
   function load_plugin_settings() {
      $settings = get_option($this->option_name);
      $this->rooms = array_map('trim', explode("\n", $settings['rooms']));
      $this->valid_emails = array_map('trim', explode("\n", $settings['valid_emails']));
   }
   
   function load_session_vars() {
      if (isset($_GET['room_cal'])) $this->sv_room_cal = $_GET['room_cal'];
      if (isset($_GET['res_new'])) $this->sv_new_res = $_GET['res_new'];
      if (isset($_GET['res_edit'])) $this->sv_edit_res = $_GET['res_edit'];
      if (isset($_POST['submit_new'])) $this->sv_submit_new = true;
      if (isset($_POST['auth_code'])) $this->sv_auth_code = $_POST['auth_code'];
   }
   
   function handle_page_request() {
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

   private function handle_new_res_submission() { // TODO: kinda smells...should time-check be moved to validate_inputs?
      $form = new PHWReserveForm($this->rooms, $this->valid_emails);
      if ($form->validate_inputs()) {
         $begin_time = strtotime(date('n/j/y', $form->time_date_valid) . ' ' . date('G:i e', $form->time_start_valid));
         $end_time= strtotime(date('n/j/y', $form->time_date_valid) . ' ' . date('G:i e', $form->time_end_valid));
         $reservation = new PHWReserveReservationRequest($form->patron_name,
                        $form->patron_email, $begin_time, $end_time, $form->reserve_room, $form->patron_purpose);
         if ($reservation->check_time_conflict()) {
            $form->hasError = true;
            $form->timeStartError = "Your requested time overlaps with an existing reservation for this room.";
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
      $transient_data = get_transient($_POST['transient_name']);     // TODO: load all of these session vars
      $auth_code = $transient_data[6];
      if ($auth_code == $_POST['auth_code']) {
                                 // TODO: clean this crap up with keys ------v
         $reservation = new PHWReserveReservationRequest($transient_data[0], $transient_data[1], $transient_data[2], $transient_data[3], $transient_data[4], $transient_data[5]);
         $reservation->insert_into_db();
      }
      else {
         echo 'no match';
      }
      
      
   }
}
