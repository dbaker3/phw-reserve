jQuery(document).ready(function() {
   jQuery('#time_start').timepicker({ 'minTime': '8:00am',
                                      'maxTime': '11:30pm',
                                      'timeFormat': 'g:i A'});
   jQuery('#time_end').timepicker({ 'minTime': '8:00am',
                                    'maxTime': '11:30pm',
                                    'timeFormat': 'g:i A'});
   
   jQuery('#recurs').click(function() {
      jQuery('#recur-opts').toggleClass('recur-hidden');
   });
   
   if (jQuery('#recurs').is(':checked')) {
      jQuery('#recur-opts').removeClass('recur-hidden');
   }
});
