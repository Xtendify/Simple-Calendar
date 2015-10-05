<?php
/**
 * Get Shortcode Meta Box
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Metaboxes;

use SimpleCalendar\Abstracts\Meta_Box;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a shortcode for the current calendar.
 *
 * @since 3.0.0
 */
class Get_Shortcode implements Meta_Box {

	/**
	 * Output HTML.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Post $post
	 */
	public static function html( $post ) {
		simcal_print_shortcode_tip( $post->ID );
	}

	/**
	 * Save settings.
	 *
	 * @since 3.0.0
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		// This Meta Box does not have settings.
	}

}
