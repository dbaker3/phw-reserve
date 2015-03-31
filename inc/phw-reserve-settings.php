<?php
/**
* Constains the PHWReserveSettings class
*
* @author David Baker
* @copyright 2015 Milligan College
* @since 1.0
*/


/**
* Registers settings and displays HTML for settings page
* @since 1.0
*/
class PHWReserveSettings {
   private static $option_name = 'phwreserve_settings';
   private static $option_page = 'phwreserve_settings_page';
   private static $todayshours_option_name = 'todayshours_settings';
   private static $settings;
   
   /**
   * Initializes the PHWReserveSettings object
   * @since 1.0
   */
   public static function init() {
      self::$settings = get_option(self::$option_name);
      if (!self::$settings) { // create settings if not exist
         add_option(self::$option_name);
         self::$settings = array(
            'valid_emails' => '',   // accept reservations from these domains
            'rooms'        => '',   // rooms to reserve
            'todays_hours' => true, // option to use data from today's hours plugin
            'temp_res_len' => 1,    // time (in hours) to keep unconfirmed res requests in transient 
            'max_res_len'  => 4,    // maximum hours allowed for reservation (overridden for logged in users)
            'keep_old_len' => 0,    // days to keep reservations that have already passed (0 = keep forever)
            'email_cust'   => "Your reservation request is complete. If someone is in the room when you " .
                              "arrive, please tell them that you have a reservation and politely ask " .
                              "them to leave. If you are uncomfortable doing this, please ask a staff " .
                              "person to assist you.",  // custom message in confirmation email
            'reply_to'     => get_option('admin_email') // reply-to address for emails
         ); 
         $valid_emails = "milligan.edu\n" .
                         "my.milligan.edu";
         $rooms = "Group Study Room 1\n" .
                  "Group Study Room 2\n" .
                  "Group Study Room 3\n" .
                  "Group Study Room 4\n" .
                  "Hopwood Room\n" .
                  "Welshimer Room";
         self::$settings['valid_emails'] = $valid_emails;
         self::$settings['rooms'] = $rooms;
         self::save_settings();
      }
      if (is_admin()) {
         add_action('admin_menu', array(__CLASS__, 'register_phwreserve_settings_page'));
         add_action('admin_init', array(__CLASS__, 'register_phwreserve_settings'));
      }
   }
   
   
   /**
   * Register settings page
   * @since 1.0
   */
   public function register_phwreserve_settings_page() {
      add_options_page(
         'Room Reservation',
         'Room Reservation',
         'administrator',
         'phwreserve_settings_page',
         array(__CLASS__, 'phwreserve_settings_page_callback')
      );
   }

   
   /**
   * Register individual settings and sections
   * @since 1.0
   */
   public function register_phwreserve_settings() {
      
      add_settings_section(
         'phwreserve_main_section',
         'Settings',
         array(__CLASS__, 'phwreserve_main_section_callback'),
         self::$option_page
      );
      
      add_settings_field(
         'valid_emails',
         'Valid Email Domains',
         array(__CLASS__, 'phwreserve_valid_emails_callback'),
         self::$option_page,
         'phwreserve_main_section'
      );
      
      add_settings_field(
         'rooms',
         'Room List',
         array(__CLASS__, 'phwreserve_rooms_callback'),
         self::$option_page,
         'phwreserve_main_section'
      );
      
      add_settings_field(
         'todays_hours',
         "Today&#39;s Hours Data",
         array(__CLASS__, 'phwreserve_todays_hours_callback'),
         self::$option_page,
         'phwreserve_main_section'
      );

      add_settings_field(
         'temp_res_len',
         'Keep Unconfirmed Reservation',
         array(__CLASS__, 'phwreserve_temp_res_len_callback'),
         self::$option_page,
         'phwreserve_main_section'
      );

      add_settings_field(
         'max_res_len',
         'Maximum Reservation Length',
         array(__CLASS__, 'phwreserve_max_res_len_callback'),
         self::$option_page,
         'phwreserve_main_section'
      );

      add_settings_field(
         'keep_old_len',
         'Keep Past Reservations',
         array(__CLASS__, 'phwreserve_keep_old_len_callback'),
         self::$option_page,
         'phwreserve_main_section'
      );

      add_settings_field(
         'email_cust',
         'Custom Confirmation Email Message',
         array(__CLASS__, 'phwreserve_email_cust_callback'),
         self::$option_page,
         'phwreserve_main_section'
      );

      add_settings_field(
         'reply_to',
         'Reply-to Address',
         array(__CLASS__, 'phwreserve_reply_to_callback'),
         self::$option_page,
         'phwreserve_main_section'
      );
    
      register_setting(self::$option_page, self::$option_name, array(__CLASS__, 'phwreserve_sanitize_callback'));
   }

