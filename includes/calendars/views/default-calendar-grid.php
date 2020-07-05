<?php
/**
 * Default Calendar - Grid View
 *
 * @package SimpleCalendar/Calendars
 */
namespace SimpleCalendar\Calendars\Views;

use Carbon\Carbon;
use Mexitek\PHPColors\Color;
use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Calendar_View;
use SimpleCalendar\Events\Event;
use SimpleCalendar\Calendars\Default_Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Calendar: Grid View.
 *
 * @since  3.0.0
 */
class Default_Calendar_Grid implements Calendar_View {

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
		return 'grid';
	}

	/**
	 * Get the view name.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Grid', 'google-calendar-events' );
	}

	/**
	 * Add ajax actions.
	 *
	 * @since 3.0.0
	 */
	public function add_ajax_actions() {
		add_action( 'wp_ajax_simcal_default_calendar_draw_grid', array( $this, 'draw_grid_ajax' ) );
		add_action( 'wp_ajax_nopriv_simcal_default_calendar_draw_grid', array( $this, 'draw_grid_ajax' ) );
	}

	/**
	 * Default calendar grid scripts.
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
				'src'       => SIMPLE_CALENDAR_ASSETS . 'js/vendor/jquery.qtip' . $min . '.js',
				'deps'      => array( 'jquery' ),
				'in_footer' => true,
			),
			'simcal-fullcal-moment' => array(
				'src'       => SIMPLE_CALENDAR_ASSETS . 'js/vendor/moment' . $min . '.js',
				'deps'      => array( 'jquery' ),
				'in_footer' => true,
			),
			'simcal-moment-timezone' => array(
				'src'       => SIMPLE_CALENDAR_ASSETS . 'js/vendor/moment-timezone-with-data' . $min . '.js',
				'deps'      => array( 'jquery' ),
				'in_footer' => true,
			),
			'simcal-default-calendar' => array(
				'src'       => SIMPLE_CALENDAR_ASSETS . 'js/default-calendar' . $min . '.js',
				'deps'      => array(
					'jquery',
					'simcal-qtip',
					'simcal-fullcal-moment',
					'simcal-moment-timezone',
				),
				'in_footer' => true,
				'localize'  => array(
					'simcal_default_calendar' => simcal_common_scripts_variables(),
				),
			),
		);
	}

	/**
	 * Default calendar grid styles.
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
			'simcal-qtip' => array(
				'src'   => SIMPLE_CALENDAR_ASSETS . 'css/vendor/jquery.qtip' . $min . '.css',
				'media' => 'all',
			),
			'simcal-default-calendar-grid' => array(
				'src'   => SIMPLE_CALENDAR_ASSETS . 'css/default-calendar-grid' . $min . '.css',
				'deps'  => array(
					'simcal-qtip',
				),
				'media' => 'all',
			),
			'simcal-default-calendar-list' => array(
				'src'   => SIMPLE_CALENDAR_ASSETS . 'css/default-calendar-list' . $min . '.css',
				'deps'  => array(
					'simcal-qtip',
				),
				'media' => 'all',
			),
		);
	}

	/**
	 * Default calendar grid markup.
	 *
	 * @since  3.0.0
	 */
	public function html() {

		$calendar = $this->calendar;

		if ( $calendar instanceof Default_Calendar ) {

			?>

			<?php edit_post_link( __( 'Edit Calendar', 'google-calendar-events' ), '<p class="simcal-align-right"><small>', '</small></p>', $calendar->id ); ?>

            <table class="simcal-calendar-grid"
                   data-event-bubble-trigger="<?php echo $calendar->event_bubble_trigger; ?>">
                <thead class="simcal-calendar-head">
                <tr>
					<?php if ( ! $calendar->static ) { ?>
                        <th class="simcal-nav simcal-prev-wrapper" colspan="<?php echo apply_filters( 'simcal_prev_cols', '1' ); ?>">
                            <button class="simcal-nav-button simcal-month-nav simcal-prev" title="<?php _e( 'Previous Month', 'google-calendar-events' ); ?>"><i class="simcal-icon-left"></i></button>
                        </th>
					<?php } ?>
                    <th colspan="<?php echo apply_filters( 'simcal_current_cols', $calendar->static ? '7' : '5' ); ?>"
                        class="simcal-nav simcal-current"
                        data-calendar-current="<?php echo $calendar->start; ?>">
						<?php

						echo '<h3>';

						// Display month and year according to user date format preference.

						$year_pos  = strcspn( $calendar->date_format, 'Y y' );
						$month_pos = strcspn( $calendar->date_format, 'F M m n' );

						$current = array( 'month' => 'F', 'year' => 'Y' );

						if ( $year_pos < $month_pos ) {
							$current = array_reverse( $current );
						}

						foreach ( $current as $k => $v ) {
							echo ' <span class="simcal-current-' . $k , '">' . date_i18n( $v, $calendar->start ) . '</span> ';
						}

						echo '</h3>';

						?>
                    </th>
					<?php if ( ! $calendar->static ) { ?>
                        <th class="simcal-nav simcal-next-wrapper" colspan="<?php echo apply_filters( 'simcal_next_cols', '1' ); ?>">
                            <button class="simcal-nav-button simcal-month-nav simcal-next" title="<?php _e( 'Next Month', 'google-calendar-events' ); ?>"><i class="simcal-icon-right"></i></button>
                        </th>
					<?php } ?>
                </tr>
                <tr>
					<?php

					// Print day names in short or long form for different viewport sizes.

					$week_starts     = $calendar->week_starts;
					$week_days_short = simcal_get_calendar_names_i18n( 'day', 'short' );
					$week_days_full  = simcal_get_calendar_names_i18n( 'day', 'full' );

					for ( $i = $week_starts; $i <= 6; $i ++ ) :

						?>
                        <th class="simcal-week-day simcal-week-day-<?php echo $i ?>"
                            data-screen-small="<?php echo mb_substr( $week_days_short[ $i ], 0, 1, 'UTF-8' ); ?>"
                            data-screen-medium="<?php echo $week_days_short[ $i ]; ?>"
                            data-screen-large="<?php echo $week_days_full[ $i ]; ?>"><?php echo $week_days_short[ $i ]; ?></th>
						<?php

					endfor;

					if ( $week_starts !== 0 ) :
						for ( $i = 0; $i < $week_starts; $i ++ ) :

							?>
                            <th class="simcal-week-day simcal-week-day-<?php echo $i ?>"
                                data-screen-small="<?php echo mb_substr( $week_days_short[ $i ], 0, 1, 'UTF-8' ); ?>"
                                data-screen-medium="<?php echo $week_days_short[ $i ]; ?>"
                                data-screen-large="<?php echo $week_days_full[ $i ]; ?>"><?php echo $week_days_short[ $i ]; ?></th>
							<?php

						endfor;
					endif;

					?>
                </tr>
                </thead>

				<?php echo $this->draw_month( date( 'n', $calendar->start ), date( 'Y', $calendar->start ) ); ?>

            </table>

			<?php

			echo '<div class="simcal-ajax-loader simcal-spinner-top" style="display: none;"><i class="simcal-icon-spinner simcal-icon-spin"></i></div>';
		}
	}

	/**
	 * Added sorting for events with the same start time to be sorted
	 * alphabetically.
	 *
	 * @since  3.1.28
	 * @access private
	 */
	private static function cmp( $a, $b ) {
		if ($a->start == $b->start) {
			if($a->title == $b->title) {
				return 0;
			}
			return ($a->title < $b->title) ? -1 : 1;
		}
		else {
			return ($a->start < $b->start) ? -1 : 1;
		}
	}

	/**
	 * Make a calendar grid.
	 *
	 * Outputs an html calendar according to month and year passed in arguments.
	 * Loosely inspired by: http://davidwalsh.name/php-calendar
	 * Adjusted by timezone and with an arbitrary week start day.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  int $month The month to print (two digits).
	 * @param  int $year  The corresponding year (four digits).
	 * @param  int $id    The calendar id.
	 *
	 * @return string
	 */
	private function draw_month( $month, $year, $id = 0 ) {

		$calendar = $this->calendar;
		if ( empty( $calendar ) ) {
			$calendar = simcal_get_calendar( intval( $id ) );
			if ( ! $calendar ) {
				return '';
			}
		}

		$events = $calendar->events;

		// Variables to cycle days in current month and find today in calendar.
		$now         = $calendar->now;
		$current     = Carbon::create( $year, $month, 1, 0, 0, 0, $calendar->timezone );
		$current_min = $current->getTimestamp();
		$current_max = $current->endOfDay()->getTimestamp();

		// Calendar grid variables.
		$week_starts   = $calendar->week_starts;
		$week_of_year  = $current->weekOfYear;   // Relative count of the week number of the year.
		$month_starts  = $current->dayOfWeek;    // Day upon which the month starts.
		$days_in_month = $current->daysInMonth;  // Number of days in the given month.

		// Set current month events timestamp boundaries.
		$this->start = $current_min;
		$this->end   = $current->endOfMonth()->getTimestamp();

		// Get daily events for this month.
		if ( $events && is_array( $events ) ) {

			// Filter events within the boundaries previously set above.
			$timestamps   = array_keys( $events );
			$lower_bound  = array_filter( $timestamps, array( $this, 'filter_events_before' ) );
			$higher_bound = array_filter( $lower_bound, array( $this, 'filter_events_after' ) );
			$filtered     = ( is_array( $events ) && is_array( $higher_bound) ) && ! empty( $events ) && ! empty( $higher_bound ) ? array_intersect_key( $events, array_combine( $higher_bound, $higher_bound ) ) : array();

			// Put resulting events in an associative array, with day of the month as key for easy retrieval in calendar days loop.
			$day_events = array();
			foreach ( $filtered as $timestamp => $events_in_day ) {
				foreach ( $events_in_day as $event ) {
					if ( $event instanceof Event ){
						$day = intval( Carbon::createFromTimestamp( $timestamp, $calendar->timezone )->endOfDay()->day );
						$day_events[ $day ][] = $event;
					}
				}
			}

			ksort( $day_events, SORT_NUMERIC );
		}

		ob_start();

		echo '<tbody class="simcal-month simcal-month-' . $month . '">' . "\n";
		echo "\t" . '<tr class="simcal-week simcal-week-' . $week_of_year . '">';

		$days_in_row = 0;
		// Week may start on an arbitrary day (sun, 0 - sat, 6).
		$week_day = $week_starts;

		// This fixes a possible bug when a month starts by Sunday (0).
		if ( 0 !== $week_starts ) {
			$b = $month_starts === 0 ? 7 : $month_starts;
		} else {
			$b = $month_starts;
		}

		// Void days in first week.
		for ( $a = $week_starts; $a < $b; $a++ ) :

			$last_void_day_class = ( $a === ( $b - 1 ) ) ? 'simcal-day-void-last' : '';

			echo '<td class="simcal-day simcal-day-void ' . $last_void_day_class . '"></td>' . "\n";

			// Reset day of the week count (sun, 0 - sat, 6).
			if ( $week_day === 6 ) {
				$week_day = -1;
			}
			$week_day++;

			$days_in_row++;

		endfor;

		// Actual days of the month.
		for ( $day = 1; $day <= $days_in_month; $day++ ) :

			$count = 0;
			$calendar_classes = array();
			$day_classes = 'simcal-day-' . $day . ' simcal-weekday-' . $week_day;

			$border_style = $bg_color = $color = '';

			// Is this the present, the past or the future, Doc?
			if ( $current_min <= $now && $current_max >= $now ) {
				$day_classes .= ' simcal-today simcal-present simcal-day';
				$the_color = new Color( $calendar->today_color );
				$bg_color = '#' . $the_color->getHex();
				$color = $the_color->isDark() ? '#ffffff' : '#000000';
				$border_style = ' style="border: 1px solid ' . $bg_color . ';"';
			} elseif ( $current_max < $now ) {
				$day_classes .= ' simcal-past simcal-day';
			} elseif ( $current_min > $now ) {
				$day_classes .= ' simcal-future simcal-day';
			}

			// Print events for the current day in loop, if found any.
			if ( isset( $day_events[ $day ] ) ) :

				$bullet_colors = array();

				$list_events = '<ul class="simcal-events">';

				usort($day_events[ $day ], array($this,'cmp'));

				foreach ( $day_events[ $day ] as $event ) :

					$event_classes = $event_visibility = '';

					if ( $event instanceof Event ) :

						// Store the calendar id where the event belongs (useful in grouped calendar feeds)
						$calendar_class  = 'simcal-events-calendar-' . strval( $event->calendar );
						$calendar_classes[] = $calendar_class ;

						$recurring     = $event->recurrence ? 'simcal-event-recurring ' : '';
						$has_location  = $event->venue ? 'simcal-event-has-location ' : '';

						$event_classes  .= 'simcal-event ' . $recurring . $has_location . $calendar_class . ' simcal-tooltip';

						// Toggle some events visibility if more than optional limit.
						if ( ( $calendar->events_limit > -1 )  && ( $count >= $calendar->events_limit ) ) :
							$event_classes    .= ' simcal-event-toggled';
							$event_visibility  = ' style="display: none"';
						endif;

						// Event title in list.
						$title = ! empty( $event->title ) ? trim( $event->title ) : __( 'Event', 'google-calendar-events' );
						if ( $calendar->trim_titles >= 1 ) {
							$title = strlen( $title ) > $calendar->trim_titles ? mb_substr( $title, 0, $calendar->trim_titles ) . '&hellip;' : $title;
						}

						// Event color.
						$bullet = '';
						//$bullet_color = '#000';
						$event_color = $event->get_color();
						if ( ! empty( $event_color ) ) {
							$bullet = '<span style="color: ' . $event_color . ';">&#9632;</span> ';
							$bullet_colors[] = $event_color;
						} else {
							$bullet_colors[] = '#000';
						}

						// Event contents.
						$list_events .= "\t" . '<li class="' . $event_classes . '"' . $event_visibility . ' itemscope itemtype="http://schema.org/Event">' . "\n";
						$list_events .= "\t\t" . '<span class="simcal-event-title">' . $bullet . $title . '</span>' . "\n";
						$list_events .= "\t\t" . '<div class="simcal-event-details simcal-tooltip-content" style="display: none;">' . $calendar->get_event_html( $event ) . '</div>' . "\n";
						$list_events .= "\t" . '</li>' . "\n";

						$count ++;

					endif;

				endforeach;

				if ( ( $current_min <= $now ) && ( $current_max >= $now ) ) {
					$day_classes .= ' simcal-today-has-events';
				}
				$day_classes .= ' simcal-day-has-events simcal-day-has-' . strval( $count ) . '-events';

				if ( $calendar_classes ) {
					$day_classes .= ' ' . trim( implode( ' ', array_unique( $calendar_classes ) ) );
				}

				$list_events .= '</ul>' . "\n";

				// Optional button to toggle hidden events in list.
				if ( ( $calendar->events_limit > -1 ) && ( $count > $calendar->events_limit ) ) :
					$list_events .= '<button class="simcal-events-toggle"><i class="simcal-icon-down simcal-icon-animate"></i></button>';
				endif;

			else :

				// Empty cell for day with no events.
				$list_events = '<span class="simcal-no-events"></span>';

			endif;

			// The actual days with numbers and events in each row cell.
			echo '<td class="' . $day_classes . '" data-events-count="' . strval( $count ) . '">' . "\n";

			if ( $color ) {
				$day_style = ' style="background-color: ' . $bg_color . '; color: ' . $color .'"';
			} elseif ( $count > 0 ) {
				$the_color = new Color( $calendar->days_events_color );
				$color = ! $color ? ( $the_color->isDark() ? '#ffffff' : '#000000' ) : $color;
				$bg_color = ! $bg_color ? '#' . $the_color->getHex() : $bg_color;
				$day_style = ' style="background-color: ' . $bg_color . '; color: ' . $color .'"';
			} else {
				$day_style = '';
			}

			echo "\t" . '<div' . $border_style . '>' . "\n";
			echo "\t\t" . '<span class="simcal-day-label simcal-day-number"' . $day_style . '>' . $day . '</span>' . "\n";
			echo "\t\t" . $list_events . "\n";
			echo "\t\t";
			echo '<span class="simcal-events-dots" style="display: none;">';

			// Event bullets for calendar mobile mode.
			for( $i = 0; $i < $count; $i++ ) {
				echo '<b style="color: ' . $bullet_colors[ $i ] . ';"> &bull; </b>';
			}

			echo '</span>' . "\n";
			echo "\t" . '</div>' . "\n";
			echo '</td>' . "\n";

			// Reset day of the week count (sun, 0 - sat, 6).
			if ( $week_day === 6 ) {
				$week_day = - 1;
			}
			$week_day++;

			// Reset count of days for this row (0-6).
			if ( $days_in_row === 6 ) :

				// Close the week row.
				echo '</tr>';

				// Open a new week row.
				if ( $day < $days_in_month ) {
					echo '<tr class="simcal-week simcal-week-' . $week_of_year++ . '">' . "\n";
				}

				$days_in_row = -1;

			endif;

			$days_in_row++;

			$current_min = Carbon::createFromTimestamp( $current_min, $calendar->timezone )->addDay()->getTimestamp();
			$current_max = Carbon::createFromTimestamp( $current_max, $calendar->timezone )->addDay()->getTimestamp();

		endfor;

		// Void days at the end of the month.
		$remainder_days = ( 6 - $days_in_row );

		for ( $i = 0; $i <= $remainder_days; $i ++ ) {

			$last_void_day_class = ( $i == $remainder_days ) ? 'simcal-day-void-last' : '';

			echo '<td class="simcal-day simcal-day-void ' . $last_void_day_class . '"></td>' . "\n";

			$week_day++;
		}

		echo "\t" . '</tr>' . "\n";
		echo '</tbody>' . "\n";

		return ob_get_clean();
	}

	/**
	 * Ajax callback to request a new month.
	 *
	 * @since 3.0.0
	 */
	public function draw_grid_ajax() {

		if ( isset( $_POST['month'] ) && isset( $_POST['year'] ) && isset( $_POST['id'] ) ) {

			$month = absint( $_POST['month'] );
			$year  = absint( $_POST['year'] );
			$id    = absint( $_POST['id'] );

			wp_send_json_success( $this->draw_month( $month, $year, $id ) );

		} else {

			wp_send_json_error( 'Missing arguments in default calendar grid ajax request.' );

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