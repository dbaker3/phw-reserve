<?php
/*
   PHWReserveForm class
   David Baker, Milligan College 2015
*/

class PHWReserveForm {
   private $rooms;
   private $valid_emails;

   // Session variables
   private $laylah;
   public $patron_name;
   public $patron_email;
   private $time_date;
   private $time_start;
   private $time_end;
   public $patron_purpose;
   public $reserve_room;
   private $patron_message;
   
   public $time_date_valid;
   public $time_start_valid;
   public $time_end_valid;

   public $hasError;
   
   // Error messages
   private $honeypotError;
   private $nameError;
   private $emailError;
   public $dateError;
   public $timeStartError;
   public $timeEndError;
   private $purposeError;
   private $roomError;

   function __construct($rooms, $emails) {
      $this->load_session_vars();
      $this->rooms = $rooms;
      $this->valid_emails = $emails;
   }
   
   private function load_session_vars() {
      $this->laylah = trim($_POST['laylah']);
      $this->patron_name = trim($_POST['patron_name']);
   	$this->patron_email = strtolower(trim($_POST['patron_email']));
   	$this->time_date = trim($_POST['time_date']);
    	$this->time_start = trim($_POST['time_start']);
   	$this->time_end = trim($_POST['time_end']);
   	$this->patron_purpose = trim($_POST['patron_purpose']);
   	$this->reserve_room = trim($_POST['reserve_room']);
   	$this->patron_message = trim($_POST['patron_message']);
   	$this->time_date_valid = strtotime($this->time_date);
     	$this->time_start_valid = strtotime($this->time_start); 
   	$this->time_end_valid = strtotime($this->time_end);
   }

   public function validate_inputs() {
      if(isset($this->laylah)) {
     		$honeypotError = 'You may not be human, please try again.';
      	$hasError = true;
   	}

   	if($this->patron_name === '') {
   		$this->nameError = 'You must enter your name.';
   		$this->hasError = true;
   	}

   	if($this->patron_email === '') {
   		$this->emailError = 'You must enter your email address.';
   		$this->hasError = true;
   	} elseif (!in_array(substr($this->patron_email, strrpos($this->patron_email, '@') + 1), $this->valid_emails)) {
         $this->emailError = 'The email address you entered is not authorized to request a reservation.';
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
   	} else if ($this->time_end_valid - $this->time_start_valid > 14400 && $this->time_start_valid !== false) {  // TODO: Option for time block length
   		$this->timeEndError = 'You may not reserve a room for more than four hours at a time.';
   		$this->hasError = true;
   	}
   
   	if($this->patron_purpose === '') {
   		$this->purposeError = 'Please specify why you wish to use the room.';
   		$this->hasError = true;
   	}
   
   	if($this->reserve_room === '') {
   		$this->roomError = "You must select a room.";
   		$this->hasError = true;
   	}
   
     	if(!isset($this->hasError)) {
         return true;
		}
      else
         return false;
   } 
   
   public function display_form() { ?>
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
			</div>
		<?php endif; ?>
         
      <form action="<?php the_permalink(); ?>" method="post">
			<p class="form"><label class="label" for="patron_name">Name:* </label><input tabindex="1" class="text three-fourths<?php if(isset($this->nameError)){echo ' fail';}?>" type="text" id="patron_name" name="patron_name" value="<?php if(isset($this->patron_name)){echo $this->patron_name;} ?>"/></p>
			<p class="form"><label class="label" for="patron_email">Email:* </label><input tabindex="2" class="text three-fourths<?php if(isset($this->emailError)){echo ' fail';}?>" type="email" id="patron_email" name="patron_email" value="<?php if(isset($this->patron_email)){echo $this->patron_email;} ?>" /></p>
			<p class="form"><label class="label" for="time_date">Date:* </label><input tabindex="3" class="text half<?php if(isset($this->dateError)){echo ' fail';}?>" type="date" id="time_date" name="time_date" value="<?php if($this->time_date_valid){echo date('n/j/Y', $this->time_date_valid);} ?>" /></p>
			<p class="form"><label class="label" for="time_start">Start Time:* </label><input tabindex="4" class="text half<?php if(isset($this->timeStartError)){echo ' fail';}?>" type="text" id="time_start" name="time_start" value="<?php if($this->time_start_valid){echo date('g:i A', $this->time_start_valid);} ?>" /></p>
			<p class="form"><label class="label" for="time_end">End Time:* </label><input tabindex="5" class="text half<?php if(isset($this->timeEndError)){echo ' fail';}?>" type="text" id="time_end" name="time_end" value="<?php if($this->time_end_valid){echo date('g:i A', $this->time_end_valid);} ?>" /></p>
			<p class="form"><label class="label" for="patron_purpose">Purpose:* </label><input tabindex="6" class="text<?php if(isset($this->purposeError)){echo ' fail';}?>" type="text" id="patron_purpose" name="patron_purpose" value="<?php if(isset($this->patron_purpose)){echo $this->patron_purpose;} ?>" /></p>
			<p class="laylah"><label for="laylah">Required:*</label><input type="text" id="laylah" name="laylah" tabindex="999" /></p>
			<p class="form"><label class="label" for="reserve_room">Room:* </label><select tabindex="7" class="text half<?php if(isset($this->roomError)){echo ' fail';}?>" id="reserve_room" name="reserve_room" >
				<option value=''>Select ... </option>
            <?php foreach ($this->rooms as $room) { ?>
             <option value="<?php echo $room; ?>"<?php if ($this->reserve_room == $room) echo ' selected="selected"'; ?>><?php echo $room?></option>
            <?php } ?>
			</select>
			</p>
			<p class="form"><label class="label" for="patron_message">Special Instructions: </label><textarea tabindex="10" class="textarea" id="patron_message" name="patron_message" ><?php if(isset($this->patron_message)){echo $this->patron_message;} ?></textarea></p>
			<p class="form"><input class="submit full" type="submit" name="submit_new" value="Send Request" tabindex="11" ></p>
		</form> <?php 
   }
   
}