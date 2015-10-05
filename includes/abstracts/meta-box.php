<?php
/**
 * Meta Box
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Abstracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Meta Box.
 *
 * Basic interface for post meta boxes markup and post meta handling.
 *
 * @since 3.0.0
 */
interface Meta_Box {

	/**
	 * Output the meta box markup.
	 *
	 * @since  3.0.0
	 *
	 * @param  \WP_Post $post
	 *
	 * @return void
	 */
	public static function html( $post );

	/**
	 * Validate and save the meta box fields.
	 *
	 * @since  3.0.0
	 *
	 * @param  int      $post_id
	 * @param  \WP_Post $post
	 *
	 * @return void
	 */
	public static function save( $post_id, $post );

}
