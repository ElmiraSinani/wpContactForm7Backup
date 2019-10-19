<?php
/*
Plugin Name: Contact Form 7 backup 
Plugin URI: https://github.com/ElmiraSinani/wpContactForm7Backup
Description: Contact Form 7 backup 
Version: 1.0.0
Author: E.Sinani
Author URI: #
License: MIT License
Text Domain: cf7b
*/

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}

/**
* Define
*/
define( 'CF7B_URL', plugins_url( '', __FILE__ ) );
define( 'CF7B_DIR', plugin_dir_path( __FILE__ ) );
define( 'CF7B_VER', '1.0.0' );


register_activation_hook(__FILE__, 'wp_cf7b_install');
/**
 * Create purchasers and Response payment detail tables
 */
function wp_cf7b_install() {    
    global $wpdb;   
     
    $cf7BackupTable = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."contact_form7_backup` (
        `id` int(15) unsigned NOT NULL AUTO_INCREMENT,         
        `formTitle` VARCHAR(100) NOT NULL, 
        `formID` VARCHAR(50) NOT NULL,  
        `date` DATETIME,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1";    
	
    //Response payment details returned from api
    $cf7BackupFieldsConnection = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."contact_form7_backup_fields` (
        `id` int(15) unsigned NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(100) NOT NULL, 
        `cf7_field_name` VARCHAR(100) NOT NULL,  
        `cf7_backup_column` VARCHAR(100) NOT NULL, 
        PRIMARY KEY (`id`)
    )ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($cf7BackupTable);
    dbDelta($cf7BackupFieldsConnection);
}

/** 
 * allow redirection, even if my theme starts to send output to the browser 
 **/
add_action('init', 'do_output_buffer');
function do_output_buffer() {
    ob_start();
}

//show all errors
//need to remove this after development
add_action('init', 'showAllErrors');
function showAllErrors(){
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'classes/Cf7b_Frontennd_Options.php';
require plugin_dir_path( __FILE__ ) . 'classes/Cf7b_Admin_Settings.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_cf7b_result() {
    $plugin = new Cf7b_Admin_Settings();
    $plugin = new Cf7b_Frontennd_Options();
	
}
run_cf7b_result();