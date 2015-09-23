<?php
/**
 * Update to 2.1.0
 *
 * @package SimpleCalendar/Updates
 */
namespace SimpleCalendar\Updates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update to 2.1.0
 */
class Update_V210 {

	/**
	 * Update feed url meta in feed posts.
	 *
	 * @param array $posts
	 */
	public function __construct( $posts ) {

		if ( ! empty( $posts ) && is_array( $posts ) ) {

			foreach ( $posts as $post ) {

				$url = get_post_meta( $post->ID, 'gce_feed_url', true );

				if ( $url ) {

					$url = str_replace( 'https://www.google.com/calendar/feeds/', '', $url );
					$url = str_replace( '/public/basic', '', $url );
					$url = str_replace( '%40', '@', $url );

					update_post_meta( $post->ID, 'gce_feed_url', $url );
				}

			}

		}
	}

}
