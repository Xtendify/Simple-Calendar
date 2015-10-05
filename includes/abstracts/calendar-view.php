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
 *
 * @since 3.0.0
 */
interface Calendar_View {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $args
	 */
	public function __construct( $args = '' );

	/**
	 * Return the parent calendar type.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	public function get_parent();

	/**
	 * Return the view type.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	public function get_type();

	/**
	 * Return the view name.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Add ajax actions.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function add_ajax_actions();

	/**
	 * Scripts.
	 *
	 * Returns the view scripts as associative array.
	 *
	 * @since  3.0.0
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
	 * @since  3.0.0
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
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function html();

}
