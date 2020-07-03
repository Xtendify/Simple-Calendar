<?php
/**
 * Default Calendar - List View
 *
 * @package SimpleCalendar/Calendars
 */
namespace SimpleCalendar\Calendars\Views;

use Carbon\Carbon;
use Mexitek\PHPColors\Color;
use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Calendar_View;
use SimpleCalendar\Calendars\Default_Calendar;
use SimpleCalendar\Events\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Calendar: List View.
 *
 * @since  3.0.0
 */
class Default_Calendar_List implements Calendar_View {

	/**
	 * Calendar.
	 *
	 * @access public
	 * @var Default_Calendar
	 */
	public $calendar = null;

	/**
	 * Current display start.
	 *
	 * @access private
	 * @var int
	 */
	private $start = 0;

	private $first_event = 0;

	private $last_event = 0;

	/**
	 * Current display end.
	 *
	 * @access private
	 * @var int
	 */
	private $end = 0;

	/**
	 * Previous display start.
	 *
	 * @access private
	 * @var int
	 */
	private $prev = 0;

	/**
	 * Next display start.
	 *
	 * @access private
	 * @var int
	 */
	private $next = 0;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string|Calendar $calendar
	 */
	public function __construct( $calendar = '' ) {
		$this->calendar = $calendar;
	}

	/**
	 * Get the view parent calendar type.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	public function get_parent() {
		return 'default-calendar';
	}

	/**
	 * Get the view type.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	public function get_type() {
		return 'list';
	}

	/**
	 * Get the view name.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'List', 'google-calendar-events' );
	}

	/**
	 * Add ajax actions.
	 *
	 * @since 3.0.0
	 */
	public function add_ajax_actions() {
		add_action( 'wp_ajax_simcal_default_calendar_draw_list', array( $this, 'draw_list_ajax' ) );
		add_action( 'wp_ajax_nopriv_simcal_default_calendar_draw_list', array( $this, 'draw_list_ajax' ) );
	}

	/**
	 * Default calendar list scripts.
	 *
	 * Scripts to load when this view is displayed.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $min
	 *
	 * @return array
	 */
	public function scripts( $min = '' ) {
		return array(
			'simcal-qtip' => array(
				'src'       => SIMPLE_CALENDAR_ASSETS . 'js/vendor/qtip' . $min . '.js',
				'deps'      => array( 'jquery' ),
				'in_footer' => true,
			),
			'simcal-default-calendar' => array(
				'src'       => SIMPLE_CALENDAR_ASSETS . 'js/default-calendar' . $min . '.js',
				'deps'      => array(
					'jquery',
					'simcal-qtip',
				),
				'in_footer' => true,
				'localize'  => array(
					'simcal_default_calendar' => simcal_common_scripts_variables(),
				),
			),
		);
	}

	/**
	 * Default calendar list styles.
	 *
	 * Stylesheets to load when this view is displayed.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $min = ''
	 *
	 * @return array
	 */
	public function styles( $min = '' ) {
		return array(
			'simcal-default-calendar-list' => array(
				'src'   => SIMPLE_CALENDAR_ASSETS . 'css/default-calendar-list' . $min . '.css',
				'media' => 'all',
			),
		);
	}

