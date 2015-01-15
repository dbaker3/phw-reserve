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
   public $sv_phw_room_cal = false;
   public $sv_phw_new_res = false;
   public $sv_phw_edit_res = false;

   function __construct() {
      
   }
   
   function init() {
      $this->load_plugin_settings();
      $this->load_session_vars();
      $this->handle_page_request();
   }
   
   function load_plugin_settings() {
      $settings = get_option($this->option_name);
      $this->rooms = json_decode($settings['rooms']);
      $this->valid_emails = json_decode($settings['valid_emails']);
   }
   
   function load_session_vars() {
      if (isset($_POST['phw_room_cal']) $this->sv_phw_room_cal = $_POST['phw_room_cal'];
      if (isset($_POST['phw_new_res']) $this->sv_phw_new_res = $_POST['phw_new_res'];
      if (isset($_POST['phw_edit_res']) $this->sv_phw_edit_res = $_POST['phw_edit_res'];
   }
   
   function handle_page_request() {
      if ($this->sv_phw_room_cal) {
         $this->call_res_calendar();
      }
      elseif ($this->sv_phw_new_res) {
         $this->call_res_form('new');
      }
      elseif ($this->sv_phw_edit_res) {
         $this->call_res_form('edit');     
      }
      else {
         $this->call_res_menu();
      }
   }

   function call_res_menu() {
      $menu = new PHWReserveMenu($this->rooms);
   }
   
   function call_res_form($type) {
      $form = new PHWReserveForm($type);
   }
   
   function call_res_calendar() {
      $calendar = new $PHWReserveCalendar($this->sv_phw_room_cal);
   }
   
}



	<?php if(isset($emailSent) && $emailSent == true): ?>

		<div class="alert success">
			<p>Your request has been successfully submitted. You will be notified via email when your reservation has been confirmed.</p>
			<p><a class="submit full"href="<?php the_permalink() ?>">Submit Another Request</a>
			</div>

	<?php else: ?>
		<?php if(isset($hasError)): ?>
		<div class="alert fail">
			<?php if(isset($honeypotError)){echo $honeypotError . '<br />';}?>
			<?php if(isset($nameError)){echo $nameError . '<br />';}?>
			<?php if(isset($emailError)){echo $emailError . '<br />';}?>
			<?php if(isset($dateError)){echo $dateError . '<br />';}?>
			<?php if(isset($timeStartError)){echo $timeStartError . '<br />';}?>
			<?php if(isset($timeEndError)){echo $timeEndError . '<br />';}?>
			<?php if(isset($purposeError)){echo $purposeError . '<br />';}?>
			<?php if(isset($roomError)){echo $roomError . '<br />';}?>
		</div>

		<?php endif; ?>
                           
                           
                           
		<!--Group Room Reservation Form -->
		<form action="<?php the_permalink(); ?>" method="post">
			<p class="form"><label class="label" for="patron_name">Name:* </label><input tabindex="1" class="text three-fourths<?php if(isset($nameError)){echo ' fail';}?>" type="text" id="patron_name" name="patron_name" value="<?php if(isset($patron_name)){echo $patron_name;} ?>"/></p>
			<p class="form"><label class="label" for="patron_email">Milligan Email:* </label><input tabindex="2" class="text three-fourths<?php if(isset($emailError)){echo ' fail';}?>" type="email" id="patron_email" name="patron_email" value="<?php if(isset($patron_email)){echo $patron_email;} ?>" /></p>
			<p class="form"><label class="label" for="time_date">Date:* </label><input tabindex="3" class="text half<?php if(isset($dateError)){echo ' fail';}?>" type="date" id="time_date" name="time_date" value="<?php if($time_date_valid){date('j/n/Y', $time_date_valid);} ?>" /></p>
			<p class="form"><label class="label" for="time_start">Start Time:* </label><input tabindex="4" class="text half<?php if(isset($timeStartError)){echo ' fail';}?>" type="text" id="time_start" name="time_start" value="<?php if($time_start_valid){echo date('g:i A', $time_start_valid);} ?>" /></p>
			<p class="form"><label class="label" for="time_end">End Time:* </label><input tabindex="5" class="text half<?php if(isset($timeEndError)){echo ' fail';}?>" type="text" id="time_end" name="time_end" value="<?php if($time_end_valid){echo date('g:i A', $time_end_valid);} ?>" /></p>
			<p class="form"><label class="label" for="patron_purpose">Purpose:* </label><input tabindex="6" class="text<?php if(isset($purposeError)){echo ' fail';}?>" type="text" id="patron_purpose" name="patron_purpose" value="<?php if(isset($patron_purpose)){echo $patron_purpose;} ?>" /></p>
			<p class="laylah"><label for="laylah">Required:*</label><input type="text" id="laylah" name="laylah" tabindex="999" /></p>
			<p class="form"><label class="label" for="reserve_room">Room:* </label><select tabindex="7" class="text half<?php if(isset($roomError)){echo ' fail';}?>" id="reserve_room" name="reserve_room" >
				<option value="">Select ... </option>
				<option value="welshimer-room" <?php if($reserve_room === 'alum'){echo 'selected="selected"';} ?> >Welshimer Room</option>
				<option value="hopwood-room" <?php if($reserve_room === 'hopwood'){echo 'selected="selected"';} ?> >Hopwood Room</option>
				<option value="group-room-1" <?php if($reserve_room === 'group-room-1'){echo 'selected="selected"';} ?> >Group Room 1</option>
				<option value="group-room-2" <?php if($reserve_room === 'group-room-2'){echo 'selected="selected"';} ?> >Group Room 2</option>
				<option value="group-room-3" <?php if($reserve_room === 'group-room-3'){echo 'selected="selected"';} ?> >Group Room 3</option>
				<option value="group-room-4" <?php if($reserve_room === 'group-room-4'){echo 'selected="selected"';} ?> >Group Room 4</option>
				<option value="other" <?php if($reserve_room === 'other'){echo 'selected="selected"';} ?> >Other (Please specify below)</option>
			</select>
			</p>
			<p class="form"><label class="label" for="patron_message">Special Instructions: </label><textarea tabindex="10" class="textarea" id="patron_message" name="patron_message" ><?php if(isset($patron_message)){echo $patron_message;} ?></textarea></p>
			<p class="form"><input class="submit full" type="submit" name="submit" value="Send Request" tabindex="11" ></p>
		</form>
	<?php endif; ?>

