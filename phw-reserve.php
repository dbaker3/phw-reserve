<?php
/*
Plugin Name: PHW Reserve
Description: Reserve rooms. No account required, however users must have an email account in the list of authorized domain names.
Version: 1.0
Plugin URI: https://github.com/dbaker3/phw-reserve
Author: David Baker - Milligan College
Author URI: https://github.com/dbaker3
License: GPL2
*/

include 'inc/phw-reserve-db-install.php';
include 'inc/phw-reserve-settings.php';
include 'inc/phw-reserve-page-controller.php';
include 'inc/phw-reserve-menu.php';
include 'inc/phw-reserve-calendar.php';
include 'inc/phw-reserve-form.php';
include 'inc/phw-reserve-reservation.php';

PHWReserveSettings::init();

/**
* Registers shortcode to access plugin from Page or Post
* @since 1.0
*/
function phw_reserve_shortcode() {
   $phwreserve_ctrlr = new PHWReservePageController();
   $phwreserve_ctrlr->init();
}
add_shortcode('phw-reserve-page', 'phw_reserve_shortcode');

/**
* Enqueues CSS and scripts for plugin
* @since 1.0
*/
function phwreserve_enqueue_files() {
   wp_enqueue_style('phwreserve_css', plugins_url('css/phwreserve.css', __FILE__), array(), filemtime(dirname(__FILE__) . '/css/phwreserve.css'), 'all');
   wp_enqueue_script('phwreserve_js', plugins_url('js/phwreserve.js', __FILE__), array('jquery'), filemtime(dirname(__FILE__) . '/js/phwreserve.js'), false);
   wp_enqueue_script('timepicker_js', plugins_url('timepicker/jquery.timepicker.min.js', __FILE__), array('jquery'), filemtime(dirname(__FILE__) . '/timepicker/jquery.timepicker.min.js'), false);
   wp_enqueue_style('timepicker_css', plugins_url('timepicker/jquery.timepicker.css', __FILE__), array(), filemtime(dirname(__FILE__) . '/timepicker/jquery.timepicker.css'), 'all');
}
add_action('wp_enqueue_scripts', 'phwreserve_enqueue_files');
