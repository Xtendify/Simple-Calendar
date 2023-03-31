<?php
/**
 * Default Calendar
 *
 * @package SimpleCalendar\Calendars
 */
namespace SimpleCalendar\Calendars;

use SimpleCalendar\plugin_deps\Carbon\Carbon;
use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Calendar_View;
use SimpleCalendar\Calendars\Admin\Default_Calendar_Admin;
use SimpleCalendar\Calendars\Views;
use SimpleCalendar\Events\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Calendar.
 *
 * The default calendar view bundled with the plugin.
 *
 * @since 3.0.0
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
	 * Skin theme.
	 *
	 * @access public
	 * @var string
	 */
	public $theme = 'light';

	/**
	 * Today color.
	 *
	 * @access public
	 * @var string
	 */
	public $today_color = '#FF0000';

	/**
	 * Days with events color.
	 *
	 * @access public
	 * @var string
	 */
	public $days_events_color = '#000000';

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
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

			$this->set_properties( $this->view->get_type() );

			$id    = $this->id;
			$theme = $this->theme;

			add_filter( 'simcal_calendar_class', function ( $class, $post_id ) use ( $theme, $id ) {
				if ( in_array( 'default-calendar', $class ) && $post_id === $id ) {
					array_push( $class, 'default-calendar-' . $theme );
				}

				return $class;
			}, 10, 2 );

		}

		// Calendar settings handling.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$admin          = new Default_Calendar_Admin();
			$this->settings = $admin->settings_fields();
		}
	}

	/**
	 * Set properties.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  $view
	 */
	private function set_properties( $view ) {

		// Set styles.
		if ( 'dark' == get_post_meta( $this->id, '_default_calendar_style_theme', true ) ) {
			$this->theme = 'dark';
		}
		if ( $today_color = get_post_meta( $this->id, '_default_calendar_style_today', true ) ) {
			$this->today_color = esc_attr( $today_color );
		}
		if ( $day_events_color = get_post_meta( $this->id, '_default_calendar_style_days_events', true ) ) {
			$this->days_events_color = esc_attr( $day_events_color );
		}

		// Hide too many events.
		if ( 'yes' == get_post_meta( $this->id, '_default_calendar_limit_visible_events', true ) ) {
			$this->events_limit = absint( get_post_meta( $this->id, '_default_calendar_visible_events', true ) );
		}

		// Expand multiple day events.
		if ( 'yes' == get_post_meta( $this->id, '_default_calendar_expand_multi_day_events', true ) || ( 'list' == $view && 'current_day_only' == get_post_meta( $this->id, '_default_calendar_expand_multi_day_events', true ) ) ) {
			$this->events = $this->expand_multiple_days_events();
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
	 * Expand multiple day events.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @return array
	 */
	private function expand_multiple_days_events() {

		$old_events = $this->events;
		$new_events = array();

		if ( ! empty( $old_events ) ) {

			foreach ( $old_events as $events ) {
				foreach ( $events as $event ) {
					if ( $event instanceof Event ) {
						if ( false !== $event->multiple_days ) {
							$days = $event->multiple_days;

							if ( $days > 0 ) {

								for ( $d = 1; $d <= $days; $d++ ) {
									$current_day_ts = $event->start + ( $d * DAY_IN_SECONDS - 1 );
									$new_events[ intval( $current_day_ts ) ][] = $event;
								}
							}
						}
					}
				}

			}

		}

		$events = $old_events + $new_events;
		ksort( $events, SORT_NUMERIC );

		return $events;
	}

	/**
	 * Get a view.
	 *
	 * Returns one of this calendar's views.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $view
	 *
	 * @return null|Calendar_View
	 */
	public function get_view( $view = '' ) {

		$view = ! empty( $view ) ? $view : 'grid';

		do_action( 'simcal_calendar_get_view', $this->type, $view );

		if ( 'grid' == $view ) {
			return new Views\Default_Calendar_Grid( $this );
		} elseif ( 'list' == $view ) {
			return new Views\Default_Calendar_List( $this );
		}

		return null;
	}

}
