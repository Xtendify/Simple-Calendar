<?php
/**
 * Upgrade to Premium Add-ons Meta Box
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Metaboxes;

use SimpleCalendar\Abstracts\Meta_Box;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrade to Premium Add-ons.
 *
 * @since 3.1.6
 */
class Upgrade_To_Premium implements Meta_Box {

	/**
	 * Output HTML.
	 *
	 * @since 3.1.6
	 *
	 * @param \WP_Post $post
	 */
	public static function html( $post ) {
		simcal_upgrade_to_premium();
	}

	/**
	 * Save settings.
	 *
	 * @since 3.1.6
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		// This meta box has no persistent settings.
	}

}
