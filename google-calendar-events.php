<?php
/**
 * Plugin Name: Simple Calendar
 * Plugin URI:  https://simplecalendar.io
 * Description: Add Google Calendar events to your WordPress site in minutes. Beautiful calendar displays. Fully responsive.
 * Author:      Moonstone Media
 * Author URI:  https://simplecalendar.io
 * Version:     3.1.9
 * Text Domain: google-calendar-events
 * Domain Path: /i18n
 */

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright 2015-2017 Moonstone Media Group. All rights reserved.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
$this_plugin_path      = trailingslashit( dirname( __FILE__ ) );
$this_plugin_dir       = plugin_dir_url( __FILE__ );
$this_plugin_constants = array(
	'SIMPLE_CALENDAR_VERSION'   => '3.1.9',
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
		'PHP'        => '5.3.3',
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

include_once 'vendor/autoload.php';

// Load plugin.
include_once 'includes/main.php';
