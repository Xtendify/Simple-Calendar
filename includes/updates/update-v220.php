<?php
/**
 * Update to 2.2.0
 *
 * @package SimpleCalendar/Updates
 */
namespace SimpleCalendar\Updates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update to 2.2.0
 */
class Update_V220 {

	/**
	 * Update feeds meta
	 *
	 * @param array $posts
	 */
	public function __construct( $posts ) {

		if ( ! empty( $posts ) && is_array( $posts ) ) {

			foreach ( $posts as $post ) {

				$gce_list_max_num        = get_post_meta( $post->ID, 'gce_list_max_num', true );
				$gce_list_max_length     = get_post_meta( $post->ID, 'gce_list_max_length', true );
				$gce_feed_start_interval = get_post_meta( $post->ID, 'gce_feed_start_interval', true );
				$gce_feed_start          = get_post_meta( $post->ID, 'gce_feed_start', true );
				$gce_feed_end_interval   = get_post_meta( $post->ID, 'gce_feed_end_interval', true );
				$gce_feed_end            = get_post_meta( $post->ID, 'gce_feed_end', true );

				update_post_meta( $post->ID, 'gce_per_page_num', $gce_list_max_num );
				update_post_meta( $post->ID, 'gce_events_per_page', $gce_list_max_length );
				update_post_meta( $post->ID, 'gce_feed_start', $gce_feed_start_interval );
				update_post_meta( $post->ID, 'gce_feed_start_num', $gce_feed_start );
				update_post_meta( $post->ID, 'gce_feed_end', $gce_feed_end_interval );
				update_post_meta( $post->ID, 'gce_feed_end_num', $gce_feed_end );

				// Add new show tooltips option checked as default.
				update_post_meta( $post->ID, 'gce_show_tooltips', 1 );
			}

		}
	}

}
