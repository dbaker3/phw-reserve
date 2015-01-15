<?php
/*
   PHWReserveSettings class
   Settings registration, etc. for PHP-Reserve Wordpress plugin
   David Baker, Milligan College 2015
*/

class PHWReserveSettings {
   private $option_name = 'phwreserve_settings';
   private $option_page = 'phwreserve_settings_page';
   
   private $todayshours_option_name = 'todayshours_settings';
   
   private $settings;
   
   function __construct() {
      $this->settings = get_option($this->option_name);
 
      if (!$this->settings) { // create settings if not already - defaults
         add_option($this->option_name);
         
         $this->settings = array(
            'valid_emails' => '',   // accept reservations from these domains
            'rooms'        => '',   // rooms to reserve
            'todays_hours' => true  // option to use data from today's hours plugin
         ) 
         
         $valid_emails = array('milligan.edu',
                               'my.milligan.edu');
                               
         $rooms = array('Group Study Room 1',
                        'Group Study Room 2', 
                        'Group Study Room 3', 
                        'Group Study Room 4',
                        'Hopwood Room', 
                        'Welshimer Room');
         
         $this->settings['valid_emails'] = json_encode($valid_emails);
         $this->settings['rooms'] = json_encode($rooms);
         
         $this->save_settings();
      }
      
      if (is_admin()) {
         add_action('admin_menu', array($this, 'register_phwreserve_settings_page'));
         add_action('admin_init', array($this, 'register_phwreserve_settings'));
      }
      
   }
   
   public function register_phwreserve_settings_page() {
      add_options_page(
         'Room Reservation',
         'Room Reservation',
         'administrator',
         'phwreserve_settings_page',
         array($this, 'phwreserve_settings_page_callback')
      );
   }

   public function register_phwreserve_settings() {
      
      add_settings_section(
         'phwreserve_main_section',
         'Room Reservation Settings',
         array($this, 'phsreserve_main_section_callback'),
         $this->option_page
      );
      
      add_settings_field(
         'valid_emails',
         'Domains to accept email from',
         array($this, 'phwreserve_valid_emails_callback'),
         $this->option_page,
         'phwreserve_main_section'
      );
      
      add_settings_field(
         'rooms',
         'Rooms to reserve',
         array($this, 'phwreserve_rooms_callback'),
         $this->option_page,
         'phwreserve_main_section'
      );
      
      add_settings_field(
         'todays_hours',
         'Use Todays Hours Plugin Data'
         array($this, 'phwreserve_todays_hours_callback'),
         $this->option_page,
         'phwreserve_main_section'
      );
    
      register_setting($this->option_page, $this->option_name, array($this, 'phwreserve_sanitize_callback'));
   }

   public function phwreserve_settings_page_callback() { ?>
      <div class="wrap">
         <div id="icon-tools" class="icon32">&nbsp;</div>
         <h2>Room Reservation</h2>
         
         <form method="post" action="options.php">
            <?php settings_fields($this->option_page);?>
            <?php do_settings_sections($this->option_page);?>
            <?php submit_button(); ?> 
            
            <?php wp_enqueue_style('todayshoursettingsstyle', plugins_url('../css/todaysHoursSettings.css', __FILE__)); ?>
            <?php wp_enqueue_script('todayshourssettings', plugins_url('../js/todaysHoursSettings.js', __FILE__), array('jquery'), '1.0', true); ?>
            <?php wp_enqueue_script('jquerytimepicker', plugins_url('../timepicker/jquery.ui.timepicker.js', __FILE__), array('jquery'), '0.3.3', true); ?>
            <?php wp_enqueue_script('jquery-ui-datepicker');?>
            
         
         </form>
         
      </div>
      
   <?php
   }
   
   public function save_settings() {
      update_option($this->option_name, $this->settings);
   }
}