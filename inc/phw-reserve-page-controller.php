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
      $this->rooms = $settings['rooms'];
      $this->valid_emails = $settings['valid_emails'];
   }
   
   function load_session_vars() {
      if (isset($_GET['room_cal'])) $this->sv_room_cal = $_GET['room_cal'];
      if (isset($_GET['res_new'])) $this->sv_new_res = $_GET['res_new'];
      if (isset($_GET['res_edit'])) $this->sv_edit_res = $_GET['res_edit'];
      
      if (isset($_POST['submit_new'])) $this->sv_submit_new = true;
      if (isset($_POST['auth_code'])) $this->sv_auth_code = $_POST['auth_code'];
   }
   
   function handle_page_request() {
      // View Room Calendar
      if ($this->sv_room_cal) {
         $this->call_res_calendar();
      }
      
      // Reserve a Room
      elseif ($this->sv_new_res) {
         $this->call_res_form('new');
      }
      
      // On submit a Reserve room
      elseif ($this->sv_submit_new) {
         $this->call_res_form('submit_new');
      }
      
      // Change/Delete Reservation
      elseif ($this->sv_edit_res) {
         $this->call_res_form('edit');     
      }
      
      // No option
      else {
         $this->call_res_menu();
      }
   }

   function call_res_menu() {
      $menu = new PHWReserveMenu($this->rooms);
   }
   
   function call_res_form($action) {
      $form = new PHWReserveForm($action);
   }
   
   function call_res_calendar() {
      $calendar = new $PHWReserveCalendar($this->sv_room_cal);
   }
   
}
