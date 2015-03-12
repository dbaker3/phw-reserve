<?php
/**
* Contains the PHWReservePageController class
*
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
   private $rooms;
   private $valid_emails;

   // GET & POST variables
   private $menu_room_cal = false;    // on main menu - View room calendar
   private $menu_new_res = false;     // on main menu - Reserve a room
   
   private $email_edit_res = false;    // on clicking edit/cancel link in email
   
   private $form_submit_new = false;  // on submitting form for new reservation
   private $email_auth_code = false;   // on clicking auth code link in email
   private $sv_submit_edit = false; // on submitting form for editing reservation
   
   // PHWReserveReservationRequest
   private $email_res_id = false; 
   private $email_transient = false;
   
   // PHWReserveForm
   private $email_auth = false;
   private $recurs = false;

   // PHWReserveCalendar
   private $cal_view_cal = false;
   private $cal_room = false;
   private $cal_month = false;
   private $cal_res_id = false;
   private $cal_auth = false;
   private $cal_submit_del = false;  // on logged in user clicking delete on res
   private $cal_res_new = false;
   
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
      $this->load_get_post_vars();
      $this->handle_page_request();
   }
   
   
   /**
   * Loads plugin settings
   * @since 1.0
   */
   private function load_plugin_settings() {
      $option_name = PHWReserveSettings::get_option_name();
      $settings = get_option($option_name);
      $this->rooms = array_map('trim', explode("\n", $settings['rooms']));
      $this->valid_emails = array_map('trim', explode("\n", $settings['valid_emails']));
   }
   
   
   /**
   * Loads GET and POST vars into object properties
   *
   * @since 1.0
   * @todo Should I preload all of these or just test for them as needed?
   */
   private function load_get_post_vars() {
      if (isset($_GET['menu_room_cal'])) $this->menu_room_cal = $_GET['menu_room_cal'];
      if (isset($_GET['menu_res_new'])) $this->menu_new_res = $_GET['menu_res_new'];
      if (isset($_POST['submit_new'])) $this->form_submit_new = true;
      if (isset($_GET['auth_code'])) $this->email_auth_code = $_GET['auth_code'];
      if (isset($_POST['submit_edit'])) $this->sv_submit_edit = true;
      
      // PHWReserveForm
      if (isset($_GET['email_auth'])) $this->email_auth = $_GET['email_auth'];
      if (isset($_POST['recurs'])) $this->recurs = true;
      
      
      // PHWReserveReservationRequest email_transient
      if (isset($_GET['email_res_id'])) $this->email_res_id = $_GET['email_res_id'];
      if (isset($_GET['email_transient'])) $this->email_transient = $_GET['email_transient'];
      
      // PHWReserveCalendar
      if (isset($_GET['cal_view_cal'])) $this->cal_view_cal = $_GET['cal_view_cal'];
      if (isset($_GET['cal_room'])) $this->cal_room = $_GET['cal_room'];
      if (isset($_GET['cal_month'])) $this->cal_month = $_GET['cal_month'];
      if (isset($_GET['cal_res_id'])) $this->cal_res_id = $_GET['cal_res_id'];
      if (isset($_GET['cal_auth'])) $this->cal_auth = $_GET['cal_auth'];
      if (isset($_GET['submit_del'])) $this->cal_submit_del = true;
      if (isset($_GET['cal_res_new'])) $this->cal_res_new = true;
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
      if ($this->menu_room_cal || $this->cal_view_cal) {
         $this->handle_cal_request();
      }
      
      // Selected - Reserve a Room
      elseif ($this->menu_new_res || $this->cal_res_new) {
         $this->handle_new_res_request();
      }
      
      // Clicked edit link in email 
      elseif ($this->email_res_id) {
         $this->handle_email_edit_res_request();
      }
      
      // Submitted new reservation form
      elseif ($this->form_submit_new) {
         $this->handle_new_res_submission();
      }
      
      // Clicked authentication code link in email
      elseif ($this->email_auth_code) {
         $this->handle_auth_code_submission();
      }
      
      // Submitted edit/cancel reservation form
      elseif ($this->sv_submit_edit) {
         $this->handle_edit_res_submission();
      }
     
      // Logged in user requested to delete
      elseif ($this->cal_submit_del) {
         $this->handle_del_res_submission();
      }
      
      // Initial Page Load
      else {
         $menu = new PHWReserveMenu($this->rooms);
         $menu->display_menu();
      }
   }
   
   
   /**
   * Handles clicking link to reserve a room
   *
   * Creates new form object and calls its display_form method.
   * @since 1.0
   */
   private function handle_new_res_request() {
      $form = new PHWReserveForm($this->rooms, $this->valid_emails);
      $form->display_form();
   }
  
  
   /**
   * Handles submission of new reservation form
   *
   * Validates user inputs, verifies that requested time doesn't conflict with
   * an existing reservation. Displays form with errors if there are any. If OK
   * and WP user logged in, inserts reservation into table. If OK and not logged
   * in, calls authenticate_user() to make sure they are who they say they are
   *
   * @since 1.0
   * @todo kinda smells...should time-check be moved to validate_inputs?
   * @todo DRY new res submission and edit res submission
   *
   * @todo add recurs to new reservations
   */
   private function handle_new_res_submission() { 
      $form = new PHWReserveForm($this->rooms, $this->valid_emails);
      if ($form->validate_inputs()) {
         $begin_time = strtotime(date('n/j/y', $form->time_date_valid) . ' ' . date('G:i e', $form->time_start_valid));
         $end_time= strtotime(date('n/j/y', $form->time_date_valid) . ' ' . date('G:i e', $form->time_end_valid));
         if ($form->recurs_until_valid)
            $recurs_until = strtotime(date('n/j/y', $form->recurs_until_valid));
         $reservation = new PHWReserveReservationRequest($form->patron_name, $form->patron_email, 
                                 $begin_time, $end_time, $form->reserve_room, $form->patron_purpose,
                                 '', $form->recurs, $recurs_until, $form->recurs_on);
         if ($reservation->check_time_conflict()) {
            $form->hasError = true;
            $form->timeStartError = "{$form->reserve_room} is already reserved during this time.";
            $form->timeEndError = "";
            $form->display_form();
         }
         else {
            if (is_user_logged_in()) {
               $reservation->create_auth_code();
               $reservation->insert_into_db();
            }
            else
               $reservation->authenticate_user();
         }
      }
      else { // form validation error
         $form->display_form();  
      }
   }
   
   
   /**
   * Handles requests to edit/delete reservations
   *
   * Called when change/cancel URL in confirmation email is visited. 
   *
   * @since 1.0
   * @todo how will admin edit/deletes be handled?
   */
   private function handle_email_edit_res_request() {
      $form = new PHWReserveForm($this->rooms, $this->emails);
      if (isset($this->email_res_id)) {
         $reservation = new PHWReserveReservationRequest();
         $res_data = $reservation->get_res_data($this->email_res_id);
         if ($res_data['auth_code'] == $this->email_auth) {
            $form->set_form_fields($res_data['res_id'],
                                   $res_data['patron_name'], 
                                   $res_data['patron_email'],
                                   $res_data['datetime_start'],
                                   $res_data['datetime_end'],
                                   $res_data['purpose'], 
                                   $res_data['room'],
                                   $res_data['auth_code'],
                                   $res_data['recurs'],
                                   $res_data['recurs_until'],
                                   $res_data['recurs_on']);
            $editing = true;
            $form->display_form($editing);
         }
         else {
            echo "ERROR: Authorization code does not match requested reservation. Please contact " 
                 . antispambot(get_option('admin_email')) . " with this error.";
            wp_die();
         }
      }
   }
   
   
   /**
   * Handles selection to View Room Availability
   *
   * Shows the calendar form. If room and month submitted, shows the listing
   * of current reservations.
   * 
   * @since 1.0
   */
   private function handle_cal_request() {
      if ($this->cal_room && $this->cal_month) 
         $submitted = true;
      else
         $submitted = false;

      $calendar = new PHWReserveCalendar($this->rooms);

      if ($submitted) 
         $calendar->set_fields($this->cal_room, $this->cal_month);

      $calendar->show_form();
      if ($submitted) 
         $calendar->show_reservations();
   }
   
   
   /**
   * Handes submission of an auth code from email
   *
   * Also deletes the transient after inserting res into table 
   *
   * @since 1.0
   * @todo Should I load these GET variables beforehand?
   */
   private function handle_auth_code_submission() {
      $transient_name = $this->email_transient;
      $transient_data = get_transient($transient_name);
      $auth_code = $transient_data['auth_code'];
      if ($auth_code == $this->email_auth_code) {
         $reservation = new PHWReserveReservationRequest($transient_data['patron_name'], 
                                                         $transient_data['patron_email'], 
                                                         $transient_data['datetime_start'], 
                                                         $transient_data['datetime_end'], 
                                                         $transient_data['room'], 
                                                         $transient_data['purpose'],
                                                         $transient_data['auth_code']);
         $reservation->insert_into_db();
         delete_transient($transient_name);
      }
      else {
         echo '<p><strong>Error:</strong> Your authorization code does not match this reservation, 
               or your email link has expired. If you belive you have incorrectly received this error, 
               please contact ' . antispambot(get_option('admin_email')) . '</p>';
      }
   }

   
   /**
   * Handles edit/cancel form submission
   * 
   * Checks if user selected to cancel reservation or edit reservation. Quickly 
   * deletes if desired. If editing, validates changes, 
   * checks for time conflicts, and updates table if all is well.
   *
   * @since 1.0
   */
   private function handle_edit_res_submission() {
      $res_id = $_POST['res_id'];
      $reservation = new PHWReserveReservationRequest();
      $res_auth_code = $reservation->get_res_auth_code($res_id);
      if ($_POST['auth'] == $res_auth_code) {
         if (isset($_POST['del_res'])) {           // user wants it cancelled
            $res_data = $reservation->get_res_data($res_id);
            $reservation->set_properties($res_data['res_id'],
                                         $res_data['patron_name'], 
                                         $res_data['patron_email'],
                                         $res_data['datetime_start'],
                                         $res_data['datetime_end'],
                                         $res_data['purpose'], 
                                         $res_data['room'],
                                         $res_data['auth_code'],
                                         $res_data['recurs'],
                                         $res_data['recurs_until'],
                                         $res_data['recurs_on']);
           $reservation->del_res($res_id);
         }
         else {   // user wants to edit res
            $form = new PHWReserveForm($this->rooms, $this->valid_emails);
            if ($form->validate_inputs()) {
               $begin_time = strtotime(date('n/j/y', $form->time_date_valid) . ' ' . date('G:i e', $form->time_start_valid));
               $end_time= strtotime(date('n/j/y', $form->time_date_valid) . ' ' . date('G:i e', $form->time_end_valid));
               $reservation->set_properties($res_id, $form->patron_name, $form->patron_email, 
                                            $begin_time, $end_time, $form->reserve_room,
                                            $form->patron_purpose, $res_auth_code, $form->recurs,
                                            $form->recurs_until, $form->recurs_on);
               if ($reservation->check_time_conflict($res_id)) {
                  $form->hasError = true;
                  $form->timeStartError = "{$form->reserve_room} is already reserved during this time.";
                  $form->timeEndError = "";
                  $form->display_form($editing = true);
               }
               else
               {
                  $reservation->update_into_db();
               }
            }
            else {
               $form->display_form($editing = true);
            }
         }
      }
      else {
            echo "ERROR: Authorization code does not match requested reservation. Please contact " 
                 . antispambot(get_option('admin_email')) . " with this error.";
            wp_die();        
      }
   }
   
   
   /**
   * Handles logged in user's request to delete a reservation
   *
   * Checks that user is logged in and also checks auth code before deleting
   * the reservation.
   *
   * @since 1.0
   */
   private function handle_del_res_submission() {
      $res_id = $this->cal_res_id;
      if (is_user_logged_in()) {
         $reservation = new PHWReserveReservationRequest();
         $res_auth_code = $reservation->get_res_auth_code($res_id);
         if ($this->cal_auth == $res_auth_code) {
            $reservation->del_res($res_id);
         }
         else {
            echo "ERROR: Authorization code does not match requested reservation. Please contact " 
                 . antispambot(get_option('admin_email')) . " with this error.";
            wp_die();                
         }
      }
      else {
         echo 'You are not logged in.';
      }
   }
   
}
