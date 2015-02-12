jQuery(document).ready(function() {
   jQuery('#time_start').timepicker();
   jQuery('#time_end').timepicker();
   
   jQuery('#recurs').click(function() {
      jQuery('#recur-opts').toggleClass('recur-hidden');
   });
   
   if (jQuery('#recurs').is(':checked')) {
      jQuery('#recur-opts').removeClass('recur-hidden');
   }
});
