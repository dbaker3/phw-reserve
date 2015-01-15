<?php
/*
   Database table install script
   David Baker, Milligan College 2015
*/

function phw_reservations_table() {
   global $wpdb;
   $wpdb->phw_reservations = "{$wpdb->prefix}phw_reservations";
}

function phw_reserve_create_table() {
   require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
   global $wpdb;
   global $charset_collate;
   
   phw_reservations_table(); // in case we missed init hook
   
   $sql_create_table = "CREATE TABLE {$wpdb->phw_reservations} (
            res_id int(10) unsigned NOT NULL auto_increment,
            patron_name varchar(100) NOT NULL default '',
            patron_email varchar(100) NOT NULL default '',
            datetime_begin datetime NOT NULL default '0000-00-00 00:00:00',
            datetime_end datetime NOT NULL default '0000-00-00 00:00:00',
            purpose varchar(100) NOT NULL default '',
            room varchar(100) NOT NULL default '',
            PRIMARY KEY  (res_id),
            ) $charset_collate; ";
            
   dbDelta($sql_create_table);
}

add_action( 'init', 'phw_reservations_table', 1 );
add_action( 'switch_blog', 'phw_reservations_table' );
register_activation_hook( __FILE__, 'phw_reserve_create_table');