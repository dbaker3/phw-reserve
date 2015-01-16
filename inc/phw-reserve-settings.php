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
         ); 
         
         $valid_emails = "milligan.edu\n" .
                         "my.milligan.edu";
                               
         $rooms = "Group Study Room 1\n" .
                  "Group Study Room 2\n" .
                  "Group Study Room 3\n" .
                  "Group Study Room 4\n" .
                  "Hopwood Room\n" .
                  "Welshimer Room";
         
         $this->settings['valid_emails'] = $valid_emails;
         $this->settings['rooms'] = $rooms;
         
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
         'Settings',
         array($this, 'phwreserve_main_section_callback'),
         $this->option_page
      );
      
      add_settings_field(
         'valid_emails',
         'Valid Email Domains',
         array($this, 'phwreserve_valid_emails_callback'),
         $this->option_page,
         'phwreserve_main_section'
      );
      
      add_settings_field(
         'rooms',
         'Room List',
         array($this, 'phwreserve_rooms_callback'),
         $this->option_page,
         'phwreserve_main_section'
      );
      
      add_settings_field(
         'todays_hours',
         "Today&#39;s Hours Data",
         array($this, 'phwreserve_todays_hours_callback'),
         $this->option_page,
         'phwreserve_main_section'
      );
    
      register_setting($this->option_page, $this->option_name, array($this, 'phwreserve_sanitize_callback'));
   }

   public function phwreserve_main_section_callback($args) {}
   
   public function phwreserve_valid_emails_callback($args) {
      $valid_emails = explode("\n", $this->settings['valid_emails']);
      
      $html = "<p>We will accept reservations only from the following email domains.<br>Enter one domain per line, i.e. <strong>gmail.com</strong></p>";     
      $html .= "<p><textarea name='phwreserve_settings[valid_emails]' id='valid_emails' rows='8' cols='50' spellcheck='false'>";
      foreach ($valid_emails as $email) {
         $html .= "{$email}\n";
      }
      $html = rtrim($html);
      $html .= "</textarea></p>";
      echo $html;
   }
   
   public function phwreserve_rooms_callback($args) {
      $rooms = explode("\n", $this->settings['rooms']);

      $html = "<p>This list contains all rooms available for reservation.<br>Enter one room name per line.</p>";
      $html .= "<p><textarea name='phwreserve_settings[rooms]' id='rooms' rows='8' cols='50' spellcheck='false'>";
      foreach ($rooms as $room) {
         $html .= "{$room}\n";
      }
      $html = rtrim($html);
      $html .= "</textarea></p>";
      echo $html;
   }
   
   public function phwreserve_todays_hours_callback($args) {
      $html = "<input type='checkbox' name='phwreserve_settings[todays_hours]' id='todays_hours' " . ($this->settings['todays_hours'] ? 'checked' : '') . "><label for='todays_hours'>Use business hour data from our Today&#39;s Hours plugin</label>";
      echo $html;
   }
   
   public function phwreserve_settings_page_callback() { ?>
      <div class="wrap">
         <div id="icon-tools" class="icon32">&nbsp;</div>
         <h2>Room Reservation</h2>
         <form method="post" action="options.php">
            <?php settings_fields($this->option_page);?>
            <?php do_settings_sections($this->option_page);?>
            <?php submit_button();?>
         </form>
      </div>
   <?php
   }
   
   public function phwreserve_sanitize_callback($input) {
      $valid_emails = $input['valid_emails'];
      $rooms = $input['rooms'];
      
      // remove lonely whitespace
      $valid_emails = rtrim(preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $valid_emails));
      $rooms = rtrim(preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $rooms));
      
      // TODO: sanitize/validate
      
      //$input['valid_emails'] = sanitize_text_field($valid_emails);
      //$input['rooms'] =  sanitize_text_field($rooms);
      
      return $input;
   }
   
   public function save_settings() {
      update_option($this->option_name, $this->settings);
   }
}