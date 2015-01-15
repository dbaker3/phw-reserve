<?php
/*
Plugin Name: PHW-Reserve
Description: Reserve rooms. No account required. Users must have an email account in authorized domain name.
Version: 1.0
Plugin URI: https://github.com/dbaker3/phw-reserve
Author: David Baker - Milligan College
Author URI: https://github.com/dbaker3
*/

include 'inc/phw-reserve-db-install.php';
include 'inc/phw-reserve-settings.php';
include 'inc/phw-reserve-page-controller.php';
include 'inc/phw-reserve-calendar.php';
include 'inc/phw-reserve-form.php';
include 'inc/phw-reserve-reservation.php';

$phwreserve_settings = new PHWReserveSettings();

function phw_reserve_shortcode() {
   $phwreserve_ctrlr = new PHWReservePageController();
   $phwreserve_ctrlr->init();
}

add_shortcode('phw-reserve-page-controller', 'phw_reserve_shortcode');