<?php
/**
 * Calendar Feed Settings Meta Box
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Metaboxes;

use SimpleCalendar\Abstracts\Meta_Box;
use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Feed;
use SimpleCalendar\Abstracts\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calendar feed settings.
 *
 * Meta box for handling an individual feed settings.
 *
 * @since 3.0.0
 */
class Settings implements Meta_Box {

	/**
	 * Output the meta box markup.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Post $post
	 */
	public static function html( $post ) {

		// @see Meta_Boxes::save_meta_boxes()
		wp_nonce_field( 'simcal_save_data', 'simcal_meta_nonce' );

		?>
		<div class="simcal-panels-wrap">

			<span class="simcal-box-handle">
				<?php self::settings_handle( $post ); ?>
			</span>

			<ul class="simcal-tabs">
				<?php self::settings_tabs( $post ); ?>
				<?php do_action( 'simcal_settings_meta_tabs' ); ?>
			</ul>

			<div class="simcal-panels">
				<div id="events-settings-panel" class="simcal-panel">
					<?php self::events_settings_panel( $post ); ?>
					<?php do_action( 'simcal_settings_meta_events_panel', $post->ID ); ?>
				</div>
				<div id="calendar-settings-panel" class="simcal-panel">
					<?php do_action( 'simcal_settings_meta_calendar_panel', $post->ID ); ?>
					<?php self::calendar_settings_panel( $post ); ?>
				</div>
				<?php
				// Hook for additional settings panels.
				do_action( 'simcal_settings_meta_panels', $post->ID );
				// Thus advanced panel is always the last one:
				?>
				<div id="advanced-settings-panel" class="simcal-panel">
					<?php self::advanced_settings_panel( $post ) ?>
					<?php do_action( 'simcal_settings_meta_advanced_panel', $post->ID ); ?>
				</div>
			</div>

			<div class="clear">
			</div>

		</div>
		<?php

	}

	/**
	 * Print the meta box settings handle.
	 *
	 * @since  3.0.0
	 * @access  private
	 *
	 * @param \WP_Post $post
	 */
	private static function settings_handle( $post ) {

		$feed_options = $calendar_options = $calendar_views = array();

		$feed_types = simcal_get_feed_types();
		foreach ( $feed_types as $feed_type ) {

			$feed = simcal_get_feed( $feed_type );

			if ( $feed instanceof Feed ) {
				$feed_options[ $feed_type ] = $feed->name;
			}
		}

		$calendar_types = simcal_get_calendar_types();
		foreach ( $calendar_types as $calendar_type => $views ) {

			$calendar = simcal_get_calendar( $calendar_type );

			if ( $calendar instanceof Calendar ) {
				$calendar_options[ $calendar_type ] = $calendar->name;
				$calendar_views[ $calendar_type ]   = $calendar->views;
			}
		}

		if ( $feed_options ) {

			if ( $feed_types = wp_get_object_terms( $post->ID, 'calendar_feed' ) ) {
				$feed_type = sanitize_title( current( $feed_types )->name );
			} else {
				$feed_type = apply_filters( 'simcal_default_feed_type', 'google' );
			}

			?>
			<label for="_feed_type"><span><?php _e( 'Event Source', 'google-calendar-events' ); ?></span>
				<select name="_feed_type" id="_feed_type">
					<optgroup label="<?php _ex( 'Get events from', 'From which calendar source to load events from', 'google-calendar-events' ) ?>">
						<?php foreach ( $feed_options as $feed => $name ) { ?>
							<option value="<?php echo $feed; ?>" <?php selected( $feed, $feed_type, true ); ?>><?php echo $name; ?></option>
						<?php } ?>
					</optgroup>
				</select>
			</label>
			<?php

		}

		if ( $calendar_options ) {

			if ( $calendar_types = wp_get_object_terms( $post->ID, 'calendar_type' ) ) {
				$calendar_type = sanitize_title( current( $calendar_types )->name );
			} else {
				$calendar_type = apply_filters( 'simcal_default_calendar_type', 'default-calendar' );
			}

			?>
			<label for="_calendar_type"><span><?php _e( 'Calendar Type', 'google-calendar-events' ); ?></span>
				<select name="_calendar_type" id="_calendar_type">
					<optgroup label="<?php _e( 'Calendar to use', 'google-calendar-events' ); ?>">
						<?php foreach ( $calendar_options as $calendar => $name ) { ?>
							<option value="<?php echo $calendar; ?>" <?php selected( $calendar, $calendar_type, true ); ?>><?php echo $name; ?></option>
						<?php } ?>
					</optgroup>
				</select>
			</label>
			<?php

			if ( $calendar_views ) {

				$calendar_view = get_post_meta( $post->ID, '_calendar_view', true );

				foreach ( $calendar_views as $calendar => $views ) {

					$calendar_type_view = isset( $calendar_view[ $calendar ] ) ? $calendar_view[ $calendar ] : '';

					?>
					<label for="_calendar_view_<?php echo $calendar; ?>"><span><?php _e( 'View', 'google-calendar-events' ); ?></span>
						<select name="_calendar_view[<?php echo $calendar; ?>]" id="_calendar_view_<?php echo $calendar; ?>">
							<optgroup label="<?php _e( 'View to display', 'google-calendar-events' ); ?>">
								<?php foreach ( $views as $view => $name ) { ?>
									<option value="<?php echo $view; ?>" <?php selected( $view, $calendar_type_view, true ); ?>><?php echo $name; ?></option>
								<?php } ?>
							</optgroup>
						</select>
					</label>
					<?php

				}
			}
		}
	}

