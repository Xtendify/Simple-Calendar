<?php
/**
 * Update to 3.0.0
 *
 * @package SimpleCalendar/Updates
 */
namespace SimpleCalendar\Updates;

use Carbon\Carbon;
use SimpleCalendar\Admin\Notice;
use SimpleCalendar\Post_Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update to 3.0.0
 */
class Update_V300 {

	/**
	 * Legacy bundled api key.
	 *
	 * @access private
	 * @var string
	 */
	private $legacy_api_key = 'AIzaSyAssdKVved1mPVY0UJCrx96OUOF9u17AuY';

	/**
	 * Update posts and options.
	 *
	 * @param array $posts
	 */
	public function __construct( $posts ) {

		Post_Types::register_taxonomies();
		Post_Types::register_post_types();

		if ( ! empty( $posts ) && is_array( $posts ) ) {

			$this->update_posts( $posts );
			$this->update_post_type();
			$this->update_widgets();

		}

		$this->update_options();

		$settings = get_option( 'simple-calendar_settings_feeds' );
		$api_key = isset( $settings['google']['api_key'] ) ? $settings['google']['api_key'] : '';

		if ( $api_key == $this->legacy_api_key ) {

			$message  = '<p>' . __( 'It looks like you are using an old Google API Key which was previously bundled with the plugin. You should update this key with a key of your own.', 'google-calendar-events' ) . '</p>';

			$notice = new Notice( array(
				'id'          => 'legacy_api_key',
				'content'     => $message,
				'capability'  => 'manage_options',
				'dismissable' => true,
			) );

			$notice->add();
		}
	}