	/**
	 * Default calendar list markup.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		$calendar = $this->calendar;

		if ( $calendar instanceof Default_Calendar ) {

			$disabled = $calendar->static === true || empty( $calendar->events ) ? ' disabled="disabled"' : '';


			$hide_header = get_post_meta( $this->calendar->id, '_default_calendar_list_header', true ) == 'yes' ? true : false;
			$static_calendar = get_post_meta( $this->calendar->id, '_calendar_is_static', true ) == 'yes' ? true : false;

			$header_class = '';
			$compact_list_class = $calendar->compact_list ? 'simcal-calendar-list-compact' : '';

			edit_post_link( __( 'Edit Calendar', 'google-calendar-events' ), '<p class="simcal-align-right"><small>', '</small></p>', $calendar->id );

			echo '<div class="simcal-calendar-list ' . $compact_list_class . '">';

			if ( ! $hide_header && ! $static_calendar ) {
				echo '<nav class="simcal-calendar-head">' . "\n";

				echo "\t" . '<div class="simcal-nav">' . "\n";
				echo "\t\t" . '<button class="simcal-nav-button simcal-prev" title="' . __('Previous', 'google-calendar-events') . '"' . $disabled . '>' . "\n";
				echo "\t\t\t" . '<i class="simcal-icon-left"></i>' . "\n";
				echo "\t\t" . '</button>' . "\n";
				echo "\t" . '</div>' . "\n";

				if ( $hide_header ) {
					$header_class = 'simcal-hide-header';
				}


				echo "\t" . '<div class="simcal-nav simcal-current ' . $header_class . '" data-calendar-current="' . $calendar->start . '">' . "\n";
				echo "\t\t" . '<h3 class="simcal-current-label"> </h3>' . "\n";
				echo "\t" . '</div>' . "\n";

				echo "\t" . '<div class="simcal-nav">';
				echo "\t\t" . '<button class="simcal-nav-button simcal-next" title="' . __('Next', 'google-calendar-events') . '"' . $disabled . '>';
				echo "\t\t\t" . '<i class="simcal-icon-right"></i>' . "\n";
				echo "\t\t" . '</button>' . "\n";
				echo "\t" . '</div>' . "\n";

				echo '</nav>' . "\n";
			}

			echo $this->draw_list( $calendar->start );

			echo '<div class="simcal-ajax-loader simcal-spinner-top" style="display: none;"><i class="simcal-icon-spinner simcal-icon-spin"></i></div>';

			echo '</div>';

		}

	}

	/**
	 * Get events for current display.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  int $timestamp
	 *
	 * @return array
	 */
	private function get_events( $timestamp ) {

		$calendar = $this->calendar;

		if ( ! $calendar->group_type || ! $calendar->group_span ) {
			return array();
		}

		// Need to pass in timezone here to get beginning of day.
		$current = Carbon::createFromTimestamp( $timestamp, $calendar->timezone );
		$prev = clone $current;
		$next = clone $current;

		$this->start = $timestamp;

		$interval = $span = max( absint( $calendar->group_span ), 1 );

		if ( 'monthly' == $calendar->group_type ) {
			$this->prev = $prev->subMonths( $span )->getTimestamp();
			$this->next = $next->addMonths( $span )->getTimestamp();
		} elseif ( 'weekly' == $calendar->group_type ) {
			$week = new Carbon( $calendar->timezone );
			$week->setTimestamp( $timestamp );
			$week->setWeekStartsAt( $calendar->week_starts );
			$this->prev = $prev->subWeeks( $span )->getTimestamp();
			$this->next = $next->addWeeks( $span )->getTimestamp();
		} elseif ( 'daily' == $calendar->group_type ) {
			$this->prev = $prev->subDays( $span )->getTimestamp();
			$this->next = $next->addDays( $span )->getTimestamp();
		}

		$events = $calendar->events;
		$daily_events = $paged_events = $flattened_events = array();

		if ( 'events' != $calendar->group_type ) {

			$this->end   = $this->next - 1;

			$timestamps   = array_keys( $events );
			$lower_bound  = array_filter( $timestamps,  array( $this, 'filter_events_before' ) );
			$higher_bound = array_filter( $lower_bound, array( $this, 'filter_events_after'  ) );

			if ( is_array( $higher_bound ) && !empty( $higher_bound ) ) {
				$filtered = array_intersect_key( $events, array_combine( $higher_bound, $higher_bound ) );
				foreach ( $filtered as $timestamp => $events ) {
					$paged_events[ intval( $timestamp ) ] = $events;
				}
			}

		} else {

			foreach ( $events as $timestamp => $e ) {
				$second = 0;
				foreach ( $e as $event ) {
					$flattened_events[ intval( $timestamp + $second ) ][] = $event;
					$second++;
				}
			}

			ksort( $flattened_events, SORT_NUMERIC );

			$keys  = array_keys( $flattened_events );
			$current = 0;
			foreach ( $keys as $timestamp ) {
				if ( $timestamp < $this->start ) {
					$current++;
				}
			}

			$paged_events = array_slice( $flattened_events, $current, $interval, true );

			$events_end = isset( $keys[ $current + $interval ] ) ? $keys[ $current + $interval ] : $calendar->end;
			$this->end  = $events_end > $calendar->end ? $calendar->end : $events_end;

			$this->prev = isset( $keys[ $current - $interval ] ) ? $keys[ $current - $interval ] : $calendar->earliest_event;
			$this->next = isset( $keys[ $current + $interval ] ) ? $keys[ $current + $interval ] : $this->end;

		}

		// Put resulting events in an associative array, with Ymd date as key for easy retrieval in calendar days loop.

		foreach ( $paged_events as $timestamp => $events ) {

			// TODO First $paged_events item timestamp 1 second off? Plus or minus?

			if ( $timestamp <= $this->end ) {

				// TODO Could go back to using Carbon to be consistent.
				// $date is off by a couple hours for dates in multi-day event, but not for first event.
				// But only certain timezones? UTC-1, UTC+1, UTC+2, UTC+3 ???
				// Offset changes after first day with these timezones only. Why?
				// November 1, 2016 is daylight savings for them!!!

				/*
				$date = Carbon::createFromTimestamp( $timestamp, $calendar->timezone );

				// Add date offset back in?
				// $date = Carbon::createFromTimestamp( $timestamp + $date->offset, $calendar->timezone );

				$dateYmd = $date->copy()->endOfDay()->format( 'Ymd' );
				*/

				// Using native PHP 5.3+ (not Carbon) here.
				// Offset value after first day same behavior as Carbon above still.
				$dtz = new \DateTimeZone( $calendar->timezone );

				$date = \DateTime::createFromFormat( 'U', $timestamp );

				// Doesn't seem to make a difference omitting timezone.
				//$date = \DateTime::createFromFormat( 'U', $timestamp, $dtz );

				// Add offset to timestamp to get correct date.
				// TODO Need to add +1 second also?
				$offset = $dtz->getOffset( $date );
				$date_offset = clone $date;
				$date_offset->add( \DateInterval::createFromDateString( $offset . ' seconds' ) );

				// TODO Multiple day events will be off if part-way through there's daylight savings.

				$dateYmd = $date_offset->format( 'Ymd' );
				$daily_events[ intval( $dateYmd ) ][] = $events;
			}
		}

		ksort( $daily_events, SORT_NUMERIC );

		if ( ! empty( $paged_events ) ) {
			$first_event       = array_slice( $paged_events, 0, 1, true );
			$first_event       = array_pop( $first_event );
			$this->first_event = $first_event[0]->start;

			$last_event       = array_pop( $paged_events );
			$this->last_event = $last_event[0]->start;
		}

		return $daily_events;
	}

