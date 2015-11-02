<?php
/**
 * Update to 3.0.0
 *
 * @package SimpleCalendar/Updates
 */
namespace SimpleCalendar\Updates;

use Carbon\Carbon;
use SimpleCalendar\Post_Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update to 3.0.0
 */
class Update_V300 {

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

		flush_rewrite_rules();
	}

	/**
	 * Update posts.
	 *
	 * @param $posts
	 */
	public function update_posts( $posts ) {

		foreach ( $posts as $post ) {

			$post_id = $post->ID;

			// Assign a feed taxonomy term (feed type) to legacy posts.
			wp_set_object_terms( $post_id, 'google', 'calendar_feed' );

			// Assign a calendar taxonomy term (calendar type) to legacy posts.
			wp_set_object_terms( $post_id, 'default-calendar', 'calendar_type' );

			// Convert legacy list/grid view to default calendar view.
			$display = get_post_meta( $post_id, 'gce_display_mode', true );
			$views = array();
			$range = false;
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

			// List calendar settings.
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

			$calendar_begins = 'today';

			// Custom calendar range.
			if ( $range === true ) {

				$begins = get_post_meta( $post_id, 'gce_feed_range_start', true );
				$ends   = get_post_meta( $post_id, 'gce_feed_range_end', true );

				if ( $begins && $ends ) {
					update_post_meta( $post_id, '_calendar_begins', 'custom_date' );
					update_post_meta( $post_id, '_calendar_begins_custom_date', $this->convert_legacy_range( $begins ) );
				} else {
					update_post_meta( $post_id, '_calendar_begins', $calendar_begins );
				}

			} else {

				// Legacy list calendars may have a start offset.
				$offset = absint( get_post_meta( $post_id, 'gce_list_start_offset_num', true ) );
				if ( 'list' == $display && $offset > 0 ) {
					$calendar_begins = 'back' == get_post_meta( $post_id, 'gce_list_start_offset_direction', true ) ? 'days_before' : 'days_after';
					update_post_meta( $post_id, '_calendar_begins_nth', $offset );
				}

				update_post_meta( $post_id, '_calendar_begins', $calendar_begins );
			}

			// Earliest event.
			$start_before = get_post_meta( $post_id, 'gce_feed_start', true );
			$start_amt = absint( get_post_meta( $post_id, 'gce_feed_start_num', true ) );
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

			// Static calendar.
			if ( false === get_post_meta( $post_id, 'gce_paging', true ) ) {
				update_post_meta( $post_id, '_calendar_is_static', 'yes' );
			}

			// Default calendar bubble trigger (click was unavailable before 3.0.0).
			update_post_meta( $post_id, '_default_calendar_event_bubble_trigger', 'hover' );

			// Default calendar multiple day events.
			if ( get_post_meta( $post_id, 'gce_multi_day_events', true ) ) {
				update_post_meta( $post_id, '_default_calendar_expand_multi_day_events', 'yes' );
			} else {
				update_post_meta( $post_id, '_default_calendar_expand_multi_day_events', 'no' );
			}

			// Google Calendar ID.
			$google_id = get_post_meta( $post_id, 'gce_feed_url', true );
			update_post_meta( $post_id, '_google_calendar_id', base64_encode( trim( $google_id ) ) );

			// Google max results.
			update_post_meta( $post_id, '_google_events_max_results', 2500 );

			// Google calendar feed search terms.
			$google_search = get_post_meta( $post_id, 'gce_search_query', true );
			if ( ! empty( $google_search ) ) {
				update_post_meta( $post_id, '_google_events_search_query', trim( $google_search ) );
			}

			// Google recurring events.
			if ( get_post_meta( $post_id, 'gce_expand_recurring', true ) ) {
				update_post_meta( $post_id, '_google_events_recurring', 'show' );
			} else {
				update_post_meta( $post_id, '_google_events_recurring', 'first-only' );
			}

			// Date and time format.
			$date_format = get_post_meta( $post_id, 'gce_date_format', true );
			if ( ! empty( $date_format ) ) {
				update_post_meta( $post_id, '_calendar_date_format_setting', 'use_custom_php' );
				update_post_meta( $post_id, '_calendar_date_format_php', $date_format );
			} else {
				update_post_meta( $post_id, '_calendar_date_format_setting', 'use_site' );
			}
			$time_format = get_post_meta( $post_id, 'gce_time_format', true );
			if ( ! empty( $time_format ) ) {
				update_post_meta( $post_id, '_calendar_time_format_setting', 'use_custom_php' );
				update_post_meta( $post_id, '_calendar_time_format_php', $time_format );
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

			$this->delete_post_meta( $post_id );

			// Post updated.
			update_post_meta( $post_id, '_calendar_version', '3.0.0' );
		}

	}

	/**
	 * Delete legacy post meta.
	 *
	 * @param int $post_id
	 */
	public function delete_post_meta( $post_id ) {

		$post_meta = array(
			'gce_cache',
			'gce_custom_from',
			'gce_custom_until',
			'gce_date_format',
			'gce_display_description',
			'gce_display_description_max',
			'gce_display_description_text',
			'gce_display_end',
			'gce_display_end_text',
			'gce_display_link',
			'gce_display_link_tab',
			'gce_display_link_text',
			'gce_display_location',
			'gce_display_location_text',
			'gce_display_mode',
			'gce_display_separator',
			'gce_display_simple',
			'gce_display_start',
			'gce_display_start_text',
			'gce_events_per_page',
			'gce_expand_recurring',
			'gce_feed_end',
			'gce_feed_end_custom',
			'gce_feed_end_num',
			'gce_feed_range_end',
			'gce_feed_range_start',
			'gce_feed_start',
			'gce_feed_start_custom',
			'gce_feed_start_num',
			'gce_feed_url',
			'gce_feed_use_range',
			'gce_list_start_offset_direction',
			'gce_list_start_offset_num',
			'gce_multi_day_events',
			'gce_paging',
			'gce_per_page_from',
			'gce_per_page_num',
			'gce_per_page_to',
			'gce_retrieve_from',
			'gce_retrieve_until',
			'gce_search_query',
			'gce_show_tooltips',
			'gce_time_format',
		);

		foreach ( $post_meta as $meta_key ) {
			delete_post_meta( $post_id, $meta_key );
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
		$new_settings_feeds['google']['api_key'] = ! empty( $old_settings['api_key'] ) ? $old_settings['api_key'] : '';
		update_option( 'simple-calendar_settings_feeds', $new_settings_feeds );

		$new_settings_advanced['assets']['disable_css'] = ! empty( $old_settings['disable_css'] ) ? 'yes' : '';
		update_option( 'simple-calendar_settings_advanced', $new_settings_advanced );

		// Delete legacy options.
		delete_option( 'gce_version' );
		delete_option( 'gce_options' );
		delete_option( 'gce_upgrade_has_run' );
		delete_option( 'gce_v240_update_notices' );
		delete_option( 'gce_show_admin_install_notice' );
	}

	/**
	 * Update widgets.
	 */
	public function update_widgets() {

		$old_widgets = get_option( 'widget_gce_widget' );

		if ( ! empty( $old_widgets ) && is_array( $old_widgets ) ) {

			$new_widgets = array();

			foreach ( $old_widgets as $i => $old_widget ) {
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
