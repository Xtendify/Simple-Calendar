<?php
/**
 * Shortcodes
 *
 * @package SimpleCalendar
 */
namespace SimpleCalendar;

use SimpleCalendar\Abstracts\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes.
 *
 * Register and handle custom shortcodes.
 *
 * @since 3.0.0
 */
class Shortcodes {

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		// Add shortcodes.
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register shortcodes.
	 *
	 * @since 3.0.0
	 */
	public function register() {

		add_shortcode( 'calendar', array( $this, 'print_calendar' ) );
		// @deprecated legacy shortcode
		add_shortcode( 'gcal', array( $this, 'print_calendar' ) );

		do_action( 'simcal_add_shortcodes' );
	}

	/**
	 * Print a calendar.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $attributes
	 *
	 * @return string
	 */
	public function print_calendar( $attributes ) {

		$args = shortcode_atts( array(
			'id' => null,
		), $attributes );

		$id = absint( $args['id'] );

		if ( $id > 0 ) {

			$calendar = simcal_get_calendar( $id );

			if ( $calendar instanceof Calendar ) {
				ob_start();
				$calendar->html();
				return ob_get_clean();
			}

		}

		return '';
	}

}
