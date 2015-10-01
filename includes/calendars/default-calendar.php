<?php
/**
 * Default Calendar
 *
 * @package SimpleCalendar\Calendars
 */
namespace SimpleCalendar\Calendars;

use Carbon\Carbon;
use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Calendar_View;
use SimpleCalendar\Calendars\Views;
use SimpleCalendar\Events\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Calendar.
 *
 * The default calendar view bundled with the plugin.
 */
class Default_Calendar extends Calendar {

	/**
	 * Limit visibility of daily events.
	 *
	 * @access public
	 * @var int
	 */
	public $events_limit = -1;

	/**
	 * Trim characters event titles in grid.
	 *
	 * @access public
	 * @var int
	 */
	public $trim_titles = -1;

	/**
	 * Event bubbles action trigger.
	 *
	 * @access public
	 * @var string
	 */
	public $event_bubble_trigger = 'click';

	/**
	 * Hide navigation buttons.
	 *
	 * @access public
	 * @var bool
	 */
	public $compact_list = false;

	/**
	 * Grouped list type.
	 *
	 * @access public
	 * @var string
	 */
	public $group_type = '';

	/**
	 * Grouped list span.
	 *
	 * @access public
	 * @var int
	 */
	public $group_span = 1;

	/**
	 * Constructor.
	 *
	 * @param int|object|\WP_Post|Calendar $calendar
	 */
	public function __construct( $calendar ) {

		$this->type  = 'default-calendar';
		$this->name  = __( 'Default', 'google-calendar-events' );
		$this->views = apply_filters( 'simcal_default_calendar_views', array(
			'grid' => __( 'Grid', 'google-calendar-events' ),
			'list' => __( 'List', 'google-calendar-events' ),
		) );

		parent::__construct( $calendar );

		if ( ! is_null( $this->post ) ) {
			$view = $this->view->get_type();
			$this->set_properties( $view );
		}

		// Calendar settings handling.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			if ( simcal_is_admin_screen() !== false ) {
				add_action( 'simcal_settings_meta_calendar_panel', array( $this, 'add_settings_meta_calendar_panel' ), 10, 1 );
			}
			add_action( 'simcal_process_settings_meta', array( $this, 'process_meta' ), 10, 1 );
		}
	}

	/**
	 * Set properties.
	 *
	 * @access private
	 *
	 * @param  $view
	 */
	private function set_properties( $view ) {

		// Hide too many events.
		if ( 'yes' == get_post_meta( $this->id, '_default_calendar_limit_visible_events', true ) ) {
			$this->events_limit = absint( get_post_meta( $this->id, '_default_calendar_visible_events', true ) );
		}

		// Expand multiple day events.
		if ( 'yes' == get_post_meta( $this->id, '_default_calendar_expand_multi_day_events', true ) ) {

			$old_events = $this->events;

			if ( ! empty( $old_events ) ) {

				$new_events = array();

				foreach( $old_events as $events ) {

					foreach( $events as $event ) {

						if ( $event instanceof Event ) {
							if ( $event->multiple_days !== false ) {

								$carbon = new Carbon();
								$date = $carbon->createFromTimestamp( $event->start_utc, $this->timezone );
								$days = $event->multiple_days;

								for( $d = 2; $d < $days; $d++ ) {
									$addDay = $date->addDay()->startOfDay();
									$new_events[ intval( $addDay->getTimestamp() ) ][] = $event;
								}
							}

						}
					}
				}

				$events = $new_events + $old_events;
				ksort( $events, SORT_NUMERIC );
				$this->events = $events;
			}
		}

		if ( 'grid' == $view ) {

			// Use hover to open event bubbles.
			if ( 'hover' == get_post_meta( $this->id, '_default_calendar_event_bubble_trigger', true ) ) {
				$this->event_bubble_trigger = 'hover';
			}

			// Trim long event titles.
			if ( 'yes' == get_post_meta( $this->id, '_default_calendar_trim_titles', true ) ) {
				$this->trim_titles = max( absint( get_post_meta( $this->id, '_default_calendar_trim_titles_chars', true ) ), 1 );
			}

		} else {

			// List range.
			$this->group_type = esc_attr( get_post_meta( $this->id, '_default_calendar_list_range_type', true ) );
			$this->group_span = max( absint( get_post_meta( $this->id, '_default_calendar_list_range_span', true ) ), 1 );

			// Make the list look more compact.
			if ( 'yes' == get_post_meta( $this->id, '_default_calendar_compact_list', true ) ) {
				$this->compact_list = true;
			}

		}

	}

	/**
	 * Get a view.
	 *
	 * Returns one of this calendar's views.
	 *
	 * @param  string $view
	 *
	 * @return null|Calendar_View
	 */
	public function get_view( $view ='' ) {

		$view = ! empty( $view ) ? $view : 'grid';

		do_action( 'simcal_calendar_get_view', $this->type, $view );

		if ( 'grid' == $view ) {
			return new Views\Default_Calendar_Grid( $this );
		} elseif ( 'list' == $view ) {
			return new Views\Default_Calendar_List( $this );
		}

		return null;
	}

	/**
	 * Fields inputs for settings page.
	 *
	 * @return array
	 */
	public function settings_fields() {
		return array();
	}

	/**
	 * Extend the calendar section panel of the settings meta box.
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
								'hover' => __( 'Hover', 'google-calendar-events' )
							)
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
							'context'     => 'metabox',
							'class'       => array(
								'simcal-field-show-next',
							),
							'value'       => 'yes' == $trim ? 'yes' : 'no',
							'attributes'  => array(
								'data-show-next-if-value' => 'yes'
							)
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
							'context'    => 'metabox',
							'value'      => 'yes' == $trim ? strval( max( absint( get_post_meta( $post_id, '_default_calendar_trim_titles_chars', true ) ), 1 ) ) : '20',
							'attributes' => array(
								'min'     => '1'
							)
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
							'context' => 'metabox',
							'value'   => strval( $list_span ),
							'attributes'  => array(
								'min' => '1'
							),
						) );

						$list_type = get_post_meta( $post_id, '_default_calendar_list_range_type', true );

						simcal_print_field( array(
							'type'    => 'select',
							'name'    => '_default_calendar_list_range_type',
							'id'      => '_default_calendar_list_range_type',
							'tooltip' => __( 'Range of events to show on each calendar page.', 'google-calendar-events' ),
							'context' => 'metabox',
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
							'context' => 'metabox',
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
							'context'     => 'metabox',
							'value'       => 'yes' == $limit ? 'yes' : 'no',
							'class'       => array(
								'simcal-field-show-next',
							),
							'attributes'  => array(
								'data-show-next-if-value' => 'yes'
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
							'context'    => 'metabox',
							'value'      => $visible_events,
							'attributes' => array(
								'min'     => '1'
							)
						) );

						?>
					</td>
				</tr>
				<tr class="simcal-panel-field simcal-default-calendar-grid simcal-default-calendar-list" style="display: none;">
					<th><label for="_default_calendar_event_bubbles_action"><?php _e( 'Expand multi day events', 'google-calendar-events' ); ?></label></th>
					<td>
						<?php

						simcal_print_field( array(
							'type'    => 'checkbox',
							'name'    => '_default_calendar_expand_multi_day_events',
							'id'      => '_default_calendar_expand_multi_day_events',
							'tooltip' => __( 'Show events spanning multiple days on each day.', 'google-calendar-events' ),
							'value'   => get_post_meta( $post_id, '_default_calendar_expand_multi_day_events', true ),
							'context' => 'metabox',
						) );

						?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php

	}

	/**
	 * Process meta fields.
	 *
	 * @param int $post_id
	 */
	public function process_meta( $post_id ) {

		// List range span.
		$span = isset( $_POST['_default_calendar_list_range_span'] ) ? max( absint( $_POST['_default_calendar_list_range_span'] ), 1 ) : 1;
		update_post_meta( $post_id, '_default_calendar_list_range_span', $span );

		// List range type.
		$group = isset( $_POST['_default_calendar_list_range_type'] ) ? sanitize_key( $_POST['_default_calendar_list_range_type'] ) : 'monthly';
		update_post_meta( $post_id, '_default_calendar_list_range_type', $group );

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
		$multi_day = isset( $_POST['_default_calendar_expand_multi_day_events'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_default_calendar_expand_multi_day_events', $multi_day );

	}

}
