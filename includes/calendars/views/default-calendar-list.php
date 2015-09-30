<?php
/**
 * Default Calendar - List View
 *
 * @package SimpleCalendar/Calendars
 */
namespace SimpleCalendar\Calendars\Views;

use Carbon\Carbon;
use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Calendar_View;
use SimpleCalendar\Calendars\Default_Calendar;
use SimpleCalendar\Events\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Calendar: List View.
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
	 * @param string|Calendar $calendar
	 */
	public function __construct( $calendar = '' ) {
		$this->calendar = $calendar;
	}

	/**
	 * Get the view parent calendar type.
	 *
	 * @return string
	 */
	public function get_parent() {
		return 'default-calendar';
	}

	/**
	 * Get the view type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'list';
	}

	/**
	 * Get the view name.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'List', 'google-calendar-events' );
	}

	/**
	 * Add ajax actions.
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
	 * @param  string $min
	 *
	 * @return array
	 */
	public function scripts( $min = '' ) {
		return array(
			'simcal-qtip' => array(
				'src'       => SIMPLE_CALENDAR_ASSETS . 'js/vendor/qtip' . $min . '.js',
				'deps'      => array( 'jquery' ),
				'ver'       => '2.2.1',
				'in_footer' => true
			),
			'simcal-default-calendar' => array(
				'src'       => SIMPLE_CALENDAR_ASSETS . 'js/default-calendar' . $min . '.js',
				'deps'      => array(
					'jquery',
					'simcal-qtip',
				),
				'var'       => SIMPLE_CALENDAR_VERSION,
				'in_footer' => true,
				'localize'  => array(
					'simcal_default_calendar' => simcal_common_scripts_variables()
				)
			),
		);
	}

	/**
	 * Default calendar list styles.
	 *
	 * Stylesheets to load when this view is displayed.
	 *
	 * @param  string $min = ''
	 *
	 * @return array
	 */
	public function styles( $min = '' ) {
		return array(
			'simcal-default-calendar-list' => array(
				'src'   => SIMPLE_CALENDAR_ASSETS . 'css/default-calendar-list' . $min . '.css',
				'ver'   => SIMPLE_CALENDAR_VERSION,
				'media' => 'all'
			),
		);
	}

	/**
	 * Default calendar list markup.
	 */
	public function html() {

		$calendar = $this->calendar;

		if ( $calendar instanceof Default_Calendar ) {

			$disabled = $calendar->static === true ? ' disabled="disabled"' : '';

			echo '<nav class="simcal-calendar-head">' . "\n";

			echo "\t" . '<div class="simcal-nav">' . "\n";
			echo "\t\t" . '<button class="simcal-nav-button simcal-prev" title="' . __( 'Previous', 'google-calendar-events' ) . '"' . $disabled . '>' . "\n";
			echo "\t\t\t" . '<i class="simcal-icon-left"></i>' . "\n";
			echo "\t\t" . '</button>' . "\n";
			echo "\t" . '</div>' . "\n";

			echo "\t" . '<div class="simcal-nav simcal-current" data-calendar-current="' . $calendar->start . '">' . "\n";
			echo "\t\t" . '<h3 class="simcal-current-label"> </h3>' . "\n";
			echo "\t" . '</div>' . "\n";

			echo "\t" . '<div class="simcal-nav">';
			echo "\t\t" . '<button class="simcal-nav-button simcal-next" title="' . __( 'Next', 'google-calendar-events' ) . '"' . $disabled . '>';
			echo "\t\t\t" . '<i class="simcal-icon-right"></i>' . "\n";
			echo "\t\t" . '</button>' . "\n";
			echo "\t" . '</div>' . "\n";

			echo '</nav>' . "\n";

			echo $this->draw_list( $calendar->start );

			echo '<div class="simcal-ajax-loader simcal-spinner-top" style="display: none;"><i class="simcal-icon-spinner simcal-icon-spin"></i></div>';
		}

	}

	/**
	 * Get events for current display.
	 *
	 * @param  int $timestamp
	 *
	 * @return array
	 */
	private function get_events( $timestamp ) {

		$calendar = $this->calendar;
		$timezone = $calendar->timezone;

		if ( ! $calendar->group_type || ! $calendar->group_span ) {
			return array();
		}

		$current = Carbon::createFromTimestamp( $timestamp, $timezone );
		$prev = clone $current;
		$next = clone $current;

		$this->start = $current->getTimestamp();

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
			$filtered     = array_intersect_key( $events, array_combine( $higher_bound, $higher_bound ) );
			foreach ( $filtered as $timestamp => $events ) {
				$paged_events[ intval( $timestamp ) ] = $events;
			}

		} else {

			foreach( $events as $timestamp => $e ) {
				$second = 0;
				foreach( $e as $event ) {
					$flattened_events[ intval( $timestamp + $second ) ][] = $event;
					$second++;
				}
			}
			ksort( $flattened_events, SORT_NUMERIC );

			$keys  = array_keys( $flattened_events );
			$current = 0;
			foreach( $keys as $timestamp ) {
				if ( $timestamp <= $this->start ) {
					$current++;
				}
			}

			$paged_events = array_slice( $flattened_events, $current, $interval, true );

			$events_end = isset( $keys[ $current + $interval ] ) ? $keys[ $current + $interval ] : $calendar->end;
			$this->end  = $events_end > $calendar->end ? $calendar->end : $events_end;
			// -1 adjusts the interval count to index count, which starts at 0.
			$this->prev = isset( $keys[ $current - $interval - 1 ] ) ? $keys[ $current - $interval - 1 ] : $calendar->earliest_event;
			$this->next = isset( $keys[ $current + $interval - 1 ] ) ? $keys[ $current + $interval - 1 ] : $this->end;

		}

		// Put resulting events in an associative array, with Ymd date as key for easy retrieval in calendar days loop.
		foreach ( $paged_events as $timestamp => $events ) {
			if ( $timestamp < $this->end ) {
				$date = Carbon::createFromTimestamp( $timestamp, 'UTC' )->setTimezone( $calendar->timezone )->format( 'Ymd' );
				$daily_events[ intval( $date ) ][] = $events;
			}
		}
		ksort( $daily_events, SORT_NUMERIC );

		return $daily_events;
	}

	/**
	 * Get calendar list heading.
	 *
	 * Parses calender date format and adapts to current display range.
	 *
	 * @return array
	 */
	private function get_heading() {

		$calendar = $this->calendar;

		$locale = \SimpleCalendar\plugin()->locale;
		$locale = $locale ? substr( $locale, 0, 2 ) : 'en';

		$start = new Carbon( 'now', $calendar->timezone );
		$start->setLocale( $locale );
		$start->setTimestamp( $this->start );

		$end = new Carbon( 'now', $calendar->timezone );
		$end->setLocale( $locale );
		$end->setTimestamp( $this->end );

		$date_format = $this->calendar->date_format;
		$date_order  = simcal_get_date_format_order( $date_format );

		if ( ( $start->day == $end->day ) && ( $start->month == $end->month ) && ( $start->year == $end->year ) ) {
			// Start and end on the same day.
			// e.g. 1 February 2020
			$large = $small = $start->format( $calendar->date_format );
			if ( ( $date_order['d'] !== false ) && ( $date_order['m'] !== false ) ) {
				if ( $date_order['m'] > $date_order['d'] ) {
					if ( $date_order['y'] !== false && $date_order['y'] > $date_order['m'] ) {
						$small = $start->format( 'Y, d M' );
					} else {
						$small = $start->format( 'd M Y' );
					}
				} else {
					if ( $date_order['y'] !== false && $date_order['y'] > $date_order['m'] ) {
						$small = $start->format( 'Y, M d' );
					} else {
						$small = $start->format( 'M d Y' );
					}
				}
			}
		} elseif ( ( $start->month == $end->month ) && ( $start->year == $end->year ) ) {
			// Start and end days on the same month.
			// e.g. August 2020
			if ( $date_order['y'] === false ) {
				// August.
				$large = $small = $start->format( 'F' );
			} else {
				if ( $date_order['y'] < $date_order['m'] ) {
					// 2020 August.
					$large = $start->format( 'Y F' );
					$small = $start->format( 'Y M' );
				} else {
					// August 2020.
					$large = $start->format( 'F Y' );
					$small = $start->format( 'M Y' );
				}
			}
		} elseif ( $start->year == $end->year ) {
			// Start and end days on months of the same year.
			// e.g. August - September 2020
			if ( $date_order['y'] === false ) {
				// August - September.
				$large = $start->format( 'F' ) . ' - ' . $end->format( 'F' );
				$small = $start->format( 'M' ) . ' - ' . $end->format( 'M' );
			} else {
				if ( $date_order['y'] < $date_order['m'] ) {
					// 2020, August - September.
					$large  = $small = $start->format( 'Y' ) . ', ';
					$large .= $start->format( 'F' ) . ' - ' . $end->format( 'F' );
					$small .= $start->format( 'M' ) . ' - ' . $end->format( 'M' );
				} else {
					// August - September, 2020.
					$large  = $start->format( 'F' ) . ' - ' . $end->format( 'F' ) . ', ';
					$small  = $start->format( 'M' ) . ' - ' . $end->format( 'M' ) . ' ';
					$year    = $start->format( 'Y' );
					$large .= $year;
					$small .= $year;
				}
			}
		} else {
			$large = $small = $start->format( 'Y' ) . ' - ' . $end->format( 'Y' );
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
		$day_format = explode( ' ', $calendar->date_format );

		ob_start();

		// Draw the events.

		$block_tag = $calendar->compact_list && ! empty( $current_events ) ? 'div' : 'dl';

		$data_heading = '';
		$heading = $this->get_heading();
		foreach( $heading as $k => $v ) {
			$data_heading .= ' data-heading-' . $k . '="' . $v . '"';
		}

		echo '<' . $block_tag . ' class="simcal-events-list-container"' .
		     ' data-prev="' . $this->prev . '"' .
		     ' data-next="' . $this->next . '"' .
		     $data_heading . '>';

			if ( ! empty( $current_events ) && is_array( $current_events ) ) :

				foreach( $current_events as $ymd => $events ) :

					// Array acrobatics to take into account expanded multi day events option.
					$last = end( $events );
					$day_ts = end( $last )->start;
					reset( $events );

					if ( ! $calendar->compact_list ) :

						$date = new Carbon( 'now', $calendar->timezone );
						$date->setLocale( substr( get_locale(), 0, 2 ) );
						$date->setTimestamp( $day_ts );

						echo "\t" . '<dt class="simcal-day-label">';
						echo '<span>';
						foreach ( $day_format as $format ) {
							echo $format ? '<span class="simcal-date-format" data-date-format="' . $format . '">' . $date->format( $format ) . '</span> ' : ' ';
						}
						echo '</span>';
						echo '</dt>' . "\n";

					endif;

					$list_events = '<ul class="simcal-events">' . "\n";

						$calendar_classes = array();
						$day_classes = 'simcal-weekday-' . date( 'w', $day_ts );

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
							foreach( $day_events as $event ) :
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
										$event_visibility = ' style="display: none"';
									endif;

									$event_color = '';
									if ( ! empty( $event->meta['color'] ) ) {
										$side = is_rtl() ? 'right' : 'left';
										$event_color = ' style="border-' . $side . ': 4px solid ' . $event->meta['color'] . '; padding-' . $side . ': 8px;"';
									}

									$list_events .= "\t" . '<li class="' . $event_classes . '"' . $event_visibility . $event_color . '>' . "\n";
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
						echo ! empty( $message ) ? $message : __( 'Nothing to show.', 'google-calendar-events' );
					} else {
						if ( ! empty( $message ) ) {
							echo $message;
						} else {

							$date = new Carbon( 'now', $calendar->timezone );
							$date->setLocale( substr( get_locale(), 0, 2 ) );
							$from = $date->setTimestamp( $this->start )->format( $calendar->date_format );
							$to   = $date->setTimestamp( $this->end - 1 )->format( $calendar->date_format );

							printf( __( 'Nothing from %1$s to %2$s.', 'google-calendar-events' ), $from, $to );
						}
					}

				echo "\t" . '</p>' . "\n";

			endif;

		echo '</' . $block_tag . '>';

		return ob_get_clean();
	}

	/**
	 * Ajax callback to request a new page.
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
	 * @param  int $event Timestamp.
	 *
	 * @return bool
	 */
	private function filter_events_before( $event ) {
		return intval( $event ) > intval( $this->start );
	}

	/**
	 * Array filter callback.
	 *
	 * @param  int $event Timestamp.
	 *
	 * @return bool
	 */
	private function filter_events_after( $event ) {
		return intval( $event ) < intval( $this->end );
	}

}
