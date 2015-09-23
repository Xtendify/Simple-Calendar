<?php
/**
 * Front End Ajax
 *
 * @package SimpleCalendar
 */
namespace SimpleCalendar;

use SimpleCalendar\Abstracts\Calendar_View;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front facing ajax.
 */
class Ajax {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_callbacks' ), 100 );
	}

	/**
	 * Add ajax callbacks.
	 */
	public function add_callbacks() {

		$calendars = simcal_get_calendar_types();

		foreach( $calendars as $calendar => $views ) {

			foreach( $views as $view ) {

				$the_view = simcal_get_calendar_view( 0, $calendar . '-' . $view );

				if ( $the_view instanceof Calendar_View ) {
					$the_view->add_ajax_actions();
				}
			}
		}

		do_action( 'simcal_add_ajax_callbacks' );
	}

}
