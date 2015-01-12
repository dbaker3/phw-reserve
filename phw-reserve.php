<?php
/*
Plugin Name: PHW-Reserve
Description: Reserve rooms. No account required. Users must have an email account in authorized domain name.
Version: 1.0
Plugin URI: https://github.com/dbaker3/phw-reserve
Author: David Baker - Milligan College
Author URI: https://github.com/dbaker3
*/

include 'inc/phw-reserve-settings.php';
include 'inc/phw-reserve-reservation.php';
include 'inc/phw-reserve-form.php';

// install database on plugin activation
include 'inc/phw-reserve-db-install.php';
register_activation_hook( __FILE__, 'phwreserve_install_db');


$phwreserve_settings = new PHWReserveSettings();

function phw_reserve_shortcode() {
   $phwreserve_controller = new PHWReservePageController();
   


}

add_shortcode('phw-reserve-page-controller', 'phw_reserve_shortcode');