	/**
	 * Update posts.
	 *
	 * @param $posts
	 */
	public function update_posts( $posts ) {
		foreach ( $posts as $post ) {

			$post_id = $post->ID;

			// Feed type.
			wp_set_object_terms( $post_id, 'google', 'calendar_feed' );

			// Calendar type.
			wp_set_object_terms( $post_id, 'default-calendar', 'calendar_type' );
			$display = get_post_meta( $post_id, 'gce_display_mode', true );
			$views   = array();
			$range   = false;
			if ( 'list' == $display ) {
				$views['default-calendar'] = 'list';
			} elseif ( 'list-grouped' == $display ) {
				$views['default-calendar'] = 'list';
			} elseif ( 'date-range-list' == $display ) {
				$views['default-calendar'] = 'list';
				$range                     = true;
			} elseif ( 'date-range-grid' == $display ) {
				$views['default-calendar'] = 'grid';
				$range                     = true;
			} else {
				$views['default-calendar'] = 'grid';
			}
			update_post_meta( $post_id, '_calendar_view', $views );
			delete_post_meta( $post_id, 'gce_display_mode' );

			// Calendar list type.
			$list_span  = get_post_meta( $post_id, 'gce_events_per_page', true );
			$list_range = max( absint( get_post_meta( $post_id, 'gce_per_page_num', true ) ), 1 );
			if ( 'days' == $list_span ) {
				$list_type = 'daily';
			} elseif ( 'week' == $list_span ) {
				$list_type = 'weekly';
				$list_range = 1;
			} elseif ( 'month' == $list_span ) {
				$list_type = 'monthly';
				$list_range = 1;
			} else {
				$list_type = 'events';
			}
			update_post_meta( $post_id, '_default_calendar_list_range_type', $list_type );
			update_post_meta( $post_id, '_default_calendar_list_range_span', $list_range );
			delete_post_meta( $post_id, 'gce_events_per_page' );
			delete_post_meta( $post_id, 'gce_per_page_num' );

			// Custom calendar range.
			if ( $range == true ) {
				$begins = get_post_meta( $post_id, 'gce_feed_range_start', true );
				$ends   = get_post_meta( $post_id, 'gce_feed_range_end', true );
				if ( $begins && $ends ) {
					update_post_meta( $post_id, '_calendar_begins', 'custom_date' );
					update_post_meta( $post_id, '_calendar_begins_custom_date', $this->convert_legacy_range( $begins ) );
				} else {
					update_post_meta( $post_id, '_calendar_begins', 'today' );
				}
				delete_post_meta( $post_id, 'gce_feed_use_range' );
			} else {
				update_post_meta( $post_id, '_calendar_begins', 'today' );
			}
			delete_post_meta( $post_id, 'gce_feed_range_start' );
			delete_post_meta( $post_id, 'gce_feed_range_end' );

			// Earliest event.
			$start_before = get_post_meta( $post_id, 'gce_feed_start', true );
			$start_amt    = absint( get_post_meta( $post_id, 'gce_feed_start_num', true ) );
			if ( $start_amt > 0 ) {
				if ( 'years' == $start_before ) {
					$earliest = 'years_before';
				} elseif ( 'months' == $start_before ) {
					$earliest = 'months_before';
				} else {
					$earliest = 'days_before';
				}
				update_post_meta( $post_id, '_feed_earliest_event_date', $earliest );
				update_post_meta( $post_id, '_feed_earliest_event_date_range', $start_amt );
			} else {
				update_post_meta( $post_id, '_feed_earliest_event_date', 'calendar_start' );
				update_post_meta( $post_id, '_feed_earliest_event_date_range', 1 );
			}
			delete_post_meta( $post_id, 'gce_feed_start' );
			delete_post_meta( $post_id, 'gce_feed_start_num' );
			delete_post_meta( $post_id, 'gce_feed_start_custom' );

			// Latest event.
			$end_after = get_post_meta( $post_id, 'gce_feed_end', true );
			$end_amt   = absint( get_post_meta( $post_id, 'gce_feed_end_num', true ) );
			if ( $end_amt > 0 ) {
				if ( 'years' == $end_after ) {
					$latest = 'years_after';
				} elseif ( 'months' == $end_after ) {
					$latest = 'months_after';
				} else {
					$latest = 'days_after';
				}
				update_post_meta( $post_id, '_feed_latest_event_date', $latest );
				update_post_meta( $post_id, '_feed_latest_event_date_range', $end_amt );
			} else {
				update_post_meta( $post_id, '_feed_latest_event_date', 'calendar_start' );
				update_post_meta( $post_id, '_feed_latest_event_date_range', 1 );
			}
			delete_post_meta( $post_id, 'gce_feed_end' );
			delete_post_meta( $post_id, 'gce_feed_end_num' );
			delete_post_meta( $post_id, 'gce_feed_end_custom' );

			// Static calendar.
			if ( ! get_post_meta( $post_id, 'gce_paging', true ) ) {
				update_post_meta( $post_id, '_calendar_is_static', 'yes' );
				delete_post_meta( $post_id, 'gce_paging' );
			}

			// Default calendar bubble trigger (click was unavailable before 3.0.0).
			update_post_meta( $post_id, '_default_calendar_event_bubble_trigger', 'hover' );

			// Default calendar multiple day events.
			if ( get_post_meta( $post_id, 'gce_multi_day_events', true ) ) {
				update_post_meta( $post_id, '_default_calendar_expand_multi_day_events', 'yes' );
				delete_post_meta( $post_id, 'gce_multi_day_events' );
			} else {
				update_post_meta( $post_id, '_default_calendar_expand_multi_day_events', 'no' );
			}

			// Google Calendar ID.
			$google_id = get_post_meta( $post_id, 'gce_feed_url', true );
			update_post_meta( $post_id, '_google_calendar_id', base64_encode( trim( $google_id ) ) );
			delete_post_meta( $post_id, 'gce_feed_url' );

			// Google max results.
			update_post_meta( $post_id, '_google_events_max_results', 2500 );

			// Google calendar feed search terms.
			$google_search = get_post_meta( $post_id, 'gce_search_query', true );
			if ( ! empty( $google_search ) ) {
				update_post_meta( $post_id, '_google_events_search_query', trim( $google_search ) );
				delete_post_meta( $post_id, 'gce_search_query' );
			}

			// Google recurring events.
			if ( get_post_meta( $post_id, 'gce_expand_recurring', true ) ) {
				update_post_meta( $post_id, '_google_events_recurring', 'show' );
				delete_post_meta( $post_id, 'gce_expand_recurring' );
			} else {
				update_post_meta( $post_id, '_google_events_recurring', 'first-only' );
			}

			// Date and time format.
			$date_format = get_post_meta( $post_id, 'gce_date_format', true );
			if ( ! empty( $date_format ) ) {
				update_post_meta( $post_id, '_calendar_date_format_setting', 'use_custom_php' );
				update_post_meta( $post_id, '_calendar_date_format_php', $date_format );
				delete_post_meta( $post_id, 'gce_date_format' );
			} else {
				update_post_meta( $post_id, '_calendar_date_format_setting', 'use_site' );
			}
			$time_format = get_post_meta( $post_id, 'gce_time_format', true );
			if ( ! empty( $time_format ) ) {
				update_post_meta( $post_id, '_calendar_time_format_setting', 'use_custom_php' );
				update_post_meta( $post_id, '_calendar_time_format_php', $time_format );
				delete_post_meta( $post_id, 'gce_time_format' );
			} else {
				update_post_meta( $post_id, '_calendar_time_format_setting', 'use_site' );
			}
			update_post_meta( $post_id, '_calendar_datetime_separator', '@' );
			update_post_meta( $post_id, '_calendar_week_starts_on_setting', 'use_site' );

			// Feed transient cache duration.
			$cache = get_post_meta( $post_id, 'gce_cache', true );
			if ( is_numeric( $cache ) ) {
				$seconds = absint( $cache );
				if ( $seconds < 3600 ) {
					$amount = $seconds / 60;
					$unit   = 60;
				} elseif ( $seconds < 86400 ) {
					$amount = $seconds / 3600;
					$unit   = 3600;
				} elseif ( $seconds < 604800 ) {
					$amount = $seconds / 86400;
					$unit   = 86400;
				} else {
					$amount = $seconds / 604800;
					$unit   = 604800;
				}
				$amount = max( ceil( $amount ), 1 );
				update_post_meta( $post_id, '_feed_cache_user_unit', $unit );
				update_post_meta( $post_id, '_feed_cache_user_amount', $amount );
				update_post_meta( $post_id, '_feed_cache', $unit * $amount );
			} else {
				update_post_meta( $post_id, '_feed_cache_user_unit', 2 );
				update_post_meta( $post_id, '_feed_cache_user_amount', 3600 );
				update_post_meta( $post_id, '_feed_cache', 7200 );
			}
			delete_post_meta( $post_id, 'gce_cache' );

			// Legacy fields.
			delete_post_meta( $post_id, 'gce_retrieve_from' );
			delete_post_meta( $post_id, 'gce_retrieve_until' );
			delete_post_meta( $post_id, 'gce_per_page_from' );
			delete_post_meta( $post_id, 'gce_per_page_to' );
			delete_post_meta( $post_id, 'gce_list_start_offset_num' );
			delete_post_meta( $post_id, 'gce_list_start_offset_direction' );
			delete_post_meta( $post_id, 'gce_show_tooltips' );
			delete_post_meta( $post_id, 'gce_custom_from' );
			delete_post_meta( $post_id, 'gce_custom_until' );
			delete_post_meta( $post_id, 'gce_display_start' );
			delete_post_meta( $post_id, 'gce_display_start_text' );
			delete_post_meta( $post_id, 'gce_display_end' );
			delete_post_meta( $post_id, 'gce_display_end_text' );
			delete_post_meta( $post_id, 'gce_display_separator' );
			delete_post_meta( $post_id, 'gce_display_location' );
			delete_post_meta( $post_id, 'gce_display_location_text' );
			delete_post_meta( $post_id, 'gce_display_description' );
			delete_post_meta( $post_id, 'gce_display_description_text' );
			delete_post_meta( $post_id, 'gce_display_description_max' );
			delete_post_meta( $post_id, 'gce_display_link' );
			delete_post_meta( $post_id, 'gce_display_link_tab' );
			delete_post_meta( $post_id, 'gce_display_link_text' );
			delete_post_meta( $post_id, 'gce_display_simple' );

			// Post updated.
			update_post_meta( $post_id, '_calendar_version', '3.0.0' );
		}
	}

