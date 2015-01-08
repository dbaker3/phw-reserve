<?php
/*
   PHWReserveSettings class
   Settings registration, etc. for PHP-Reserve Wordpress plugin
   David Baker, Milligan College 2015
*/

class PHWReserveSettings {
   private $option_name = 'phwreserve_settings';
   private $option_page = 'phwreserve_settings_page';
   
   private $settings;
   
   function __construct() {
      $this->settings = get_option($this->option_name);
 
      if (!$this->settings) { // create settings if not already - defaults
         add_option($this->option_name);
         
         $this->settings = array(
            'valid_email'  => '@milligan.edu, @my.milligan.edu',
            'rooms'        => '' // array of rooms
         ) 
         
         
         
      }
      
   }
}