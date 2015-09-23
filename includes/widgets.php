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
	 */
	public function __construct() {

		$this->widgets = apply_filters( 'simcal_get_widgets', array(
			'SimpleCalendar\Widgets\Calendar'
		), array() );

		add_action( 'widgets_init', array( $this, 'register' ) );
	}

	/**
	 * Register widgets.
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
