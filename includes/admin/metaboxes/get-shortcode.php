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
 */
class Get_Shortcode implements Meta_Box {

	public static function html( $post ) {
		simcal_print_shortcode_tip( $post->ID );
	}

	public static function save( $post_id, $post ) {
		// This Meta Box does not have settings.
	}

}
