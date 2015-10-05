<?php
/**
 * Widget
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Abstracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Widget.
 *
 * Basic widget interface.
 *
 * @since 3.0.0
 */
interface Widget {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct();

	/**
	 * Print the widget content.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $args     Display arguments.
	 * @param  array $instance The settings for the particular instance of the widget.
	 *
	 * @return void
	 */
	public function widget( $args, $instance );

	/**
	 * Update a particular instance of the widget.
	 *
	 * This function should check that $new_instance is set correctly.
	 * The newly-calculated value of `$instance` should be returned.
	 * If false is returned, the instance won't be saved/updated.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $new_instance New settings for this instance as input by the user via
	 * @param  array $old_instance Old settings for this instance.
	 *
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance );

	/**
	 * Print the settings update form.
	 *
	 * @since  3.0.0
	 *
	 * @param  array  $instance Current settings.
	 *
	 * @return string
	 */
	public function form( $instance );

}