	/**
	 * Get calendar list heading.
	 *
	 * Parses calender date format and adapts to current display range.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @return array
	 */
	private function get_heading() {

		$calendar = $this->calendar;
		$start = Carbon::createFromTimestamp( $calendar->start, $calendar->timezone );
		$end = Carbon::createFromTimestamp( $this->end, $calendar->timezone );
		$date_format = $this->calendar->date_format;
		$date_order  = simcal_get_date_format_order( $date_format );

		if ( $this->first_event !== 0 ) {
			$start = Carbon::createFromTimestamp( $this->first_event, $calendar->timezone );
		}

		if ( $this->last_event !== 0 ) {
			$end = Carbon::createFromTimestamp( $this->last_event, $calendar->timezone );
		}

		$st = strtotime( $start->toDateTimeString() );
		$et = strtotime( $end->toDateTimeString() );

		// TODO Is logic here causing the weird "29 Oct, 2016" format when navigating?

		if ( ( $start->day == $end->day ) && ( $start->month == $end->month ) && ( $start->year == $end->year ) ) {
			// Start and end on the same day.
			// e.g. 1 February 2020
			$large = $small = date_i18n( $calendar->date_format , $st );
			if ( ( $date_order['d'] !== false ) && ( $date_order['m'] !== false ) ) {
				if ( $date_order['m'] > $date_order['d'] ) {
					if ( $date_order['y'] !== false && $date_order['y'] > $date_order['m'] ) {
						$small = date_i18n( 'Y, d M', $st );
					} else {
						$small = date_i18n( 'd M Y', $st );
					}
				} else {
					if ( $date_order['y'] !== false && $date_order['y'] > $date_order['m'] ) {
						$small = date_i18n( 'Y, M d', $st );
					} else {
						$small = date_i18n( 'M d Y', $st );
					}
				}
			}
		} elseif ( ( $start->month == $end->month ) && ( $start->year == $end->year ) ) {
			// Start and end days on the same month.
			// e.g. August 2020
			if ( $date_order['y'] === false ) {
				// August.
				$large = $small = date_i18n( 'F', $st );
			} else {
				if ( $date_order['y'] < $date_order['m'] ) {
					// 2020 August.
					$large = date_i18n( 'Y F', $st );
					$small = date_i18n( 'Y M', $st );
				} else {
					// August 2020.
					$large = date_i18n( 'F Y', $st );
					$small = date_i18n( 'M Y', $st );
				}
			}
		} elseif ( $start->year == $end->year ) {
			// Start and end days on months of the same year.
			// e.g. August - September 2020
			if ( $date_order['y'] === false ) {
				// August - September.
				$large = date_i18n( 'F', $st ) . ' - ' . date_i18n( 'F', $et );
				$small = date_i18n( 'M', $st ) . ' - ' . date_i18n( 'M', $et );
			} else {
				if ( $date_order['y'] < $date_order['m'] ) {
					// 2020, August - September.
					$large  = $small = date( 'Y', $st ) . ', ';
					$large .= date_i18n( 'F', $st ) . ' - ' . date_i18n( 'F', $et );
					$small .= date_i18n( 'M', $st ) . ' - ' . date_i18n( 'M', $et );
				} else {
					// August - September, 2020.
					$large  = date_i18n( 'F', $st ) . ' - ' . date_i18n( 'F', $et ) . ', ';
					$small  = date_i18n( 'M', $st ) . ' - ' . date_i18n( 'M', $et ) . ' ';
					$year   = date( 'Y', $st );
					$large .= $year;
					$small .= $year;
				}
			}
		} else {
			$large = $small = date( 'Y', $st ) . ' - ' . date( 'Y', $et );
		}

		return array(
			'small' => $small,
			'large' => $large,
		);
	}

