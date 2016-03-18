<?php
/**
 * Default Calendar - Admin
 *
 * @package    SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Calendars\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Calendar feed admin.
 *
 * @since 3.0.0
 */
class Default_Calendar_Admin {

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		if ( simcal_is_admin_screen() !== false ) {
			add_action( 'simcal_settings_meta_calendar_panel', array( $this, 'add_settings_meta_calendar_panel' ), 10, 1 );
		}
		add_action( 'simcal_process_settings_meta', array( $this, 'process_meta' ), 10, 1 );
	}

	/**
	 * Feed settings page fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function settings_fields() {
		/*return array(
			'name' => __( 'Default Calendar', 'google-calendar-events' ),
			'description' => '',
			'fields' => array(
				'theme' => array(
					'type'      => 'select',
					'title'     => __( 'Default theme', 'google-calendar-events' ),
					'default'   => 'light',
					'options'   => array(
						'light' => __( 'Light', 'google-calendar-events' ),
						'dark'  => __( 'Dark', 'google-calendar-events' ),
					),
				),
				'today_color' => array(
					'type'        => 'standard',
					'subtype'     => 'color-picker',
					'title'       => __( 'Today default color', 'google-calendar-events' ),
					'default'   => '#FF0000',
				),
				'days_events_color' => array(
					'type'      => 'standard',
					'subtype'   => 'color-picker',
					'title'     => __( 'Days with events color', 'google-calendar-events' ),
					'default'   => '#000000',
				),
			),
		);*/

		return array();
	}

	/**
	 * Extend the calendar section panel of the settings meta box.
	 *
	 * @since  3.0.0
	 *
	 * @param int $post_id
	 */
	public function add_settings_meta_calendar_panel( $post_id ) {

		?>
		<table id="default-calendar-settings">
			<thead>
			<tr><th colspan="2"><?php _e( 'Default calendar', 'google-calendar-events' ); ?></th></tr>
			</thead>
			<tbody class="simcal-panel-section">

			<tr class="simcal-panel-field simcal-default-calendar-grid" style="display: none;">
				<th><label for="_default_calendar_event_bubbles_action"><?php _e( 'Event bubbles', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$bubbles = get_post_meta( $post_id, '_default_calendar_event_bubble_trigger', true );

					simcal_print_field( array(
						'type'    => 'radio',
						'inline'  => 'inline',
						'name'    => '_default_calendar_event_bubble_trigger',
						'id'      => '_default_calendar_event_bubble_trigger',
						'tooltip' => __( 'Open event bubbles in calendar grid by clicking or hovering on event titles. On mobile devices it will always default to tapping.', 'google-calendar-events' ),
						'value'   => $bubbles ? $bubbles : 'hover',
						'default' => 'hover',
						'options' => array(
							'click' => __( 'Click', 'google-calendar-events' ),
							'hover' => __( 'Hover', 'google-calendar-events' ),
						),
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field simcal-default-calendar-grid" style="display: none;">
				<th><label for="_default_calendar_trim_titles"><?php _e( 'Trim event titles', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$trim = get_post_meta( $post_id, '_default_calendar_trim_titles', true );

					simcal_print_field( array(
						'type'        => 'checkbox',
						'name'        => '_default_calendar_trim_titles',
						'id'          => '_default_calendar_trim_titles',
						'class'       => array(
							'simcal-field-show-next',
						),
						'value'       => 'yes' == $trim ? 'yes' : 'no',
						'attributes'  => array(
							'data-show-next-if-value' => 'yes',
						),
					) );

					simcal_print_field( array(
						'type'       => 'standard',
						'subtype'    => 'number',
						'name'       => '_default_calendar_trim_titles_chars',
						'id'         => '_default_calendar_trim_titles_chars',
						'tooltip'    => __( 'Shorten event titles in calendar grid to a specified length in characters.', 'google-calendar-events' ),
						'class'      => array(
							'simcal-field-tiny',
						),
						'value'      => 'yes' == $trim ? strval( max( absint( get_post_meta( $post_id, '_default_calendar_trim_titles_chars', true ) ), 1 ) ) : '20',
						'attributes' => array(
							'min'     => '1',
						),
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field simcal-default-calendar-list" style="display: none;">
				<th><label for="_default_calendar_list_grouped_span"><?php _e( 'Span', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$list_span = max( absint( get_post_meta( $post_id, '_default_calendar_list_range_span', true ) ), 1 );

					simcal_print_field( array(
						'type'    => 'standard',
						'subtype' => 'number',
						'name'    => '_default_calendar_list_range_span',
						'id'      => '_default_calendar_list_range_span',
						'class'   => array(
							'simcal-field-tiny',
							'simcal-field-inline',
						),
						'value'   => strval( $list_span ),
						'attributes'  => array(
							'min' => '1',
						),
					) );

					$list_type = get_post_meta( $post_id, '_default_calendar_list_range_type', true );

					simcal_print_field( array(
						'type'    => 'select',
						'name'    => '_default_calendar_list_range_type',
						'id'      => '_default_calendar_list_range_type',
						'tooltip' => __( 'Range of events to show on each calendar page.', 'google-calendar-events' ),
						'class'   => array(
							'simcal-field-inline',
						),
						'value'   => $list_type,
						'options' => array(
							'monthly' => __( 'Month(s)', 'google-calendar-events' ),
							'weekly'  => __( 'Week(s)', 'google-calendar-events' ),
							'daily'   => __( 'Day(s)', 'google-calendar-events' ),
							'events'  => __( 'Event(s)', 'google-calendar-events' ),
						),
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field simcal-default-calendar-list" style="display: none;">
				<th><label for="_default_calendar_list_header"><?php _e( 'Hide header', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$header = get_post_meta( $post_id, '_default_calendar_list_header', true );

					simcal_print_field( array(
						'type'    => 'checkbox',
						'name'    => '_default_calendar_list_header',
						'id'      => '_default_calendar_list_header',
						'tooltip' => __( 'You can use this to hide the month header for this calendar.', 'google-calendar-events' ),
						'value'   => 'yes' == $header ? 'yes' : 'no',
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field simcal-default-calendar-list" style="display: none;">
				<th><label for="_default_calendar_compact_list"><?php _e( 'Compact list', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$compact = get_post_meta( $post_id, '_default_calendar_compact_list', true );

					simcal_print_field( array(
						'type'    => 'checkbox',
						'name'    => '_default_calendar_compact_list',
						'id'      => '_default_calendar_compact_list',
						'tooltip' => __( 'Make an events list more compact by grouping together events from different days in a single list.', 'google-calendar-events' ),
						'value'   => 'yes' == $compact ? 'yes' : 'no',
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field simcal-default-calendar-grid simcal-default-calendar-list"  style="display: none;">
				<th><label for="_default_calendar_limit_visible_events"><?php _e( 'Limit visible events', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$limit = get_post_meta( $post_id, '_default_calendar_limit_visible_events', true );

					simcal_print_field( array(
						'type'        => 'checkbox',
						'name'        => '_default_calendar_limit_visible_events',
						'id'          => '_default_calendar_limit_visible_events',
						'value'       => 'yes' == $limit ? 'yes' : 'no',
						'class'       => array(
							'simcal-field-show-next',
						),
						'attributes'  => array(
							'data-show-next-if-value' => 'yes',
						)
					) );

					$visible_events = absint( get_post_meta( $post_id, '_default_calendar_visible_events', true ) );
					$visible_events = $visible_events > 0 ? $visible_events : 3;

					simcal_print_field( array(
						'type'       => 'standard',
						'subtype'    => 'number',
						'name'       => '_default_calendar_visible_events',
						'id'         => '_default_calendar_visible_events',
						'tooltip'    => __( 'Limit the number of initial visible events on each day to a set maximum.', 'google-calendar-events' ),
						'class'      => array(
							'simcal-field-tiny',
						),
						'value'      => $visible_events,
						'attributes' => array(
							'min'     => '1',
						)
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field simcal-default-calendar-grid simcal-default-calendar-list" style="display: none;">
				<th><label for="_default_calendar_event_bubbles_action"><?php _e( 'Expand multi-day events', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$post_meta = get_post_meta( $post_id );

					if ( ! is_array( $post_meta ) && ! empty( $post_meta ) ) {
						$multi_day_value = 'current_day_only';
					} else {
						$multi_day_value = get_post_meta( $post_id, '_default_calendar_expand_multi_day_events', true );
					}

					simcal_print_field( array(
						'type'    => 'select',
						'name'    => '_default_calendar_expand_multi_day_events',
						'id'      => '_default_calendar_expand_multi_day_events',
						'tooltip' => __( 'For events spanning multiple days, you can display them on each day of the event, ' .
						                 'only on the first day of the event, or on all days of the event, but only up to the current day. ' .
						                 'Third option applies to list views only.', 'google-calendar-events' ),
						'value'   => $multi_day_value,
						'options' => array(
							'yes'              => __( 'Yes, display on all days of event', 'google-calendar-events' ),
							'no'               => __( 'No, display only on first day of event', 'google-calendar-events' ),
							'current_day_only' => __( 'No, display on all days of event up to current day (list view only)', 'google-calendar-events' ),
						),
						'default' => 'current_day_only',
					) );

					?>
				</td>
			</tr>
			</tbody>
			<?php

			$settings                   = get_option( 'simple-calendar_settings_calendars' );
			$default_theme              = isset( $settings['default-calendar']['theme'] ) ? $settings['default-calendar']['theme'] : 'light';
			$default_today_color        = /*isset( $settings['default-calendar']['today_color'] ) ? $settings['default-calendar']['today_color'] :*/ '#1e73be';
			$default_days_events_color  = /*isset( $settings['default-calendar']['days_events_color'] ) ? $settings['default-calendar']['days_events_color'] :*/ '#000000';

			?>
			<tbody class="simcal-panel-section">
			<tr class="simcal-panel-field simcal-default-calendar-grid simcal-default-calendar-list" style="display: none;">
				<th><label for="_default_calendar_style_theme"><?php _e( 'Theme', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$saved = get_post_meta( $post_id, '_default_calendar_style_theme', true );
					$value = ! $saved ? $default_theme : $saved;

					simcal_print_field( array(
						'type'    => 'select',
						'name'    => '_default_calendar_style_theme',
						'id'      => '_default_calendar_style_theme',
						'value'   => $value,
						'tooltip' => __( 'Choose a calendar theme to match your site theme.', 'google-calendar-events' ),
						'options' => array(
							'light' => __( 'Light', 'google-calendar-events' ),
							'dark' => __( 'Dark', 'google-calendar-events' ),
						),
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field simcal-default-calendar-grid simcal-default-calendar-list" style="display: none;">
				<th><label for="_default_calendar_style_today"><?php _e( 'Today', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$saved = get_post_meta( $post_id, '_default_calendar_style_today', true );
					$value = ! $saved ? $default_today_color : $saved;

					simcal_print_field( array(
						'type'    => 'standard',
						'subtype' => 'color-picker',
						'name'    => '_default_calendar_style_today',
						'id'      => '_default_calendar_style_today',
						'value'   => $value,
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field simcal-default-calendar-grid simcal-default-calendar-list" style="display: none;">
				<th><label for="_default_calendar_style_days_events"><?php _e( 'Days with events', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$saved = get_post_meta( $post_id, '_default_calendar_style_days_events', true );
					$value = ! $saved ? $default_days_events_color : $saved;

					simcal_print_field( array(
						'type'    => 'standard',
						'subtype' => 'color-picker',
						'name'    => '_default_calendar_style_days_events',
						'id'      => '_default_calendar_style_days_events',
						'value'   => $value,
					) );

					?>
				</td>
			</tr>

			</tbody>
			<?php

			?>
		</table>
		<?php

	}

	/**
	 * Process meta fields.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id
	 */
	public function process_meta( $post_id ) {

		// Theme.
		$theme = isset( $_POST['_default_calendar_style_theme'] ) ? sanitize_key( $_POST['_default_calendar_style_theme'] ) : 'light';
		update_post_meta( $post_id, '_default_calendar_style_theme', $theme );

		// Today color.
		$today_color = isset( $_POST['_default_calendar_style_today'] ) ? sanitize_text_field( $_POST['_default_calendar_style_today'] ) : '#FF000';
		update_post_meta( $post_id, '_default_calendar_style_today', $today_color );

		// Days with events color.
		$days_events_color = isset( $_POST['_default_calendar_style_days_events'] ) ? sanitize_text_field( $_POST['_default_calendar_style_days_events'] ) : '#000000';
		update_post_meta( $post_id, '_default_calendar_style_days_events', $days_events_color );

		// List range span.
		$span = isset( $_POST['_default_calendar_list_range_span'] ) ? max( absint( $_POST['_default_calendar_list_range_span'] ), 1 ) : 1;
		update_post_meta( $post_id, '_default_calendar_list_range_span', $span );

		// List range type.
		$group = isset( $_POST['_default_calendar_list_range_type'] ) ? sanitize_key( $_POST['_default_calendar_list_range_type'] ) : 'monthly';
		update_post_meta( $post_id, '_default_calendar_list_range_type', $group );

		// Hide header.
		$header = isset( $_POST['_default_calendar_list_header'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_default_calendar_list_header', $header );

		// Compact list.
		$compact = isset( $_POST['_default_calendar_compact_list'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_default_calendar_compact_list', $compact );

		// Limit number of initially visible daily events.
		$limit = isset( $_POST['_default_calendar_limit_visible_events'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_default_calendar_limit_visible_events', $limit );
		$number = isset( $_POST['_default_calendar_visible_events'] ) ? absint( $_POST['_default_calendar_visible_events'] ) : 3;
		update_post_meta( $post_id, '_default_calendar_visible_events', $number );

		// Grid event bubbles action.
		$bubbles = isset( $_POST['_default_calendar_event_bubble_trigger'] ) ? esc_attr( $_POST['_default_calendar_event_bubble_trigger'] ) : 'hover';
		update_post_meta( $post_id, '_default_calendar_event_bubble_trigger', $bubbles );

		// Trim event titles characters length.
		$trim = isset( $_POST['_default_calendar_trim_titles'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_default_calendar_trim_titles', $trim );
		$chars = isset( $_POST['_default_calendar_trim_titles_chars'] ) ? max( absint( $_POST['_default_calendar_trim_titles_chars'] ), 1 ) : 20;
		update_post_meta( $post_id, '_default_calendar_trim_titles_chars', $chars );

		// Expand multiple day events on each day.
		$multi_day = isset( $_POST['_default_calendar_expand_multi_day_events'] ) && ! empty( $_POST['_default_calendar_expand_multi_day_events'] ) ? sanitize_key( $_POST['_default_calendar_expand_multi_day_events'] ) : 'current_day_only';
		update_post_meta( $post_id, '_default_calendar_expand_multi_day_events', $multi_day );

	}

}
