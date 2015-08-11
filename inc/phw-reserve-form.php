<?php
/**
* Contains the PHWReserveForm class
*
* @author David Baker
* @copyright 2015 Milligan College
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU Public License v2
* @since 1.0
*
*/


/**
* Reservation Request Form
*
* @since 1.0
*/
class PHWReserveForm {
   private $rooms;
   private $valid_emails;

   // GET & POST variables
   private $laylah;
   public $patron_name;
   public $patron_email;
   private $time_date;
   private $time_start;
   private $time_end;
   public $patron_purpose;
   public $reserve_room;
   private $patron_message;
   public $recurs;
   public $recurs_until;
   public $recurs_on = array();
   
   public $time_date_valid;
   public $time_start_valid;
   public $time_end_valid;
   public $recurs_until_valid;

   public $hasError;
   
   private $honeypotError;
   private $nameError;
   private $emailError;
   public $dateError;
   public $timeStartError;
   public $timeEndError;
   private $purposeError;
   private $roomError;

   // Plugin setting
   private $max_res_len;

   /**
   * Loads GET & POST variables, available rooms, and valid emails
   *
   * @param mixed $rooms Available rooms configured in plugin settings
   * @param mixed $emails Valid email domains configured in plugin settings
   * @since 1.0
   */
   function __construct($rooms, $emails, $selected_datetime = null, $selected_room = null) {
      $this->load_get_post_vars();
      $this->rooms = $rooms;
      $this->valid_emails = $emails;
      if ($selected_room != null) {
         $this->reserve_room = $selected_room;
      }
      if ($selected_datetime != null) {
         $this->time_date_valid = $selected_datetime;
      }
      $this->load_plugin_settings();
   }
   
   
   /**
   * Loads plugin settings
   * @since 1.0
   */
   private function load_plugin_settings() {
      $option_name = PHWReserveSettings::get_option_name();
      $settings = get_option($option_name);
      $this->max_res_len = $settings['max_res_len'];
   }

   
   /**
   * Loads GET & POST variables into object properties
   *
   * Checks if the 'Make reservation' link was clicked on the View Room Availability
   * page. converts entered dates & times to UNIX Timestamps.
   *
   * @since 1.0
   */
   private function load_get_post_vars() {
      // POSTed from form
      $this->laylah = trim($_POST['laylah']);
      $this->patron_name = trim($_POST['patron_name']);
   	$this->patron_email = strtolower(trim($_POST['patron_email']));
   	$this->time_date = trim($_POST['time_date']);
    	$this->time_start = trim($_POST['time_start']);
   	$this->time_end = trim($_POST['time_end']);
    	if ($this->time_end == "12:00 AM") {$this->time_end = "11:59 PM";}
   	$this->patron_purpose = trim($_POST['patron_purpose']);
   	$this->reserve_room = trim($_POST['reserve_room']);
   	$this->patron_message = trim($_POST['patron_message']);
   	$this->time_date_valid = strtotime($this->time_date);
     	$this->time_start_valid = strtotime($this->time_start); 
   	$this->time_end_valid = strtotime($this->time_end);
      if (isset($_POST['recurs'])) $this->recurs = true;
      $this->recurs_until = trim($_POST['recurs_until']);
      $this->recurs_until_valid = strtotime($this->recurs_until);
      if (isset($_POST['recurs_sun'])) $this->recurs_on['sun'] = true;
      if (isset($_POST['recurs_mon'])) $this->recurs_on['mon'] = true;
      if (isset($_POST['recurs_tue'])) $this->recurs_on['tue'] = true;
      if (isset($_POST['recurs_wed'])) $this->recurs_on['wed'] = true;
      if (isset($_POST['recurs_thu'])) $this->recurs_on['thu'] = true;
      if (isset($_POST['recurs_fri'])) $this->recurs_on['fri'] = true;
      if (isset($_POST['recurs_sat'])) $this->recurs_on['sat'] = true;
      // Passed from calendar via 'make reservation' link. Already timestamp.
    /*  if (isset($_GET['time_date'])) {
         $this->time_date_valid = $_GET['time_date'];
      } // Passed from calendar via 'make reservation' link.
      if (isset($_GET['reserve_room'])) {
         $this->reserve_room = $_GET['reserve_room'];
      } */
   }

   
   /**
   * Validates the form inputs
   *
   * Sets error variables if it runs into problems. 
   *
   * @return boolean True if validates without error
   * @since 1.0
   */
   public function validate_inputs() {
      if($this->laylah != '') {
     		$this->honeypotError = 'You may not be human, please try again. Do not enter a value into the field labeled <em>Required*</em>.';
      	$this->hasError = true;
   	}

   	if($this->patron_name === '') {
   		$this->nameError = 'You must enter your name.';
   		$this->hasError = true;
   	}

   	if($this->patron_email === '') {
   		$this->emailError = 'You must enter your email address.';
   		$this->hasError = true;
   	} elseif (!in_array(substr($this->patron_email, strrpos($this->patron_email, '@') + 1), $this->valid_emails)) {
         $this->emailError = 'Your email address must end in one of the following:';
         foreach ($this->valid_emails as $valid_email) {
            $this->emailError .=  " " . $valid_email;
         }
         $this->hasError = true;
      } elseif (!is_email($this->patron_email)) {
         $this->emailError = 'You must enter a valid email address.';
         $this->hasError = true;
      }
   
   	if($this->time_date === '') {
   		$this->dateError = 'You must enter a date.';
   		$this->hasError = true;
   	} else if ($this->time_date_valid === false){
   		$this->dateError = 'You must enter a valid date (e.g. 11/4/2012).';
   		$this->hasError = true;
   	} else if ($this->time_date_valid < strtotime('today')) {
   		$this->dateError = 'Please pick a date in the future.';
   		$this->hasError = true;
   	}
   

   	if($this->time_start === '') {
   		$this->timeStartError = 'You must enter a start time.';
   		$this->hasError = true;
   	} else if ($this->time_start_valid === false) {
   		$this->timeStartError = 'You must enter a valid start time (e.g. 7:30 PM).';
   		$this->hasError = true;
   	}
   
   	if($this->time_end === '') {
   		$this->timeEndError = 'You must enter an end time.';
   		$this->hasError = true;
   	} else if ($this->time_end_valid === false) {
   		$this->timeEndError = 'You must enter a valid end time (e.g. 10:45 PM).';
   		$this->hasError = true;
   	} else if ($this->time_end_valid <= $this->time_start_valid) {    // TODO: Fix ending at midnight
   		$this->timeEndError = 'The end time must be later than the start time.';
   		$this->hasError = true;
   	} else if ($this->time_end_valid - $this->time_start_valid > ($this->max_res_len * HOUR_IN_SECONDS) && $this->time_start_valid !== false) {  
         if (!is_user_logged_in()) {
      		$this->timeEndError = "You may not reserve a room for more than {$this->max_res_len} hours at a time.";
            $this->hasError = true;
         }
   	}
   
   	if($this->patron_purpose === '') {
   		$this->purposeError = 'Please specify why you wish to use the room.';
   		$this->hasError = true;
   	}
   
   	if($this->reserve_room === '') {
   		$this->roomError = "You must select a room.";
   		$this->hasError = true;
   	}
       
      if ($this->recurs && (count($this->recurs_on) < 1)) {
         $this->recurs_on_error = "Specify which day(s) of the week the reservation will recur on";
         $this->hasError = true;
      }

      if ($this->recurs) {
         if ($this->recurs_until === '') {
            $this->recurs_until_error = "You must enter a date for recurring reservations to end.";
            $this->hasError = true;
         }
         else if ($this->recurs_until_valid === false) {
            $this->recurs_until_error = "You must enter a valid recurs until date (e.g. 11/4/2012).";
            $this->hasError = true;
         }
         else if ($this->recurs_until_valid < $this->time_date_valid) {
            $this->recurs_until_error = "The recurs until date must be later than the start date.";
            $this->hasError = true;
         }
      }
   
     	if(!isset($this->hasError)) {
         return true;
		}
      else
         return false;
   } 
   
   
   /**
   * Echos HTML for reservation request form
   *
   * Displays the reservation request form. The same form is used for new requests and 
   * edits of existing reservations. Displays errors if found by validate_inputs()
   * method. Populates fields with any POSTed data.
   *
   * If editing, a checkbox to cancel reservation is included, as well as 2 hidden
   * fields containing the auth_code and res_id. Submit name changes to 'submit_edit'
   * for PHWReservePageController to identify submission was an edit.
   *
   * If loaded from calendar via 'make reservation' link,
   * populates the user's selected date.
   *
   * @param boolean $editing True if user is editing existing reservation. Defaults to false
   * @return void
   * 
   * @since 1.0
   *
   */
   public function display_form($editing = false) { ?>
      <div class="welshimer-form">
		<?php if(isset($this->hasError)): ?>
			<div class="alert fail">
				<?php if(isset($this->honeypotError)){echo $this->honeypotError . '<br />';}?>
				<?php if(isset($this->nameError)){echo $this->nameError . '<br />';}?>
				<?php if(isset($this->emailError)){echo $this->emailError . '<br />';}?>
				<?php if(isset($this->dateError)){echo $this->dateError . '<br />';}?>
				<?php if(isset($this->timeStartError)){echo $this->timeStartError . '<br />';}?>
				<?php if(isset($this->timeEndError)){echo $this->timeEndError . '<br />';}?>
				<?php if(isset($this->purposeError)){echo $this->purposeError . '<br />';}?>
				<?php if(isset($this->roomError)){echo $this->roomError . '<br />';}?>
            <?php if(isset($this->recurs_on_error)) {echo $this->recurs_on_error . '<br />';}?>
            <?php if(isset($this->recurs_until_error)) {echo $this->recurs_until_error . '<br />';}?>
			</div>
		<?php endif; ?>
         
      <form action="<?php the_permalink(); ?>" method="post">
			<p class="form"><label class="label" for="patron_name">Name:* </label><input tabindex="1" class="text three-fourths<?php if(isset($this->nameError)){echo ' fail';}?>" type="text" id="patron_name" name="patron_name" value="<?php if(isset($this->patron_name)){echo $this->patron_name;} ?>" <?php if($editing){echo 'readonly';} ?>/></p>
			<p class="form"><label class="label" for="patron_email">Email:* </label><input tabindex="2" class="text three-fourths<?php if(isset($this->emailError)){echo ' fail';}?>" type="email" id="patron_email" name="patron_email" value="<?php if(isset($this->patron_email)){echo $this->patron_email;} ?>" <?php if($editing){echo 'readonly';} ?>/></p>
			<p class="form"><label class="label" for="time_date">Date:* </label><input tabindex="3" class="text half<?php if(isset($this->dateError)){echo ' fail';}?>" type="text" id="time_date" name="time_date" value="<?php if($this->time_date_valid){echo date('n/j/Y', $this->time_date_valid);} ?>" /></p>
			<p class="form"><label class="label" for="time_start">Start Time:* </label><input tabindex="4" class="text half<?php if(isset($this->timeStartError)){echo ' fail';}?>" type="text" id="time_start" name="time_start" value="<?php if($this->time_start_valid){echo date('g:i A', $this->time_start_valid);} ?>" /></p>
			<p class="form"><label class="label" for="time_end">End Time:* </label><input tabindex="5" class="text half<?php if(isset($this->timeEndError)){echo ' fail';}?>" type="text" id="time_end" name="time_end" value="<?php if($this->time_end_valid){echo date('g:i A', $this->time_end_valid);} ?>" /></p>

			<p class="form"><label class="label" for="patron_purpose">Purpose:* </label><input tabindex="6" class="text<?php if(isset($this->purposeError)){echo ' fail';}?>" type="text" id="patron_purpose" name="patron_purpose" value="<?php if(isset($this->patron_purpose)){echo $this->patron_purpose;} ?>" /></p>
			<p class="laylah"><label for="laylah">Required:*</label><input type="text" id="laylah" name="laylah" tabindex="999" /></p>
			<p class="form"><label class="label" for="reserve_room">Room:* </label><select tabindex="7" class="text three-fourths<?php if(isset($this->roomError)){echo ' fail';}?>" id="reserve_room" name="reserve_room" >
				<option value=''>Select ... </option>
            <?php foreach ($this->rooms as $room) { 
               if (!preg_match("/<\/?optgroup[^<]*/", $room)) { 
                  echo "<option";
                  if ($room == $this->reserve_room) {echo " selected";};
                     echo ">{$room}</option>";
               } 
               else echo $room; // "<optgroup label='Branch Location'>" and "</optgroup>"  
            } ?>
			</select>
			</p>
			<p class="form">
         <?php 
         if (is_user_logged_in()) { ?>
            <p class="form"><label class="label" for="recurs">Recurring: </label><input type="checkbox" id="recurs" name="recurs" <?php if ($this->recurs) echo 'checked' ?>></p>
            <div id="recur-opts" class="recur-hidden">
            <p class="form"><label class="label" for="recurs_every">Recurs Every:*</label><input type="checkbox" id="recurs_sun" name="recurs_sun" <?php if (array_key_exists('sun',$this->recurs_on)) echo 'checked' ?>><label for="recurs_sun">Sun</label>
                                                                               <input type="checkbox" id="recurs_mon" name="recurs_mon" <?php if (array_key_exists('mon',$this->recurs_on)) echo 'checked'?>><label for="recurs_mon">Mon</label>
                                                                               <input type="checkbox" id="recurs_tue" name="recurs_tue" <?php if (array_key_exists('tue',$this->recurs_on)) echo 'checked' ?>><label for="recurs_tue">Tue</label>                                                                                      
                                                                               <input type="checkbox" id="recurs_wed" name="recurs_wed" <?php if (array_key_exists('wed',$this->recurs_on)) echo 'checked' ?>><label for="recurs_wed">Wed</label>
                                                                               <input type="checkbox" id="recurs_thu" name="recurs_thu" <?php if (array_key_exists('thu',$this->recurs_on)) echo 'checked' ?>><label for="recurs_thu">Thu</label>
                                                                               <input type="checkbox" id="recurs_fri" name="recurs_fri" <?php if (array_key_exists('fri',$this->recurs_on)) echo 'checked' ?>><label for="recurs_fri">Fri</label>
                                                                               <input type="checkbox" id="recurs_sat" name="recurs_sat" <?php if (array_key_exists('sat',$this->recurs_on)) echo 'checked' ?>><label for="recurs_sat">Sat</label></p>
            <p class="form"><label class="label" for="recurs_until">Recurs until:* </label><input type="text" class="text half <?php if(isset($this->recurs_until_error)){echo ' fail';}?>" id="recurs_until" name="recurs_until" value="<?php if($this->recurs_until_valid){echo date('n/j/Y', $this->recurs_until_valid);} ?>"></p>
            </div><?php
         } 
         if ($editing) { echo "<p class='form'><label class='label' for='del_res'><strong>Cancel Reservation</strong></label><input type='checkbox' id='del_res' name='del_res'></p>"; }
         if ($editing) {
            echo "<input class='submit full' type='submit' name='submit_edit' value='Save Changes' tabindex='11' >"
                 . "<input type='hidden' name='method' value='handle_edit_res_submission'>"
                 . "<input type='hidden' name='auth' value='{$this->auth_code}'>"
                 . "<input type='hidden' name='res_id' value='{$this->res_id}'>";
         }
         else {
            echo "<input class='submit full' type='submit' name='submit_new' value='Send Request' tabindex='11' >" 
                 . "<input type='hidden' name='method' value='handle_new_res_submission'";
         }
         ?>
         </p>
		</form> 
      </div><?php 
   }
 
   
   /**
   * Fills form fields values on reservation request form for an edit
   *
   * Sets the class properties with the given parameters. You should call this
   * method prior to display_form method if using form to edit existing reservation
   * since a user didn't POST these to the form. Used by the 
   * PHWReservePageController::handle_edit_res_request() method
   *
   * @param string $patron_name
   * @param string $patron_email
   * @param int $time_start
   * @param int $time_end
   * @param string $patron_purpose
   * @param string $reserve_room
   *
   * @return void
   * @since 1.0
   */
   public function set_form_fields($res_id, $patron_name, $patron_email, $time_start, 
                                   $time_end, $patron_purpose, $reserve_room, $auth_code,
                                   $recurs, $recurs_until, $recurs_on) {
      $this->res_id = $res_id;
      $this->patron_name = $patron_name;
      $this->patron_email = $patron_email;
      $this->time_date_valid = $time_start;
      $this->time_start_valid = $time_start;
      $this->time_end_valid = $time_end;
      $this->patron_purpose = $patron_purpose;
      $this->reserve_room = $reserve_room;
      $this->auth_code = $auth_code;
      $this->recurs = $recurs;
      $this->recurs_until_valid = $recurs_until;
      $this->recurs_on = json_decode($recurs_on);
   }
}
