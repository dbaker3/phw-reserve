<?php

class PHWReservePageController {

   function __construct() {
      if (isset($_GET['phw_room']) {
         $room = $_GET['phw_room'];
         $this->display_room_calendar($room);
      }
      elseif (isset($_GET['phw_new_res']) {
         $restype = 'new';
         $this->display_res_form($restype);
      }
      elseif (isset($_GET['phw_edit_res']) {
         $restype = 'edit';
         $this->display_res_form($restype);     
      }
      else {
         $this->display_main_menu();
      }
      
   
   }

   function display_main_menu() {
      $output =  "<div id='phwreserve-mainmenu'>";
      $output .= "   <ul>";
      $output .= "      <li><a href="">View Room Calendar</a></li>";
      $output .= "      <li><a href="">Reserve a Study Room</a></li>";     
      $output .= "      <li><a href="">Edit or delete an existing reservation</a></li>";
      $output .= "   </ul>";
      $output .= "</div>";
      echo $output;
   }
   
   function display_res_form() {}
   
   function display_room_calendar() {}
   
   function validate_inputs() {
      if(trim($_POST['laylah']) !== '') {
     		$honeypotError = 'You may not be human, please try again.';
      	$hasError = true;
   	}

   	$patron_name = trim($_POST['patron_name']);
   	if($patron_name === '') {
   		$nameError = 'You must enter your name.';
   		$hasError = true;
   	}

   	$patron_email = strtolower(trim($_POST['patron_email']));
   	if($patron_email === '') {
   		$emailError = 'You must enter your email address.';
   		$hasError = true;
   	} else if (!eregi("^[a-z0-9._%-]*@.*milligan\.edu$", strtolower(trim($_POST['patron_email'])))) {
   		$emailError = 'You must enter a valid Milligan email address.';
   		$hasError = true;
   	}
   
   	$time_date = trim($_POST['time_date']);
   	$time_date_valid = strtotime($time_date);
   	if($time_date === '') {
   		$dateError = 'You must enter a date.';
   		$hasError = true;
   	} else if ($time_date_valid === false){
   		$dateError = 'You must enter a valid date (e.g. 11/4/2012).';
   		$hasError = true;
   	} else if ($time_date_valid < strtotime('today')) {
   		$dateError = 'Please pick a date in the future. Milligan does not allow time travel on campus.';
   		$hasError = true;
   	}
   
   	$time_start = trim($_POST['time_start']);
   	$time_start_valid = strtotime($time_start);
   	if($time_start === '') {
   		$timeStartError = 'You must enter a start time.';
   		$hasError = true;
   	} else if ($time_start_valid === false) {
   		$timeStartError = 'You must enter a valid start time (e.g. 7:30 PM).';
   		$hasError = true;
   	}
   
   	$time_end = trim($_POST['time_end']);
   	$time_end_valid = strtotime($time_end);
   	if($time_end === '') {
   		$timeEndError = 'You must enter an end time.';
   		$hasError = true;
   	} else if ($time_end_valid === false) {
   		$timeEndError = 'You must enter a valid end time (e.g. 10:45 PM).';
   		$hasError = true;
   	} else if ($time_end_valid <= $time_start_valid) {
   		$timeEndError = 'Your end time must be later than your start time. You are not a time traveler.';
   		$hasError = true;
   	} else if ($time_end_valid - $time_start_valid > 14400 && $time_start_valid !== false) {
   		$timeEndError = 'You may not reserve a room for more than four hours at a time.';
   		$hasError = true;
   	}
   
   	$patron_purpose = trim($_POST['patron_purpose']);
   	if($patron_purpose === '') {
   		$purposeError = 'Please specify why you wish to use the room.';
   		$hasError = true;
   	}
   
   	$reserve_room = trim($_POST['reserve_room']);
   	if($reserve_room === '') {
   		$roomError = "You must select a room. Select 'Other' if you are unsure.";
   		$hasError = true;
   	}
   
   	$patron_message = trim($_POST['patron_message']);
   	if($patron_message === '') {
   		// If empty string
   	}
   } // end function validate_inputs

   
   
} // end class PWHReservePageController











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

