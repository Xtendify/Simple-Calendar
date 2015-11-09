<?php
/**
 * Plugin Name: Simple Calendar
 * Plugin URI:  https://wordpress.org/plugins/google-calendar-events/
 * Description: Show off your Google calendar in grid (month) or list view, in a post, page or widget, and in a style that matches your site.
 *
 * Version:     3.0.1
 *
 * Author:      Moonstone Media
 * Author URI:  http://moonstonemediagroup.com
 *
 * Text Domain: google-calendar-events
 * Domain Path: /languages
 *
 * @package     SimpleCalendar
 * @copyright   2014-2015 Moonstone Media/Phil Derksen. All rights reserved.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer fallback for PHP < 5.3.0.
if ( version_compare( PHP_VERSION, '5.3.0' ) === -1 ) {
	include_once 'vendor/autoload_52.php';
} else {
	include_once 'vendor/autoload.php';
}

// Plugin constants.
$this_plugin_path = trailingslashit( dirname( __FILE__ ) );
$this_plugin_dir  = plugin_dir_url( __FILE__ );
$this_plugin_constants = array(
	'SIMPLE_CALENDAR_VERSION'   => '3.0.1',
	'SIMPLE_CALENDAR_MAIN_FILE' => __FILE__,
	'SIMPLE_CALENDAR_URL'       => $this_plugin_dir,
	'SIMPLE_CALENDAR_ASSETS'    => $this_plugin_dir  . 'assets/',
	'SIMPLE_CALENDAR_PATH'      => $this_plugin_path,
	'SIMPLE_CALENDAR_INC'       => $this_plugin_path . 'includes/',
);
foreach ( $this_plugin_constants as $constant => $value ) {
	if ( ! defined( $constant ) ) {
		define( $constant, $value );
	}
}

// Check plugin requirements before loading plugin.
$this_plugin_checks = new WP_Requirements(
	'Simple Calendar',
	plugin_basename( __FILE__ ),
	array(
		'PHP'       => '5.3.2',
		'WordPress' => '4.0.0',
	)
);
if ( $this_plugin_checks->pass() === false ) {
	$this_plugin_checks->halt();
	return;
}

// Load plugin.
include_once 'includes/main.php';
