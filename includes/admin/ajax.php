<?php
/**
 * Admin Ajax
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin ajax.
 */
class Ajax {

	/**
	 * Set up ajax hooks.
	 */
	public function __construct() {

		// Set an option if the user rated the plugin.
		add_action( 'wp_ajax_simcal_rated', array( $this, 'rate_plugin' ) );

		// Set an option if the user rated the plugin.
		add_action( 'wp_ajax_simcal_clear_cache', array( $this, 'clear_cache' ) );

		// Convert a datetime format.
		add_action( 'wp_ajax_simcal_date_i18n_input_preview', array( $this, 'date_i18n' ) );
	}

	/**
	 * Clear transients.
	 */
	public function clear_cache() {

		$id = isset( $_POST['id'] ) ? ( is_array( $_POST['id'] ) ? array_map( 'intval', $_POST['id'] ) : intval( $_POST['id'] ) ) : '';

		if ( ! empty( $id ) ) {
			simcal_delete_feed_transients( $id );
		}
	}

	/**
	 * Ajax callback when a user clicks on the rate plugin link.
	 */
	public function rate_plugin() {
		update_option( 'simple-calendar_admin_footer_text_rated', date( 'Y-m-d', time() ) );
	}

	/**
	 * Ajax callback to return a formatted datetime string.
	 */
	public function date_i18n() {

		$value     = isset( $_POST['value'] )     ? sanitize_text_field( $_POST['value'] ) : ' ';
		$timestamp = isset( $_POST['timestamp'] ) ? absint( $_POST['timestamp'] ) : time();

		wp_send_json_success( date_i18n( $value, $timestamp ) );
	}

}