	/**
	 * Print settings tabs.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  \WP_Post $post
	 */
	private static function settings_tabs( $post ) {

		// Hook to add more tabs.
		$tabs = apply_filters( 'simcal_settings_meta_tabs_li', array(
			'events' => array(
				'label'   => __( 'Events', 'google-calendar-events' ),
				'target'  => 'events-settings-panel',
				'class'   => array( 'active' ),
				'icon'    => 'simcal-icon-event',
			),
			'calendar' => array(
				'label'  => __( 'Appearance', 'google-calendar-events' ),
				'target' => 'calendar-settings-panel',
				'class'  => array(),
				'icon'   => 'simcal-icon-calendar',
			),
		), $post->ID );

		// Always keep advanced tab as the last one.
		$tabs['advanced'] = array(
			'label'   => __( 'Advanced', 'google-calendar-events' ),
			'target'  => 'advanced-settings-panel',
			'class'   => array(),
			'icon'    => 'simcal-icon-settings',
		);

		// Output the tabs as list items.
		foreach ( $tabs as $key => $tab ) {

			if ( isset( $tab['target'] ) && isset( $tab['label'] ) ) {

				$icon  = $tab['icon'] ? $tab['icon'] : 'simcal-icon-panel';
				$class = $tab['class'] ? $tab['class'] : array();

				echo '<li class="' . $key . '-settings ' . $key . '-tab ' . implode( ' ', $class ) . '" data-tab="' . $key . '">';
				echo    '<a href="#' . $tab['target'] . '"><i class="' . $icon . '" ></i> <span>' . esc_html( $tab['label'] ) . '</span></a>';
				echo '</li>';
			}
		}
	}