	/**
	 * Update post type slug.
	 */
	public function update_post_type() {

		global $wpdb;
		$table = $wpdb->prefix . 'posts';

		$wpdb->query(
			"
UPDATE {$table} SET `post_type`='calendar' WHERE `post_type`='gce_feed';
"
		);
	}

	/**
	 * Update options.
	 */
	public function update_options() {

		$old_settings          = get_option( 'gce_settings_general' );
		$new_settings_feeds    = get_option( 'simple-calendar_settings_feeds' );
		$new_settings_advanced = get_option( 'simple-calendar_settings_advanced' );

		// If empty probably using a legacy hardcoded key (no longer recommended).
		$new_settings_feeds['google']['api_key'] = ! empty( $old_settings['api_key'] ) ? $old_settings['api_key'] : $this->legacy_api_key;
		update_option( 'simple-calendar_settings_feeds', $new_settings_feeds );

		$new_settings_advanced['assets']['disable_css'] = ! empty( $old_settings['disable_css'] ) ? 'yes' : '';
		update_option( 'simple-calendar_settings_advanced', $new_settings_advanced );

		// Delete legacy options.
		delete_option( 'gce_version' );
		delete_option( 'gce_options' );
		delete_option( 'gce_upgrade_has_run' );
	}

	/**
	 * Update widgets.
	 */
	public function update_widgets() {

		$old_widgets = get_option( 'widget_gce_widget' );

		if ( ! empty( $old_widgets ) && is_array( $old_widgets ) ) {

			$new_widgets = array();

			foreach( $old_widgets as $i => $old_widget ) {
				if ( isset( $old_widget['id'] ) ) {

					$id = absint( substr( $old_widget['id'], 0, strspn( $old_widget['id'], '0123456789' ) ) );

					if ( $id > 0 ) {
						$new_widgets[ $i ]['title']       = isset( $old_widget['name'] ) ? $old_widget['name'] : 'Simple Calendar';
						$new_widgets[ $i ]['calendar_id'] = $id;
					}
				}
			}

			if ( ! empty( $new_widgets ) ) {
				update_option( 'widget_gce_widget', $new_widgets );
			}
		}
	}

	/**
	 * Convert legacy range date.
	 *
	 * Converts US format m/d/Y to international ISO 8601 Y-m-d.
	 *
	 * @param  string $date
	 *
	 * @return string
	 */
	private function convert_legacy_range( $date ) {
		$date = empty( $date ) ? date( 'm/d/Y', time() ) : $date;
		$timestamp = Carbon::createFromFormat( 'm/d/Y', $date )->getTimestamp();
		return date( 'Y-m-d', $timestamp );
	}

}
