/*
* PHW Reserve
* Applies timepicker plugin to form fields.
* Hides option for recurring days until recurring option checked.
* Adds confirmation listener to delete buttons.
* 
* @author David Baker
* @copyright 2015 Milligan College
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU Public License v2
*/

jQuery(document).ready(function() {
   
   // Add Timepicker to form inputs
   jQuery('#time_start').timepicker({ 'minTime': '8:00am',
                                      'maxTime': '11:30pm',
                                      'timeFormat': 'g:i A'});
   jQuery('#time_end').timepicker({ 'minTime': '8:00am',
                                    'maxTime': '11:59pm',
                                    'timeFormat': 'g:i A'});
  
   // Show days when recurring option selected on form
   jQuery('#recurs').click(function() {
      jQuery('#recur-opts').toggleClass('recur-hidden');
   });
   if (jQuery('#recurs').is(':checked')) {
      jQuery('#recur-opts').removeClass('recur-hidden');
   }
  
   // Get confirmation on deleting reservations from calendar 
   jQuery('.res-del-series').on('click', function() {
      return confirm("Are you sure you want to delete this ENTIRE SERIES of recurring reservations?");
   });
   jQuery('.res-del-occur').on('click', function() {
      return confirm("Are you sure you want to delete this SINGLE INSTANCE in the series of recurring reservations?");
   });
   jQuery('.res-del-single').on('click',function() {
      return confirm("Are you sure you want to delete this reservation?");
   });
   
});