	/**
	 * Print events settings panel.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  \WP_Post $post
	 */
	private static function events_settings_panel( $post ) {

		?>
		<table>
			<thead>
			<tr><th colspan="2"><?php _e( 'Event Settings', 'google-calendar-events' ); ?></th></tr>
			</thead>
			<tbody class="simcal-panel-section simcal-panel-section-events-range">
			<tr class="simcal-panel-field">
				<th><label for="_calendar_begins"><?php _e( 'Calendar Start', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$calendar_begins = esc_attr( get_post_meta( $post->ID, '_calendar_begins', true ) );
					$calendar_begins_nth = max( absint( get_post_meta( $post->ID, '_calendar_begins_nth', true ) ), 1 );
					$calendar_begins_nth_show = in_array( $calendar_begins, array(
						'days_before',
						'days_after',
						'weeks_before',
						'weeks_after',
						'months_before',
						'months_after',
						'years_before',
						'years_after',
					) );

					simcal_print_field( array(
						'type'       => 'standard',
						'subtype'    => 'number',
						'name'       => '_calendar_begins_nth',
						'id'         => '_calendar_begins_nth',
						'value'      => strval( $calendar_begins_nth ),
						'attributes' => array(
							'min' => '1',
						),
						'class'   => array(
							'simcal-field-inline',
							'simcal-field-tiny',
						),
						'style'      => ! $calendar_begins_nth_show ? array( 'display' => 'none' ) : '',
					) );

					?>
					<select name="_calendar_begins"
							id="_calendar_begins"
							class="simcal-field simcal-field-select simcal-field-inline simcal-field-switch-other">
						<optgroup label="<?php _e( 'Days', 'google-calendar-events' ); ?>">
							<option value="today"
									data-hide-fields="_calendar_begins_custom_date,_calendar_begins_nth"
								<?php selected( 'today', $calendar_begins, true ); ?>><?php _e( 'Today', 'google-calendar-events' ); ?></option>
							<option value="now"
									data-hide-fields="_calendar_begins_custom_date,_calendar_begins_nth"
								<?php selected( 'now', $calendar_begins, true ); ?>><?php _e( 'Now', 'google-calendar-events' ); ?></option>
							<option value="days_before"
									data-hide-field="_calendar_begins_custom_date"
									data-show-field="_calendar_begins_nth" <?php selected( 'days_before', $calendar_begins, true ); ?>><?php _e( 'Day(s) before today', 'google-calendar-events' ); ?></option>
							<option value="days_after"
									data-hide-field="_calendar_begins_custom_date"
									data-show-field="_calendar_begins_nth" <?php selected( 'days_after', $calendar_begins, true ); ?>><?php _e( 'Day(s) after today', 'google-calendar-events' ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'Weeks', 'google-calendar-events' ); ?>">
							<option value="this_week"
									data-hide-fields="_calendar_begins_custom_date,_calendar_begins_nth"
								<?php selected( 'this_week', $calendar_begins, true ); ?>><?php _e( 'This week', 'google-calendar-events' ); ?></option>
							<option value="weeks_before"
									data-hide-field="_calendar_begins_custom_date"
									data-show-field="_calendar_begins_nth" <?php selected( 'weeks_before', $calendar_begins, true ); ?>><?php _e( 'Week(s) before current', 'google-calendar-events' ); ?></option>
							<option value="weeks_after"
									data-hide-field="_calendar_begins_custom_date"
									data-show-field="_calendar_begins_nth" <?php selected( 'weeks_after', $calendar_begins, true ); ?>><?php _e( 'Week(s) after current', 'google-calendar-events' ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'Months', 'google-calendar-events' ); ?>">
							<option value="this_month"
									data-hide-fields="_calendar_begins_custom_date,_calendar_begins_nth"
								<?php selected( 'this_month', $calendar_begins, true ); ?>><?php _e( 'This month', 'google-calendar-events' ); ?></option>
							<option value="months_before"
									data-hide-field="_calendar_begins_custom_date"
									data-show-field="_calendar_begins_nth" <?php selected( 'months_before', $calendar_begins, true ); ?>><?php _e( 'Month(s) before current', 'google-calendar-events' ); ?></option>
							<option value="months_after"
									data-hide-field="_calendar_begins_custom_date"
									data-show-field="_calendar_begins_nth" <?php selected( 'months_after', $calendar_begins, true ); ?>><?php _e( 'Month(s) after current', 'google-calendar-events' ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'Years', 'google-calendar-events' ); ?>">
							<option value="this_year"
									data-hide-fields="_calendar_begins_custom_date,_calendar_begins_nth"
								<?php selected( 'this_year', $calendar_begins, true ); ?>><?php _e( 'This year', 'google-calendar-events' ); ?></option>
							<option value="years_before"
									data-show-field="_calendar_begins_nth" <?php selected( 'years_before', $calendar_begins, true ); ?>><?php _e( 'Year(s) before current', 'google-calendar-events' ); ?></option>
							<option value="years_after"
									data-hide-field="_calendar_begins_custom_date"
									data-show-field="_calendar_begins_nth" <?php selected( 'years_after', $calendar_begins, true ); ?>><?php _e( 'Year(s) after current', 'google-calendar-events' ); ?></option>
						</optgroup>
						<optgroup label="<?php _e( 'Other', 'google-calendar-events' ); ?>">
							<option value="custom_date"
									data-hide-field="_calendar_begins_nth"
									data-show-field="_calendar_begins_custom_date" <?php selected( 'custom_date', $calendar_begins, true ); ?>><?php _e( 'Specific date', 'google-calendar-events' ); ?></option>
						</optgroup>
					</select>
					<?php

					simcal_print_field( array(
						'type'    => 'date-picker',
						'name'    => '_calendar_begins_custom_date',
						'id'      => '_calendar_begins_custom_date',
						'value'   => get_post_meta( $post->ID, '_calendar_begins_custom_date', true ),
						'class' => array(
							'simcal-field-inline',
						),
						'style'   => 'custom_date' != $calendar_begins ? array( 'display' => 'none' ) : '',
					) );

					?>
					<i class="simcal-icon-help simcal-help-tip"
					   data-tip="<?php _e( 'The calendar default opening date. It will automatically adapt based on the chosen calendar type.', 'google-calendar-events' ); ?>"></i>
				</td>
			</tr>
			<tr class="simcal-panel-field">
				<th><label for="_feed_earliest_event_date"><?php _e( 'Earliest Event', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$earliest_event_saved = get_post_meta( $post->ID, '_feed_earliest_event_date', true );
					$earliest_event = false == $earliest_event_saved ? 'months_before' : esc_attr( $earliest_event_saved );

					simcal_print_field( array(
						'type'       => 'standard',
						'subtype'    => 'number',
						'name'       => '_feed_earliest_event_date_range',
						'id'         => '_feed_earliest_event_date_range',
						'value'      => strval( max( absint( get_post_meta( $post->ID, '_feed_earliest_event_date_range', true ) ), 1 ) ),
						'attributes' => array(
							'min' => '1',
						),
						'class'   => array(
							'simcal-field-inline',
							'simcal-field-tiny',
						),
						'style'      => ( 'now' != $earliest_event ) && ( 'today' != $earliest_event ) ? array( 'display' => 'none' ) : '',
					) );

					?>
					<select name="_feed_earliest_event_date"
							id="_feed_earliest_event_date"
							class="simcal-field simcal-field-select simcal-field-inline simcal-field-switch-other">
						<option value="calendar_start" data-hide-field="_feed_earliest_event_date_range" <?php selected( 'calendar_start', $earliest_event, true ); ?>><?php _e( 'Same as start date', 'google-calendar-events' ); ?></option>
						<option value="days_before"    data-show-field="_feed_earliest_event_date_range" <?php selected( 'days_before', $earliest_event, true ); ?>><?php _e( 'Day(s) before start date', 'google-calendar-events' ); ?></option>
						<option value="weeks_before"   data-show-field="_feed_earliest_event_date_range" <?php selected( 'weeks_before', $earliest_event, true ); ?>><?php _e( 'Week(s) before start date', 'google-calendar-events' ); ?></option>
						<option value="months_before"  data-show-field="_feed_earliest_event_date_range" <?php selected( 'months_before', $earliest_event, true ); ?>><?php _e( 'Month(s) before start date', 'google-calendar-events' ); ?></option>
						<option value="years_before"   data-show-field="_feed_earliest_event_date_range" <?php selected( 'years_before', $earliest_event, true ); ?>><?php _e( 'Year(s) before start date', 'google-calendar-events' ); ?></option>
					</select>
					<i class="simcal-icon-help simcal-help-tip"
					   data-tip="<?php _e( 'Set the date for the earliest possible event to show in calendar. There will not be events before this date.', 'google-calendar-events' ); ?>"></i>
				</td>
			</tr>
			<tr class="simcal-panel-field">
				<th><label for="_feed_latest_event_date"><?php _e( 'Latest Event', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$latest_event_saved = get_post_meta( $post->ID, '_feed_latest_event_date', true );
					$latest_event = false == $latest_event_saved ? 'years_after' : esc_attr( $latest_event_saved );

					simcal_print_field( array(
						'type'       => 'standard',
						'subtype'    => 'number',
						'name'       => '_feed_latest_event_date_range',
						'id'         => '_feed_latest_event_date_range',
						'value'      => strval( max( absint( get_post_meta( $post->ID, '_feed_latest_event_date_range', true ) ), 1 ) ),
						'attributes' => array(
							'min' => '1',
						),
						'class'      => array(
							'simcal-field-inline',
							'simcal-field-tiny',
						),
						'style'      => 'indefinite' != $latest_event ? array( 'display' => 'none' ) : '',
					) );

					?>
					<select name="_feed_latest_event_date"
							id="_feed_latest_event_date"
							class="simcal-field simcal-field-select simcal-field-inline simcal-field-switch-other">
						<option value="calendar_start" data-hide-field="_feed_latest_event_date_range" <?php selected( 'calendar_start', $earliest_event, true ); ?>><?php _e( 'Day end of start date', 'google-calendar-events' ); ?></option>
						<option value="days_after"     data-show-field="_feed_latest_event_date_range" <?php selected( 'days_after', $latest_event, true ); ?>><?php _e( 'Day(s) after start date', 'google-calendar-events' ); ?></option>
						<option value="weeks_after"    data-show-field="_feed_latest_event_date_range" <?php selected( 'weeks_after', $latest_event, true ); ?>><?php _e( 'Weeks(s) after start date', 'google-calendar-events' ); ?></option>
						<option value="months_after"   data-show-field="_feed_latest_event_date_range" <?php selected( 'months_after', $latest_event, true ); ?>><?php _e( 'Month(s) after start date', 'google-calendar-events' ); ?></option>
						<option value="years_after"    data-show-field="_feed_latest_event_date_range" <?php selected( 'years_after', $latest_event, true ); ?>><?php _e( 'Year(s) after start date', 'google-calendar-events' ); ?></option>
					</select>
					<i class="simcal-icon-help simcal-help-tip"
					   data-tip="<?php _e( 'Set the date for the latest possible event to show on calendar. There will not be events after this date.', 'google-calendar-events' ); ?>"></i>
				</td>
			</tr>
			</tbody>
		</table>
		<?php

	}

	/**
	 * Print the calendar settings panel.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  \WP_Post $post
	 */
	private static function calendar_settings_panel( $post ) {

		?>
		<table>
			<thead>
			<tr><th colspan="2"><?php _e( 'Miscellaneous', 'google-calendar-events' ); ?></th></tr>
			</thead>
			<tbody class="simcal-panel-section">
			<tr class="simcal-panel-field">
				<th><label for="_calendar_is_static"><?php _e( 'Static Calendar', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$fixed = get_post_meta( $post->ID, '_calendar_is_static', true );

					simcal_print_field( array(
						'type'    => 'checkbox',
						'name'    => '_calendar_is_static',
						'id'      => '_calendar_is_static',
						'tooltip' => __( 'Remove the navigation arrows and fix the calendar view to its initial state.', 'google-calendar-events' ),
						'value'   => 'yes' == $fixed ? 'yes' : 'no',
						'text'    => __( 'Yes (hide navigation arrows)', 'google-calendar-events' ),
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field">
				<th><label for="_no_events_message"><?php _e( 'No Events Message', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					simcal_print_field( array(
						'type'    => 'textarea',
						'name'    => '_no_events_message',
						'id'      => '_no_events_message',
						'tooltip' => __( 'Some calendars may display a message when no events are found. You can change the default message here.', 'google-calendar-events' ),
						'value'   => get_post_meta( $post->ID, '_no_events_message', true ),
						'placeholder' => __( 'There are no upcoming events.', 'google-calendar-events' ),
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field">
				<th><label for="_event_formatting"><?php _e( 'Event Formatting', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$event_formatting = get_post_meta( $post->ID, '_event_formatting', true );

					simcal_print_field( array(
						'type'    => 'select',
						'name'    => '_event_formatting',
						'id'      => '_event_formatting',
						'tooltip' => __( 'How to preserve line breaks and paragraphs in the event template builder.', 'google-calendar-events' ),
						'value'   => $event_formatting,
						'default' => 'preserve_linebreaks',
						'options' => array(
							'preserve_linebreaks' => __( 'Preserve line breaks, auto paragraphs (default)', 'google-calendar-events' ),
							'no_linebreaks'       => __( 'No line breaks, auto paragraphs', 'google-calendar-events' ),
							'none'                => __( 'No line breaks, no auto paragraphs', 'google-calendar-events' ),
						),
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field">
				<th><label for="_poweredby"><?php _e( 'Powered By', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$poweredby = get_post_meta( $post->ID, '_poweredby', true );

					simcal_print_field( array(
						'type'    => 'checkbox',
						'name'    => '_poweredby',
						'id'      => '_poweredby',
						'value'   => 'yes' == $poweredby ? 'yes' : 'no',
						'text'    => __( 'Yes, Simple Calendar rocks! Show some love with a little link below this calendar.', 'google-calendar-events' ),
					) );

					?>
				</td>
			</tr>
			</tbody>
		</table>
		<?php

	}

	/**
	 * Print the advanced settings panel.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  \WP_Post $post
	 */
	private static function advanced_settings_panel( $post ) {

		?>
		<table>
			<thead>
			<tr><th colspan="2"><?php _e( 'Date and Time', 'google-calendar-events' ); ?></th></tr>
			</thead>
			<tbody class="simcal-panel-section simcal-panel-datetime-formatting">
			<tr class="simcal-panel-field">
				<th><label for="_calendar_timezone_setting"><?php _e( 'Timezone', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$timezone_wordpress = simcal_get_wp_timezone();
					$timezone_default   = $timezone_wordpress ? $timezone_wordpress : 'UTC';
					$timezone_setting   = esc_attr( get_post_meta( $post->ID, '_feed_timezone_setting', true ) );
					$timezone           = esc_attr( get_post_meta( $post->ID, '_feed_timezone', true ) );
					$timezone           = $timezone ? $timezone : $timezone_default;
					$show_use_calendar  = isset( simcal_get_feed( $post )->type );

					if ( $show_use_calendar ) {
						$show_use_calendar = ( simcal_get_feed( $post )->type !== 'grouped-calendars' ? 1 : 0 );
					} else {
						$show_use_calendar = true;
					}

					?>
					<select name="_feed_timezone_setting"
							id="_feed_timezone_setting"
							class="simcal-field simcal-field-select simcal-field-inline simcal-field-show-other"
							data-show-field-on-choice="true">
						<option value="use_site" <?php selected( 'use_site', $timezone_setting, true ); ?>><?php printf( _x( 'Site default', 'Use this site default setting', 'google-calendar-events' ) . ' (%s)', $timezone_default ); ?></option>
						<?php if ( $show_use_calendar ) { ?>
							<option id="use_calendar" value="use_calendar" data-show-field="_use_calendar_warning" <?php selected( 'use_calendar', $timezone_setting, true ); ?>><?php _ex( 'Event source default', 'Use the calendar default setting', 'google-calendar-events' ); ?></option>
						<?php } ?>
						<option value="use_custom" data-show-field="_feed_timezone" <?php selected( 'use_custom', $timezone_setting, true ); ?>><?php _ex( 'Custom', 'Use a custom setting', 'google-calendar-events' ); ?></option>
					</select>
					<select name="_feed_timezone"
							id="_feed_timezone"
							class="simcal-field simcal-field-select simcal-field-inline"
						<?php echo 'use_custom' != $timezone_setting ? 'style="display: none;"' : ''; ?>>
						<?php echo wp_timezone_choice( $timezone ); ?>
					</select>
					<i class="simcal-icon-help simcal-help-tip" data-tip="<?php _e( 'Using a different timezone may alter the date and time display of your calendar events. We recommended using the site default timezone.', 'google-calendar-events' ); ?>"></i>
					<p id="_use_calendar_warning" style="display: none;" class="simcal-field">
						<span class="attention"><?php _e( 'Warning', 'google-calendar-events' ); ?>:</span>
						<?php _e( 'Setting this to <code>Event source default</code> can at times cause unexpected results. Please test thoroughly.', 'google-calendar-events' ); ?>
						<a href="http://docs.simplecalendar.io/timezone-settings/" target="_blank"><?php _e( 'See details.', 'google-calendar-events' ); ?></a>
					</p>
				</td>
			</tr>
			<tr class="simcal-panel-field">
				<th><label for="_calendar_date_format_setting"><?php _e( 'Date Format', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$date_format_setting = esc_attr( get_post_meta( $post->ID, '_calendar_date_format_setting', true ) );
					$date_format_default = esc_attr( get_option( 'date_format' ) );
					$date_format = esc_attr( get_post_meta( $post->ID, '_calendar_date_format', true ) );
					$date_format_php = esc_attr( get_post_meta( $post->ID, '_calendar_date_format_php', true ) );
					$date_format_php = $date_format_php ? $date_format_php : $date_format_default;

					?>
					<select name="_calendar_date_format_setting"
							id="_calendar_date_format_setting"
							class="simcal-field simcal-field-select simcal-field-show-other">
						<option value="use_site" data-show-field="_calendar_date_format_default" <?php selected( 'use_site', $date_format_setting, true ); ?>><?php  _ex( 'Site default', 'Use this site default setting', 'google-calendar-events' ); ?></option>
						<option value="use_custom" data-show-field="_calendar_date_format" <?php selected( 'use_custom', $date_format_setting, true ); ?>><?php _ex( 'Custom', 'Use a custom setting', 'google-calendar-events' ); ?></option>
						<option value="use_custom_php" data-show-field="_calendar_date_format_php_field" <?php selected( 'use_custom_php', $date_format_setting, true ); ?>><?php _e( 'Custom (PHP format)', 'google-calendar-events' ); ?></option>
					</select>
					<i class="simcal-icon-help simcal-help-tip" data-tip="<?php _e( 'This option sets how calendars display event dates. It is recommended to keep your site default setting.', 'google-calendar-events' ); ?>"></i>
					<p id="_calendar_date_format_default" style="<?php echo $date_format_setting != 'use_site' ? 'display: none;' : ''; ?>">
						<em><?php _e( 'Preview', 'google-calendar-events' ) ?>:</em>&nbsp;&nbsp;
						<code><?php echo date_i18n( $date_format_default, time() ); ?></code>
					</p>
					<?php simcal_print_field( array(
						'type'    => 'datetime-format',
						'subtype' => 'date',
						'name'    => '_calendar_date_format',
						'id'      => '_calendar_date_format',
						'value'   => $date_format,
						'style'   => $date_format_setting != 'use_custom' ? array( 'display' => 'none' ) : '',
					) ); ?>
					<div class="simcal-field-datetime-format-php" id="_calendar_date_format_php_field" style="<?php echo $date_format_setting != 'use_custom_php' ? 'display: none;' : ''; ?>">
						<br>
						<label for="_calendar_date_format_php">
							<input type="text"
								   name="_calendar_date_format_php"
								   id="_calendar_date_format_php"
								   class="simcal-field simcal-field-text simcal-field-small"
								   value="<?php echo $date_format_php; ?>" />
							<?php printf( __( 'Enter a date format using %s values.', 'google-calendar-events' ), '<a href="//php.net/manual/en/function.date.php" target="_blank">PHP</a>' ); ?>
						</label>
						<p>
							<em><?php _e( 'Preview', 'google-calendar-events' ) ?>:</em>&nbsp;&nbsp;
							<code><?php echo date_i18n( $date_format_php, time() ); ?></code>
						</p>
					</div>
				</td>
			</tr>
			<tr class="simcal-panel-field">
				<th><label for="_calendar_datetime_separator"><?php _e( 'Separator', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$separator = get_post_meta( $post->ID, '_calendar_datetime_separator', true );
					$separator = false == $separator ? '@' : $separator;

					simcal_print_field( array(
						'type'    => 'standard',
						'subtype' => 'text',
						'name'    => '_calendar_datetime_separator',
						'id'      => '_calendar_datetime_separator',
						'value'   => $separator,
						'tooltip' => __( 'Used to divide date and time when both are shown.', 'google-calendar-events' ),
						'class'   => array(
							'simcal-field-tiny',
						),
					) );

					?>
				</td>
			</tr>
			<tr class="simcal-panel-field">
				<th><label for="_calendar_time_format_setting"><?php _e( 'Time Format', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$time_format_setting = esc_attr( get_post_meta( $post->ID, '_calendar_time_format_setting', true ) );
					$time_format_default = esc_attr( get_option( 'time_format' ) );
					$time_format = esc_attr( get_post_meta( $post->ID, '_calendar_time_format', true ) );
					$time_format_php = esc_attr( get_post_meta( $post->ID, '_calendar_time_format_php', true ) );
					$time_format_php = $time_format_php ? $time_format_php : $time_format_default;

					?>
					<select name="_calendar_time_format_setting"
							id="_calendar_time_format_setting"
							class="simcal-field simcal-field-select simcal-field-show-other">
						<option value="use_site" data-show-field="_calendar_time_format_default" <?php selected( 'use_site', $time_format_setting, true ); ?>><?php _ex( 'Site default', 'Use this site default setting', 'google-calendar-events' ); ?></option>
						<option value="use_custom" data-show-field="_calendar_time_format" <?php selected( 'use_custom', $time_format_setting, true ); ?>><?php _ex( 'Custom', 'Use a custom setting', 'google-calendar-events' ); ?></option>
						<option value="use_custom_php" data-show-field="_calendar_time_format_php_field" <?php selected( 'use_custom_php', $time_format_setting, true ); ?>><?php _e( 'Custom (PHP format)', 'google-calendar-events' ); ?></option>
					</select>
					<i class="simcal-icon-help simcal-help-tip" data-tip="<?php _e( 'This option sets how calendars display event times. It is recommended to keep your site default setting.', 'google-calendar-events' ); ?>"></i>
					<p id="_calendar_time_format_default" style="<?php echo $time_format_setting != 'use_site' ? 'display: none;' : ''; ?>">
						<em><?php _e( 'Preview', 'google-calendar-events' ) ?>:</em>&nbsp;&nbsp;
						<code><?php echo date_i18n( $time_format_default, time() ); ?></code>
					</p>
					<?php simcal_print_field( array(
						'type'    => 'datetime-format',
						'subtype' => 'time',
						'name'    => '_calendar_time_format',
						'id'      => '_calendar_time_format',
						'value'   => $time_format,
						'style'   => $time_format_setting != 'use_custom' ? array( 'display' => 'none' ) : '',
					) ); ?>
					<div class="simcal-field-datetime-format-php" id="_calendar_time_format_php_field" style="<?php echo $time_format_setting != 'use_custom_php' ? 'display: none;' : ''; ?>">
						<br>
						<label for="_calendar_date_format_php">
							<input type="text"
								   name="_calendar_time_format_php"
								   id="_calendar_time_format_php"
								   class="simcal-field simcal-field-text simcal-field-small"
								   value="<?php echo $time_format_php; ?>"/>
							<?php printf( __( 'Enter a time format using %s values.', 'google-calendar-events' ), '<a href="//php.net/manual/en/function.date.php" target="_blank">PHP</a>' ); ?>
						</label>
						<p>
							<em><?php _e( 'Preview', 'google-calendar-events' ) ?>:</em>&nbsp;&nbsp;
							<code><?php echo date_i18n( $time_format_php, time() ); ?></code>
						</p>
					</div>
				</td>
			</tr>
			<tr class="simcal-panel-field">
				<th><label for="_calendar_week_starts_on_setting"><?php _e( 'Week Starts On', 'google-calendar-events' ); ?></label></th>
				<td>
					<?php

					$week_starts_setting = esc_attr( get_post_meta( $post->ID, '_calendar_week_starts_on_setting', true ) );
					$week_starts_default = esc_attr( get_option( 'start_of_week' ) );
					$week_starts = intval( get_post_meta( $post->ID, '_calendar_week_starts_on', true ) );
					$week_starts = is_numeric( $week_starts ) ? strval( $week_starts ) : $week_starts_default;

					?>
					<select
						name="_calendar_week_starts_on_setting"
						id="_calendar_week_starts_on_setting"
						class="simcal-field simcal-field-select simcal-field-inline simcal-field-show-next"
						data-show-next-if-value="use_custom">
						<option value="use_site" <?php selected( 'use_site', $week_starts_setting, true ); ?>><?php printf( _x( 'Site default', 'Use this site default setting', 'google-calendar-events' ) . ' (%s)', date_i18n( 'l', strtotime( "Sunday + $week_starts_default Days" ) ) ); ?></option>
						<option value="use_custom" <?php selected( 'use_custom', $week_starts_setting, true ); ?>><?php _ex( 'Custom', 'Use a custom setting', 'google-calendar-events' ); ?></option>
					</select>
					<select
						name="_calendar_week_starts_on"
						id="_calendar_week_starts_on"
						class="simcal-field simcal-field-select simcal-field-inline"
						<?php echo 'use_custom' != $week_starts_setting ? 'style="display: none;"' : ''; ?>>
						<?php $day_names = simcal_get_calendar_names_i18n( 'day', 'full' ); ?>
						<?php for ( $i = 0; $i <= 6; $i++ ) : ?>
							<option value="<?php echo $i; ?>" <?php selected( $i, $week_starts, true ); ?>><?php echo $day_names[ $i ]; ?></option>
						<?php endfor; ?>
					</select>
					<i class="simcal-icon-help simcal-help-tip" data-tip="<?php _e( 'Some calendars may use this setting to display the start of the week. It is recommended to keep the site default setting.', 'google-calendar-events' ); ?>"></i>
				</td>
			</tr>
			</tbody>
		</table>
		<table>
			<thead>
			<tr><th colspan="2"><?php _e( 'Cache', 'google-calendar-events' ); ?></th></tr>
			</thead>
			<tbody class="simcal-panel-section simcal-panel-section-cache">
			<?php

			$cache_freq = esc_attr( get_post_meta( $post->ID, '_feed_cache_user_amount', true ) );
			$cache_unit = esc_attr( get_post_meta( $post->ID, '_feed_cache_user_unit', true ) );

			$cache_freq = intval( $cache_freq ) && $cache_freq >= 0 ? $cache_freq : 2;
			$cache_unit = $cache_unit ? $cache_unit : '3600';

			?>
			<tr class="simcal-panel-field">
				<th><label for="_feed_cache_user_amount"><?php _ex( 'Refresh Interval', 'Cache maximum interval', 'google-calendar-events' ); ?></label></th>
				<td>
					<input type="number"
						   name="_feed_cache_user_amount"
						   id="_feed_cache_user_amount"
						   class="simcal-field simcal-field-number simcal-field-tiny simcal-field-inline"
						   value="<?php echo $cache_freq; ?>"
						   min="0" />
					<select name="_feed_cache_user_unit"
							id="_feed_cache_user_unit"
							class="simcal-field simcalfield-select simcal-field-inline">
						<option value="60" <?php selected( '60', $cache_unit, true ); ?>><?php _e( 'Minute(s)', 'google-calendar-events' ); ?></option>
						<option value="3600" <?php selected( '3600', $cache_unit, true ); ?>><?php _e( 'Hour(s)', 'google-calendar-events' ); ?></option>
						<option value="86400" <?php selected( '86400', $cache_unit, true ); ?>><?php _e( 'Day(s)', 'google-calendar-events' ); ?></option>
						<option value="604800" <?php selected( '604800', $cache_unit, true ); ?>><?php _e( 'Week(s)', 'google-calendar-events' ); ?></option>
					</select>
					<i class="simcal-icon-help simcal-help-tip" data-tip="<?php _e( 'If you add, edit or remove events in your calendar very often, you can set a lower interval to refresh the events displayed. Set a higher interval for best performance.', 'google-calendar-events' ); ?>"></i>
				</td>
			</tr>
			</tbody>
		</table>
		<?php

	}

	/**
	 * Print fields in a panel.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $array
	 * @param  int   $post_id
	 *
	 * @return void
	 */
	public static function print_panel_fields( $array, $post_id ) {

		foreach ( $array as $section => $fields ) :

			if ( $fields && is_array( $fields ) ) :

				?>
				<tbody class="simcal-panel-section simcal-panel-section-<?php echo esc_attr( $section ); ?>">
				<?php foreach ( $fields as $key => $field ) :

					$value            = get_post_meta( $post_id, $key, true );
					$field['value']   = $value ? $value : ( isset( $field['default'] ) ? $field['default'] : '' );
					$the_field = simcal_get_field( $field ); ?>

					<?php if ( $the_field instanceof Field ) : ?>
					<tr class="simcal-panel-field">
						<th><label for="<?php echo $the_field->id ?>"><?php echo $the_field->title; ?></label></th>
						<td><?php $the_field->html(); ?></td>
					</tr>
				<?php endif; ?>

				<?php endforeach; ?>
				</tbody>
				<?php

			endif;

		endforeach;

	}

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
	public static function save( $post_id, $post ) {

		/* ====================== *
		 * Calendar type and view *
		 * ====================== */

		// Unlink existing terms for feed type and calendar type.
		wp_delete_object_term_relationships( $post_id, array(
			'calendar_feed',
			'calendar_type',
		) );

		// Set the feed type as term.
		$feed_type = isset( $_POST['_feed_type'] ) ? sanitize_title( stripslashes( $_POST['_feed_type'] ) ) : apply_filters( 'simcal_default_feed_type', 'google' );
		wp_set_object_terms( $post_id, $feed_type, 'calendar_feed' );

		// Set the calendar type as a term.
		$calendar_type = isset( $_POST['_calendar_type'] ) ? sanitize_title( stripslashes( $_POST['_calendar_type'] ) ) : apply_filters( 'simcal_default_calendar_type', 'default-calendar' );
		wp_set_object_terms( $post_id, $calendar_type, 'calendar_type' );
		// Set the calendar type view as post meta.
		$calendar_view = isset( $_POST['_calendar_view'] ) ? $_POST['_calendar_view'] : '';
		if ( $calendar_view && is_array( $calendar_view ) ) {
			$views = array_map( 'sanitize_title', $calendar_view );
			update_post_meta( $post_id, '_calendar_view', $views );
		}

		/* ===================== *
		 * Events settings panel *
		 * ===================== */

		// Calendar opening.
		$calendar_begins = isset( $_POST['_calendar_begins'] ) ? sanitize_key( $_POST['_calendar_begins'] ) : 'this_month';
		update_post_meta( $post_id, '_calendar_begins', $calendar_begins );
		$calendar_begins_nth = isset( $_POST['_calendar_begins_nth'] ) ? absint( $_POST['_calendar_begins_nth'] ) : 2;
		update_post_meta( $post_id, '_calendar_begins_nth', $calendar_begins_nth );
		$calendar_begins_custom_date = isset( $_POST['_calendar_begins_custom_date'] ) ? sanitize_title( $_POST['_calendar_begins_custom_date'] ) : '';
		update_post_meta( $post_id, '_calendar_begins_custom_date', $calendar_begins_custom_date );

		// Feed earliest events date.
		$earliest_events = isset( $_POST['_feed_earliest_event_date'] ) ? sanitize_key( $_POST['_feed_earliest_event_date'] ) : '';
		update_post_meta( $post_id, '_feed_earliest_event_date', $earliest_events );
		$earliest_events_range = isset( $_POST['_feed_earliest_event_date_range'] ) ? max( absint( $_POST['_feed_earliest_event_date_range'] ), 1 ) : 1;
		update_post_meta( $post_id, '_feed_earliest_event_date_range', $earliest_events_range );

		// Feed latest events date.
		$latest_events = isset( $_POST['_feed_latest_event_date'] ) ? sanitize_key( $_POST['_feed_latest_event_date'] ) : '';
		update_post_meta( $post_id, '_feed_latest_event_date', $latest_events );
		$latest_events_range = isset( $_POST['_feed_latest_event_date_range'] ) ? max( absint( $_POST['_feed_latest_event_date_range'] ), 1 ) : 1;
		update_post_meta( $post_id, '_feed_latest_event_date_range', $latest_events_range );

		/* ======================= *
		 * Calendar settings panel *
		 * ======================= */

		// Static calendar.
		$static = isset( $_POST['_calendar_is_static'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_calendar_is_static', $static );

		// No events message.
		$message = isset( $_POST['_no_events_message'] ) ? wp_kses_post( $_POST['_no_events_message'] ) : '';
		update_post_meta( $post_id, '_no_events_message', $message );

		// _event_formatting
		$event_formatting = isset( $_POST['_event_formatting'] ) ? sanitize_key( $_POST['_event_formatting'] ) : 'preserve_linebreaks';
		update_post_meta( $post_id, '_event_formatting', $event_formatting );

		// Powered by option
		$poweredby = isset( $_POST['_poweredby'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_poweredby', $poweredby );

		/* ======================= *
		 * Advanced settings panel *
		 * ======================= */

		// Timezone.
		$feed_timezone_setting = isset( $_POST['_feed_timezone_setting'] ) ? sanitize_key( $_POST['_feed_timezone_setting'] ) : 'use_calendar';
		update_post_meta( $post_id, '_feed_timezone_setting', $feed_timezone_setting );
		$default_timezone = simcal_get_wp_timezone();
		$feed_timezone = $default_timezone ? $default_timezone : 'UTC';
		$feed_timezone = isset( $_POST['_feed_timezone'] ) ? sanitize_text_field( $_POST['_feed_timezone'] ) : $feed_timezone;
		update_post_meta( $post_id, '_feed_timezone', $feed_timezone );

		// Date format.
		$date_format_setting = isset( $_POST['_calendar_date_format_setting'] ) ? sanitize_key( $_POST['_calendar_date_format_setting'] ) : 'use_site';
		update_post_meta( $post_id, '_calendar_date_format_setting', $date_format_setting );
		$date_format = isset( $_POST['_calendar_date_format'] ) ? sanitize_text_field( trim( $_POST['_calendar_date_format'] ) ) : get_option( 'date_format' );
		update_post_meta( $post_id, '_calendar_date_format', $date_format );
		$date_format_php = isset( $_POST['_calendar_date_format_php'] ) ? sanitize_text_field( trim( $_POST['_calendar_date_format_php'] ) ) : get_option( 'date_format' );
		update_post_meta( $post_id, '_calendar_date_format_php', $date_format_php );

		// Time format.
		$time_format_setting = isset( $_POST['_calendar_time_format_setting'] ) ? sanitize_key( $_POST['_calendar_time_format_setting'] ) : 'use_site';
		update_post_meta( $post_id, '_calendar_time_format_setting', $time_format_setting );
		$time_format = isset( $_POST['_calendar_time_format'] ) ? sanitize_text_field( trim( $_POST['_calendar_time_format'] ) ) : get_option( 'time_format' );
		update_post_meta( $post_id, '_calendar_time_format', $time_format );
		$time_format_php = isset( $_POST['_calendar_time_format_php'] ) ? sanitize_text_field( trim( $_POST['_calendar_time_format_php'] ) ) : get_option( 'time_format' );
		update_post_meta( $post_id, '_calendar_time_format_php', $time_format_php );

		// Date-time separator.
		$datetime_separator = isset( $_POST['_calendar_datetime_separator'] ) ? sanitize_text_field( $_POST['_calendar_datetime_separator'] ) : ' ';
		update_post_meta( $post_id, '_calendar_datetime_separator', $datetime_separator );

		// Week start.
		$week_start_setting = isset( $_POST['_calendar_week_starts_on_setting'] ) ? sanitize_key( $_POST['_calendar_week_starts_on_setting'] ) : 'use_site';
		update_post_meta( $post_id, '_calendar_week_starts_on_setting', $week_start_setting );
		$week_start = isset( $_POST['_calendar_week_starts_on'] ) ? intval( $_POST['_calendar_week_starts_on'] ) : get_option( 'start_of_week' );
		update_post_meta( $post_id, '_calendar_week_starts_on', $week_start );

		// Cache interval.
		$cache = 7200;
		if ( isset( $_POST['_feed_cache_user_amount'] ) && isset( $_POST['_feed_cache_user_unit'] ) ) {
			$amount = is_numeric( $_POST['_feed_cache_user_amount'] ) || $_POST['_feed_cache_user_amount'] == 0 ? absint( $_POST['_feed_cache_user_amount'] ) : 1;
			$unit   = is_numeric( $_POST['_feed_cache_user_unit'] ) ? absint( $_POST['_feed_cache_user_unit'] ) : 3600;
			update_post_meta( $post_id, '_feed_cache_user_amount', $amount );
			update_post_meta( $post_id, '_feed_cache_user_unit', $unit );
			$cache  = ( ( $amount * $unit ) > 0 ) ? $amount * $unit : 1;
		}
		update_post_meta( $post_id, '_feed_cache', $cache );

		/* ============= *
		 * Miscellaneous *
		 * ============= */

		// Update version.
		update_post_meta( $post_id, '_calendar_version', SIMPLE_CALENDAR_VERSION );

		// Action hook.
		do_action( 'simcal_process_settings_meta', $post_id );

		// Clear cache.
		simcal_delete_feed_transients( $post_id );
	}

}