   /**
   * Writes HTML for main section. Not used in 1.0
   * @since 1.0
   */
   public function phwreserve_main_section_callback($args) {}
   
   
   /**
   * Writes HTML for valid_emails setting
   * @since 1.0
   */
   public function phwreserve_valid_emails_callback($args) {
      $valid_emails = explode("\n", self::$settings['valid_emails']);
      
      $html = "<p>We will accept reservations only from the following email domains.<br>Enter one domain per line, i.e. <strong>gmail.com</strong></p>";     
      $html .= "<p><textarea name='phwreserve_settings[valid_emails]' id='valid_emails' rows='8' cols='50' spellcheck='false'>";
      foreach ($valid_emails as $email) {
         $html .= "{$email}\n";
      }
      $html = rtrim($html);
      $html .= "</textarea></p>";
      echo $html;
   }
 
 
   /**
   * Writes HTML for rooms setting
   * @since 1.0
   */
   public function phwreserve_rooms_callback($args) {
      $rooms = explode("\n", self::$settings['rooms']);

      $html = "<p>This list contains all rooms available for reservation.<br>Enter one room name per line.</p>";
      $html .= "<p><textarea name='phwreserve_settings[rooms]' id='rooms' rows='8' cols='50' spellcheck='false'>";
      foreach ($rooms as $room) {
         $html .= "{$room}\n";
      }
      $html = rtrim($html);
      $html .= "</textarea></p>";
      echo $html;
   }
 
 
   /**
   * Writes HTML for todays_hours setting
   * @since 1.0
   */
   public function phwreserve_todays_hours_callback($args) {
      $html = "<input type='checkbox' name='phwreserve_settings[todays_hours]' id='todays_hours' " 
               . (self::$settings['todays_hours'] ? 'checked' : '') 
               . "><label for='todays_hours'>Use business hour data from our Today&#39;s Hours plugin (not implemented)</label>";
      echo $html;
   }

   /**
   * Writes HTML for temp_res_len setting
   * @since 1.0
   */
   public function phwreserve_temp_res_len_callback($args) {
      $html = "<input type='text' name='phwreserve_settings[temp_res_len]' id='temp_res_len' size='2' value='"
               . self::$settings['temp_res_len'] . "' maxlength='2'>"
               . "<label for='temp_res_len'>&nbsp;Number of hours a user has to confirm reservation request</label>";
      echo $html;
   }


   /**
   * Writes HTML for max_res_len setting
   * @since 1.0
   */
   public function phwreserve_max_res_len_callback($args) {
      $html = "<input type='text' name='phwreserve_settings[max_res_len]' id='max_res_len' size='2' value='"
               . self::$settings['max_res_len'] . "' maxlength='2'>"
               . "<label for='max_res_len'>&nbsp;Number of hours a reservation can be made for (Restriction "
               . "does not apply to logged-in users)</label>";
      echo $html;
   }


   /**
   * Writes HTML for keep_old_len setting
   * @since 1.0
   */
   public function phwreserve_keep_old_len_callback($args) {
      $html = "<input type='text' name='phwreserve_settings[keep_old_len]' id='keep_old_len' size='4' value='"
               . self::$settings['keep_old_len'] . "' maxlength='4'>"
               . "<label for='keep_old_len'>&nbsp;Days to keep past reservations in database (0 = keep forever)</label>";
      echo $html;
   }


   /**
   * Writes HTML for email_cust setting
   * @since 1.0
   */
   public function phwreserve_email_cust_callback($args) {
      $html = "<p>Message displayed in confirmation email</p>";
      $html .= "<p><textarea name='phwreserve_settings[email_cust]' id='email_cust' rows='8' cols='50' spellcheck='true'>";
      $html .= self::$settings['email_cust'];
      $html .= "</textarea></p>";
      echo $html;
   }

   /**
   * Writes HTML for reply_to setting
   * @since 1.0
   */
   public function  phwreserve_reply_to_callback($args) {
      $html = "<input type='text' name='phwreserve_settings[reply_to]' id='reply_to' size='20' value='"
               . self::$settings['reply_to'] . "' maxlength='100'>"
               . "<label for='reply_to'>&nbsp;Use this address when user replies to emails</label>";
      echo $html;
   }


   /**
   * Writes HTML for setting page
   * @since 1.0
   */
   public function phwreserve_settings_page_callback() { ?>
      <div class="wrap">
         <div id="icon-tools" class="icon32">&nbsp;</div>
         <h2>Room Reservation</h2>
         <form method="post" action="options.php">
            <?php settings_fields(self::$option_page);?>
            <?php do_settings_sections(self::$option_page);?>
            <?php submit_button();?>
         </form>
      </div>
   <?php
   }
   
   
   /**
   * Cleans up whitespace from user's settings input
   * @since 1.0
   * @todo Any necessary validation/sanitizing
   * @return mixed $input the values from the form fields
   */
   public function phwreserve_sanitize_callback($input) {
      $valid_emails = $input['valid_emails'];
      $rooms = $input['rooms'];
      $valid_emails = rtrim(preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $valid_emails));
      $rooms = rtrim(preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $rooms));
      return $input;
   }

   
   /**
   * Saves settings to WP option
   * @since 1.0
   */
   public function save_settings() {
      update_option(self::$option_name, self::$settings);
   }
   
   
   /**
   * Gets the name of the option in WordPress
   * @since 1.0
   */
   public function get_option_name() {
      return self::$option_name;
   }
}
