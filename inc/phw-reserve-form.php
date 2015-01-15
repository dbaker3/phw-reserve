<?php
/*
   PHWReserveForm class
   David Baker, Milligan College 2015
*/

class PHWReserveForm {

   function __construct($type) {
      if ($type == 'new')
         echo 'new';
      else
         echo 'edit';
   }

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
   
}