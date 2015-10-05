<?php
/**
 * Widgets
 *
 * @package SimpleCalendar
 */
namespace SimpleCalendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widgets
 *
 * Register and handle widgets.
 *
 * @since 3.0.0
 */
class Widgets {

	/**
	 * Widgets.
	 *
	 * @access public
	 * @var array
	 */
	public $widgets = array();

	/**
	 * Register new widgets.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->widgets = apply_filters( 'simcal_get_widgets', array(
			'SimpleCalendar\Widgets\Calendar'
		), array() );

		add_action( 'widgets_init', array( $this, 'register' ) );
	}

	/**
	 * Register widgets.
	 *
	 * @since 3.0.0
	 */
	public function register() {

		$widgets = $this->widgets;

		if ( ! empty( $widgets ) && is_array( $widgets ) ) {
			foreach ( $widgets as $widget ) {
				register_widget( $widget );
			}
		}

		do_action( 'simcal_register_widgets' );
	}

}
