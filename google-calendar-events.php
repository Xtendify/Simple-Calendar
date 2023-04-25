<?php
/**
 * Plugin Name: Simple Calendar
 * Plugin URI:  https://simplecalendar.io
 * Description: Add Google Calendar events to your WordPress site in minutes. Beautiful calendar displays. Fully responsive.
 * Author:      Simple Calendar
 * Author URI:  https://simplecalendar.io
 * Version:     3.1.42
 * Text Domain: google-calendar-events
 * Domain Path: /i18n
 *
 * @copyright   2013-2023 Xtendify Technologies. All rights reserved.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
$this_plugin_path      = trailingslashit( dirname( __FILE__ ) );
$this_plugin_dir       = plugin_dir_url( __FILE__ );
$this_plugin_constants = array(
	'SIMPLE_CALENDAR_VERSION'   => '3.1.42',
	'SIMPLE_CALENDAR_MAIN_FILE' => __FILE__,
	'SIMPLE_CALENDAR_URL'       => $this_plugin_dir,
	'SIMPLE_CALENDAR_ASSETS'    => $this_plugin_dir . 'assets/',
	'SIMPLE_CALENDAR_PATH'      => $this_plugin_path,
	'SIMPLE_CALENDAR_INC'       => $this_plugin_path . 'includes/',
);
foreach ( $this_plugin_constants as $constant => $value ) {
	if ( ! defined( $constant ) ) {
		define( $constant, $value );
	}
}

// Plugin requirements

include_once 'includes/wp-requirements.php';

// Check plugin requirements before loading plugin.
$this_plugin_checks = new SimCal_WP_Requirements( 'Simple Calendar', plugin_basename( __FILE__ ), array(
		'PHP'        => '7.3',
		'WordPress'  => '4.2',
		'Extensions' => array(
			'curl',
			'iconv',
			'json',
			'mbstring',
		),
	) );
if ( $this_plugin_checks->pass() === false ) {
	$this_plugin_checks->halt();

	return;
}

include_once 'third-party/vendor/autoload.php';


// Load plugin.
include_once 'includes/main.php';
?>
<link href="/css/output-style.css" rel="stylesheet">
<link href="/css/admin-sett-style.css" rel="stylesheet">
<?php

function sd_admin_scripts() {


	wp_register_style( 'admin-sd-style', SIMPLE_CALENDAR_ASSETS . 'css/output-style.css' , false );
	wp_enqueue_style( 'admin-sd-style' );
	wp_register_style( 'admin-sett-style', SIMPLE_CALENDAR_ASSETS . 'css/admin-sett-style.css' , false );
	wp_enqueue_style( 'admin-sett-style' );

	// wp_register_script( 'select2', 'https://cdn.tailwindcss.com', array( 'tailwindcss' ),  true );
	// wp_enqueue_script( 'select2' );
	
	 }
	
	add_action( 'admin_enqueue_scripts', 'sd_admin_scripts' );
