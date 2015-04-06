<?php
/**
* Contains database table install functions
*
* Two tables are created by these functions. The first, phw_reservations, is the
* main table that holds created reservations. The second table, phw_reservations_recur
* holds an entry for each recurring instance of a reservation that is created as
* recurring. These entries are linked back to the main reservation in the 
* phw_reservations table.
*
* Adds table setup and creation to the init and switch_blog hooks
* @author David Baker
* @copyright 2015 Milligan College
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU Public License v2
* @since 1.0
*/


/**
* Sets main table name for WPDB class
* @since 1.0
*/
function phw_reservations_table() {
   global $wpdb;
   $wpdb->phw_reservations = "{$wpdb->prefix}phw_reservations";
}


/**
* Creates the table for reservation data 
* @since 1.0
*/
function phw_reserve_create_reservations_table() {
   require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
   global $wpdb;
   global $charset_collate;
   
   phw_reservations_table(); // in case we missed init hook
   
   $sql_create_table = "CREATE TABLE {$wpdb->phw_reservations} (
            res_id int(10) unsigned NOT NULL auto_increment,
            patron_name varchar(100) NOT NULL default '',
            patron_email varchar(100) NOT NULL default '',
            datetime_start bigint NOT NULL default 0,
            datetime_end bigint NOT NULL default 0,
            purpose varchar(100) NOT NULL default '',
            room varchar(100) NOT NULL default '',
            auth_code varchar(20) NOT NULL default '',
            recurs boolean NOT NULL default 0,
            recurs_until bigint NOT NULL default 0,
            recurs_on varchar(100) NOT NULL default '{}',
            PRIMARY KEY  (res_id)
            ) $charset_collate; ";
            
   dbDelta($sql_create_table);
}


add_action( 'init', 'phw_reservations_table', 1 );
add_action( 'switch_blog', 'phw_reservations_table' );
register_activation_hook(plugin_dir_path(dirname(__FILE__)) . 'phw-reserve.php', 'phw_reserve_create_reservations_table');


/**
* Sets recurring reservation table name for WPDB class
* @since 1.0
*/
function phw_reservations_recur_table() {
   global $wpdb;
   $wpdb->phw_reservations_recur = "{$wpdb->prefix}phw_reservations_recur";
}


/**
* Creates the table for recurring reservation data 
* @since 1.0
*/
function phw_reserve_create_recur_table() {
   require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
   global $wpdb;
   global $charset_collate;
   
   phw_reservations_recur_table(); // in case we missed init hook
   
   $sql_create_table = "CREATE TABLE {$wpdb->phw_reservations_recur} (
            recur_id int(10) unsigned NOT NULL auto_increment,
            res_id int(10) unsigned NOT NULL default 0,
            r_datetime_start bigint NOT NULL default 0,
            r_datetime_end bigint NOT NULL default 0,
            PRIMARY KEY  (recur_id)
            ) $charset_collate; ";
            
   dbDelta($sql_create_table);
}


add_action( 'init', 'phw_reservations_recur_table', 1 );
add_action( 'switch_blog', 'phw_reservations_recur_table' );
register_activation_hook(plugin_dir_path(dirname(__FILE__)) . 'phw-reserve.php', 'phw_reserve_create_recur_table');
