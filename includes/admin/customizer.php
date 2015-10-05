<?php
/**
 * Customizer
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customizer settings.
 *
 * Handles the WordPress customizer settings.
 */
class Customizer {

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'customize_register', array( $this, 'register' ) );
	}

	/**
	 * Register customizer objects.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Customize_Manager $wp_customize
	 */
	public function register( $wp_customize ) {
		do_action( 'simple_calendar_customizer', $wp_customize );
	}

}