	/**
	 * Make a calendar list of events.
	 *
	 * Outputs a list of events according to events for the specified range.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  int $timestamp
	 * @param  int $id
	 *
	 * @return string
	 */
	private function draw_list( $timestamp, $id = 0 ) {

		$calendar = $this->calendar;

		if ( empty( $calendar ) ) {
			$calendar = $this->calendar = simcal_get_calendar( intval( $id ) );
			if ( ! $calendar instanceof Default_Calendar ) {
				return '';
			}
		}

		$now = $calendar->now;
		$current_events = $this->get_events( $timestamp );
		$format = $calendar->date_format;

		ob_start();

		// Draw the events.

		$block_tag = $calendar->compact_list && ! empty( $current_events ) ? 'div' : 'dl';

		$data_heading = '';
		$heading = $this->get_heading();
		foreach ( $heading as $k => $v ) {
			$data_heading .= ' data-heading-' . $k . '="' . $v . '"';
		}

		echo '<' . $block_tag . ' class="simcal-events-list-container"' .
		     ' data-prev="' . $this->prev . '"' .
		     ' data-next="' . $this->next . '"' .
		     $data_heading . '>';

		if ( ! empty( $current_events ) && is_array( $current_events ) ) :

			$last_event = null;

			foreach ( $current_events as $ymd => $events ) :

				// This is where we can find out if an event is a multi-day event and if it needs to be shown.
				// Since this is for list view we are showing the event on the day viewed if it is part of that day even when
				// expand multi-day events are turned off.

				$first_event = $events[0][0];

				if ( isset( $first_event->multiple_days ) && $first_event->multiple_days > 0 ) {

					if ( 'current_day_only' == get_post_meta( $calendar->id, '_default_calendar_expand_multi_day_events', true ) ) {

						$year  = substr( $ymd, 0, 4 );
						$month = substr( $ymd, 4, 2 );
						$day   = substr( $ymd, 6, 2 );

						$temp_date = Carbon::createFromDate( $year, $month, $day );

						if ( ! ( $temp_date < Carbon::now()->endOfDay() ) ) {

							// Break here only if event already shown once.
							if ( $last_event == $first_event ) {
								continue;
							} else {
								// Save event as "last" for next time through, then break.
								$last_event = $first_event;
							}
						}
					}
				}

				// Add offset offset for list view day headings.
				$day_date = Carbon::createFromFormat( 'Ymd', $ymd, $calendar->timezone );
				$day_date_offset = clone $day_date;
				$day_date_offset->addSeconds( $day_date->offset );
				$day_date_ts_offset = $day_date_offset->timestamp;

				if ( ! $calendar->compact_list ) {
					if ( $day_date_offset->isToday() ) {
						$the_color = new Color( $calendar->today_color );
					} else {
						$the_color = new Color( $calendar->days_events_color );
					}

					$bg_color     = '#' . $the_color->getHex();
					$color        = $the_color->isDark() ? '#ffffff' : '#000000';
					$border_style = ' style="border-bottom: 1px solid ' . $bg_color . ';" ';
					$bg_style     = ' style="background-color: ' . $bg_color . '; color: ' . $color . ';"';

					echo "\t" . '<dt class="simcal-day-label"' . $border_style . '>';
					echo '<span' . $bg_style . '>';

					echo $format ? '<span class="simcal-date-format" data-date-format="' . $format . '">' . date_i18n( $format, $day_date_ts_offset, strtotime( $day_date_offset->toDateTimeString() ) ) . '</span> ' : ' ';

					echo '</span>';
					echo '</dt>' . "\n";
				}

				$list_events = '<ul class="simcal-events">' . "\n";

				$calendar_classes = array();

				// Add day of week number to CSS class.
				$day_classes = 'simcal-weekday-' . date( 'w', $day_date_ts_offset );

				// Is this the present, the past or the future, Doc?
				if ( $timestamp <= $now && $timestamp >= $now ) {
					$day_classes .= ' simcal-today simcal-present simcal-day';
				} elseif ( $timestamp < $now ) {
					$day_classes .= ' simcal-past simcal-day';
				} elseif ( $this->end > $now ) {
					$day_classes .= ' simcal-future simcal-day';
				}

				$count = 0;

				foreach ( $events as $day_events ) :
					foreach ( $day_events as $event ) :

						if ( $event instanceof Event ) :

							$event_classes = $event_visibility = '';

							$calendar_class     = 'simcal-events-calendar-' . strval( $event->calendar );
							$calendar_classes[] = $calendar_class;

							$recurring     = $event->recurrence ? 'simcal-event-recurring ' : '';
							$has_location  = $event->venue ? 'simcal-event-has-location ' : '';

							$event_classes .= 'simcal-event ' . $recurring . $has_location . $calendar_class;

							// Toggle some events visibility if more than optional limit.
							if ( ( $calendar->events_limit > - 1 ) && ( $count >= $calendar->events_limit ) ) :
								$event_classes .= ' simcal-event-toggled';
								$event_visibility = ' display: none;';
							endif;

							$event_color = $event->get_color();
							if ( ! empty( $event_color ) ) {
								$side = is_rtl() ? 'right' : 'left';
								$event_color = ' border-' . $side . ': 4px solid ' . $event_color . '; padding-' . $side . ': 8px;';
							}

							$list_events .= "\t" . '<li class="' . $event_classes . '" style="' . $event_visibility . $event_color . '" itemscope itemtype="http://schema.org/Event" data-start="' . esc_attr( $event->start ) . '">' . "\n";
							$list_events .= "\t\t" . '<div class="simcal-event-details">' . $calendar->get_event_html( $event ) . '</div>' . "\n";
							$list_events .= "\t" . '</li>' . "\n";

							$count ++;

							// Event falls within today.
							if ( ( $this->end <= $now ) && ( $this->start >= $now ) ) :
								$day_classes .= ' simcal-today-has-events';
							endif;
							$day_classes .= ' simcal-day-has-events simcal-day-has-' . strval( $count ) . '-events';

							if ( $calendar_classes ) :
								$day_classes .= ' ' . trim( implode( ' ', array_unique( $calendar_classes ) ) );
							endif;

						endif;
					endforeach;
				endforeach;

				$list_events .= '</ul>' . "\n";

				// If events visibility is limited, print the button toggle.
				if ( ( $calendar->events_limit > -1 ) && ( $count > $calendar->events_limit ) ) :
					$list_events .= '<button class="simcal-events-toggle"><i class="simcal-icon-down simcal-icon-animate"></i></button>';
				endif;

				// Print final list of events for the current day.
				$tag = $calendar->compact_list ? 'div' : 'dd';
				echo '<'  . $tag . ' class="' . $day_classes . '" data-events-count="' . strval( $count ) . '">' . "\n";
				echo "\t" . $list_events . "\n";
				echo '</' . $tag . '>' . "\n";

			endforeach;

		else :

			echo "\t" . '<p>';

			$message = get_post_meta( $calendar->id, '_no_events_message', true );

			if ( 'events' == $calendar->group_type ) {
				echo ! empty( $message ) ? $message : __( 'There are no upcoming events.', 'google-calendar-events' );
			} else {
				if ( ! empty( $message ) ) {
					echo $message;
				} else {
					$from = Carbon::createFromTimestamp( $this->start, $calendar->timezone )->getTimestamp();
					$to = Carbon::createFromTimestamp( $this->end, $calendar->timezone )->getTimestamp();
					echo apply_filters( 'simcal_no_events_message', sprintf(
						__( 'Nothing from %1$s to %2$s.', 'google-calendar-events' ),
						date_i18n( $calendar->date_format, $from ),
						date_i18n( $calendar->date_format, $to )
					), $calendar->id, $from, $to );
				}
			}

			echo "\t" . '</p>' . "\n";

		endif;

		echo '</' . $block_tag . '>';

		return ob_get_clean();
	}

	/**
	 * Ajax callback to request a new page.
	 *
	 * @since 3.0.0
	 */
	public function draw_list_ajax() {

		if ( isset( $_POST['ts'] ) && isset( $_POST['id'] ) ) {

			$ts = absint( $_POST['ts'] );
			$id = absint( $_POST['id'] );

			wp_send_json_success( $this->draw_list( $ts, $id ) );

		} else {

			wp_send_json_error( 'Missing arguments in default calendar list ajax request.' );

		}
	}

	/**
	 * Array filter callback.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  int $event Timestamp.
	 *
	 * @return bool
	 */
	private function filter_events_before( $event ) {
		return intval( $event ) >= intval( $this->start );
	}

	/**
	 * Array filter callback.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  int $event Timestamp.
	 *
	 * @return bool
	 */
	private function filter_events_after( $event ) {
		return intval( $event ) < intval( $this->end );
	}

}
