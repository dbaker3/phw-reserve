<?php
/**
* Contains the PHWReservePageController class
*
* @author David Baker
* @copyright 2015 Milligan College
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU Public License v2
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
   private $method;

   
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
   * Sends control to proper class on page load
   *
   * Checks GETs and POSTs to see what the current state of user interaction is.
   * @since 1.0
   * @return void
   */
   private function handle_page_request() {
      $method = isset($_GET['method']) ? $_GET['method'] : (isset($_POST['method']) ? $_POST['method'] : null);
      
      if ($method == 'handle_cal_request') {
         $this->handle_cal_request();
      } 
      elseif ($method == 'handle_new_res_request') {
         $this->handle_new_res_request();
      }
      elseif ($method == 'handle_edit_res_request') {
         $this->handle_edit_res_request();
      }
      elseif ($method == 'handle_new_res_submission') {
         $this->handle_new_res_submission();
      }
      elseif ($method == 'handle_edit_res_submission') {
         $this->handle_edit_res_submission();
      }
      elseif ($method == 'handle_auth_code_submission') {
         $this->handle_auth_code_submission();
      }
      elseif ($method == 'handle_del_res_submission') {
         $this->handle_del_res_submission();
      }
      elseif ($method == 'handle_del_occur_submission') {
         $this->handle_del_occur_submission();
      }
      elseif ($method == null) {
         $menu = new PHWReserveMenu($this->rooms);
         $menu->display_menu();
      }
      else {
         echo "ERROR: Method <strong>{$method}</strong> is not valid. Please contact "
              . antispambot(get_option('admin_email')) . " with this error.";
      }
   }
   
   
   /**
   * Handles clicking link to reserve a room
   *
   * Creates new form object and calls its display_form method. Includes 
   * user's selected datetime and room if called from Calendar.
   * @since 1.0
   */
   private function handle_new_res_request() {
      $cal_selected_date = isset($_GET['date']) ? $_GET['date'] : null;
      $cal_selected_room = isset($_GET['room']) ? $_GET['room'] : null;
      if ($cal_selected_date && $cal_selected_room) {
         $form = new PHWReserveForm($this->rooms,
                                    $this->valid_emails,
                                    $cal_selected_date,
                                    $cal_selected_room);
      }
      else {
         $form = new PHWReserveForm($this->rooms, $this->valid_emails);
      }
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
         elseif ($reservation->is_recurring()) {
            $conflicts = $reservation->check_recur_time_conflict();
            if (!empty($conflicts)) {
               $form->hasError = true;
               $form->timeStartError = "{$form->reserve_room} is already reserved during this time on: " 
                                        .implode(", ", $conflicts);
               $form->timeEndError = "";
               $form->display_form();
            }
         }
         if (!$form->hasError) {
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
   * Called when change/cancel URL in confirmation email is visited, or when
   * logged in user clicks edit button on the Calendar. 
   *
   * @since 1.0
   */
   private function handle_edit_res_request() {
      $form = new PHWReserveForm($this->rooms, $this->emails);
      $res_id = isset($_GET['res_id']) ? $_GET['res_id'] : null;
      if ($res_id) {
         $reservation = new PHWReserveReservationRequest();
         $res_data = $reservation->get_res_data($res_id);
         $received_auth_code = isset($_GET['auth_code']) ? $_GET['auth_code'] : null;
         if ($res_data['auth_code'] == $received_auth_code && $received_auth_code != null) {
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
      $cal_room = isset($_GET['room']) ? $_GET['room'] : null;
      $cal_month = isset($_GET['month']) ? $_GET['month'] : null;
      
      if ($cal_room && $cal_month)
         $submitted = true;
      else
         $submitted = false;

      $calendar = new PHWReserveCalendar($this->rooms);

      if ($submitted) 
         $calendar->set_fields($cal_room, $cal_month);

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
      $transient_name = isset($_GET['transient']) ? $_GET['transient'] : null; 
      $transient_data = get_transient($transient_name);
      $auth_code = $transient_data['auth_code'];
      $received_auth_code = isset($_GET['auth_code']) ? $_GET['auth_code'] : null;
      if ($auth_code == $received_auth_code && $auth_code != null) {
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
               $recurs_until = strtotime(date('n/j/y', $form->recurs_until_valid));
               $reservation->set_properties($res_id, $form->patron_name, $form->patron_email, 
                                            $begin_time, $end_time, $form->reserve_room,
                                            $form->patron_purpose, $res_auth_code, $form->recurs,
                                            $recurs_until, $form->recurs_on);
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
   * @todo +recur $reservation->recurs needs set true if recurs to remove from recur table
   */
   private function handle_del_res_submission() {
      $res_id = isset($_GET['res_id']) ? $_GET['res_id'] : null; 
      if (is_user_logged_in()) {
         $reservation = new PHWReserveReservationRequest();
         $res_auth_code = $reservation->get_res_auth_code($res_id);
         $received_auth_code = isset($_GET['auth_code']) ? $_GET['auth_code'] : null;
         if ($received_auth_code == $res_auth_code && $res_auth_code != null) {
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


   /**
   * Handles logged in user's request to delete single occurence in series
   *
   * Deletes only a single instance of a recurring reservation. Only logged in
   * users can delete
   *
   * @since 1.0
   */
   private function handle_del_occur_submission() {
      $res_id = isset($_GET['res_id']) ? $_GET['res_id'] : null;
      $recur_id = isset($_GET['recur_id']) ? $_GET['recur_id'] : null;
       if (is_user_logged_in()) {
         $reservation = new PHWReserveReservationRequest();
         $res_auth_code = $reservation->get_res_auth_code($res_id);
         $received_auth_code = isset($_GET['auth_code']) ? $_GET['auth_code'] : null;
         if ($received_auth_code == $res_auth_code && $res_auth_code != null) {
            $reservation->del_recur($recur_id);
         }
         else {
            echo "recur_id: {$recur_id}<br>res_id: {$res_id}<br>res_auth_code: {$res_auth_code}<br>received_auth_code: {$received_auth_code}<br>";
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
