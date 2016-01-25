<?php
/**
 * Update to 3.0.13
 *
 * @package SimpleCalendar/Updates
 */
namespace SimpleCalendar\Updates;

use Carbon\Carbon;
use SimpleCalendar\Post_Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update to 3.0.13
 */
class Update_V3013 {

	/**
	 * Update posts and options.
	 *
	 * @param array $posts
	 */
	public function __construct( $posts ) {

		$this->update_options();
	}


	/**
	 * Update options.
	 */
	public function update_options() {

		$settings_advanced = get_option( 'simple-calendar_settings_advanced' );
		
		// Remove stored always_enqueue value
		if ( isset( $settings_advanced['assets']['always_enqueue'] ) ) {
			unset( $settings_advanced['assets']['always_enqueue'] );
		}

		// Remove stored disable_js value
		if ( isset( $settings_advanced['assets']['disable_js'] ) ) {
			unset( $settings_advanced['assets']['disable_js'] );
		}

		update_option( 'simple-calendar_settings_advanced', $settings_advanced );

		// Delete legacy options.
		delete_option( 'simple-calendar_defaults' );
	}

}
