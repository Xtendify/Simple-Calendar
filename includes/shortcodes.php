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
 */
class Shortcodes {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {

		// Add shortcodes.
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register shortcodes.
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
	 * @param  array $attributes
	 */
	public function print_calendar( $attributes ) {

		$args = shortcode_atts( array(
			'id' => null,
		), $attributes );

		if ( ! is_numeric( $args['id'] ) ) {
			return;
		}

		if ( ( $id = absint( $args['id'] ) ) > 0 ) {

			$calendar = simcal_get_calendar( $id );

			if ( $calendar instanceof Calendar ) {
				$calendar->html();
			}

		}
	}

}
