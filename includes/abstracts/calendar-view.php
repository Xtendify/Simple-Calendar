<?php
/**
 * Calendar View
 *
 * @package SimpleCalendar/Calendars
 */
namespace SimpleCalendar\Abstracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Calendar View.
 *
 * An individual view, handling assets and markup, of a specific Calendar.
 */
interface Calendar_View {

	/**
	 * Constructor.
	 *
	 * @param mixed $args
	 */
	public function __construct( $args = '' );

	/**
	 * Return the parent calendar type.
	 *
	 * @return string
	 */
	public function get_parent();

	/**
	 * Return the view type.
	 *
	 * @return string
	 */
	public function get_type();

	/**
	 * Return the view name.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Add ajax actions.
	 */
	public function add_ajax_actions();

	/**
	 * Scripts.
	 *
	 * Returns the view scripts as associative array.
	 *
	 * @param  string $min
	 *
	 * @return array
	 */
	public function scripts( $min = '' );

	/**
	 * Styles.
	 *
	 * Returns the view styles as associative array.
	 *
	 * @param  string $min
	 *
	 * @return array
	 */
	public function styles( $min = '' );

	/**
	 * Print HTML.
	 *
	 * Echoes the view markup.
	 */
	public function html();

}
