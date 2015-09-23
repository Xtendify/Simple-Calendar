<?php
/**
 * Events Collection
 *
 * @package SimpleCalendar/Events
 */
namespace SimpleCalendar\Events;

use Carbon\Carbon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Events.
 *
 * A collection of Event objects.
 */
class Events {

	/**
	 * Events.
	 *
	 * @access public
	 * @var array
	 */
	protected $events = array();

	/**
	 * Timezone.
	 *
	 * @access public
	 * @var string
	 */
	protected $timezone = 'UTC';

	/**
	 * Constructor.
	 *
	 * @param array $events
	 */
	public function __construct( $events = array() ) {
		$this->set_events( $events );
	}

	/**
	 * Get events.
	 *
	 * @param  string|int $n Amount of events (optional).
	 *
	 * @return array
	 */
	public function get_events( $n = '' ) {
		if ( ! empty( $n ) && ! empty( $this->events ) ) {
			$length = absint( $n );
			return array_slice( $this->events, 0, $length, true );
		}
		return $this->events;
	}

	/**
	 * Set events.
	 *
	 * @param array $events
	 */
	public function set_events( array $events ) {
		$this->events = $events;
	}

	/**
	 * Set timezone.
	 *
	 * @param  string $timezone
	 *
	 * @return Events
	 */
	public function set_timezone( $timezone ) {
		$this->timezone = simcal_esc_timezone( $timezone, $this->timezone );
		return $this;
	}

	/**
	 * Shift events.
	 *
	 * @param  int $n
	 *
	 * @return Events
	 */
	public function shift( $n ) {
		if ( ! empty( $this->events ) ) {
			$offset = intval( $n );
			$length = count( $this->events );
			$this->set_events( array_slice( $this->events, $offset, $length, true ) );
		}
		return $this;
	}

	/**
	 * Filter private events.
	 *
	 * @return Events
	 */
	public function private_only() {
		$this->set_events( $this->filter_property( 'public', 'hide' ) );
		return $this;
	}

	/**
	 * Filter public events.
	 *
	 * @return Events
	 */
	public function public_only() {
		$this->set_events( $this->filter_property( 'public', 'show' ) );
		return $this;
	}

	/**
	 * Filter recurring events in the current block.
	 *
	 * @return Events
	 */
	public function recurring() {
		$this->set_events( $this->filter_property( 'recurrence', 'show' ) );
		return $this;
	}

	/**
	 * Filter non recurring events in the current block.
	 *
	 * @return Events
	 */
	public function not_recurring() {
		$this->set_events( $this->filter_property( 'recurrence', 'hide' ) );
		return $this;
	}

	/**
	 * Filter whole day events in the current block.
	 *
	 * @return Events
	 */
	public function whole_day() {
		$this->set_events( $this->filter_property( 'whole_day', 'show' ) );
		return $this;
	}

	/**
	 * Filter non whole day in the current block.
	 *
	 * @return Events
	 */
	public function not_whole_day() {
		$this->set_events( $this->filter_property( 'whole_day', 'hide' ) );
		return $this;
	}

	/**
	 * Filter events spanning multiple days in the current block.
	 *
	 * @return Events
	 */
	public function multi_day() {
		$this->set_events( $this->filter_property( 'multiple_days', 'show' ) );
		return $this;
	}

	/**
	 * Filter events that do not span multiple days in the current block.
	 *
	 * @return Events
	 */
	public function single_day() {
		$this->set_events( $this->filter_property( 'multiple_days', 'hide' ) );
		return $this;
	}

	/**
	 * Filter events in the current block that have a location.
	 *
	 * @return Events
	 */
	public function with_location() {
		$this->set_events( $this->filter_property( 'venue', 'show' ) );
		return $this;
	}

	/**
	 * Filter events in the current block that do not have a location.
	 *
	 * @return Events
	 */
	public function without_location() {
		$this->set_events( $this->filter_property( 'venue', 'hide' ) );
		return $this;
	}

	/**
	 * Filter whole day events.
	 *
	 * @access private
	 *
	 * @param  string $property
	 * @param  string $toggle
	 *
	 * @return array
	 */
	private function filter_property( $property, $toggle ) {
		$filtered = array();
		if ( ! empty( $this->events ) ) {
			foreach ( $this->events as $ts => $events ) {
				foreach ( $events as $event ) {
					if ( 'hide' == $toggle ) {
						if ( ! $event->$property ) {
							$filtered[ $ts ][] = $event;
						}
					} elseif ( 'show' == $toggle ) {
						if ( $event->$property ) {
							$filtered[ $ts ][] = $event;
						}
					}
				}
			}
		}
		return $filtered;
	}

	/**
	 * Filter events in the past.
	 *
	 * @param  int|string $present
	 *
	 * @return Events
	 */
	public function future( $present = '' ) {
		$last = $this->get_last();
		$to = $last instanceof Event ? $last->start_utc : false;
		if ( $to ) {
			if ( empty( $present ) ) {
				$present = Carbon::now( $this->timezone )->getTimestamp();
			}
			$this->set_events( $this->filter_events( intval( $present ), $to ) );
		}
		return $this;
	}

	/**
	 * Filter events in the future.
	 *
	 * @param  int|string $present
	 *
	 * @return Events
	 */
	public function past( $present = '') {
		$first = $this->get_last();
		$from  = $first instanceof Event ? $first->start_utc : false;
		if ( $from ) {
			if ( empty( $present ) ) {
				$present = Carbon::now( $this->timezone )->getTimestamp();
			}
			$this->set_events( $this->filter_events( $from, intval( $present ) ) );
		}
		return $this;
	}

	/**
	 * Filter events after time.
	 *
	 * @param  int|string|\DateTime|Carbon $time
	 *
	 * @return Events
	 */
	public function after( $time ) {
		$dt = $this->parse( $time );
		return ! is_null( $dt ) ? $this->future( $dt->getTimestamp() ) : $this;
	}

	/**
	 * Filter events before time.
	 *
	 * @param  int|string|\DateTime|Carbon $time
	 *
	 * @return Events
	 */
	public function before( $time ) {
		$dt = $this->parse( $time );
		return ! is_null( $dt ) ? $this->past( $dt->getTimestamp() ) : $this;
	}

	/**
	 * Filter events from a certain time onwards.
	 *
	 * @param  int|string|\DateTime|Carbon $time
	 *
	 * @return Events
	 */
	public function from( $time ) {
		$last = $this->parse( $time );
		if ( ! is_null( $last ) ) {
			$this->set_events( $this->filter_events( $time, $last->getTimestamp() ) );
		}
		return $this;
	}

	/**
	 * Filter events up to to a certain time.
	 *
	 * @param  int|string|\DateTime|Carbon $time
	 *
	 * @return Events
	 */
	public function to( $time ) {
		$first = $this->parse( $time );
		if ( ! is_null( $first ) ) {
			$this->set_events( $this->filter_events( $first->getTimestamp(), $time ) );
		}
		return $this;
	}

	/**
	 * Parse time.
	 *
	 * @param  int|string|\DateTime|Carbon $time
	 *
	 * @return null|Carbon
	 */
	private function parse( $time ) {
		if ( is_int( $time ) ) {
			return Carbon::createFromTimestamp( $time, $this->timezone );
		} elseif ( is_string( $time ) && ! empty( $time ) ) {
			return Carbon::parse( $time, $this->timezone );
		} elseif ( $time instanceof Carbon ) {
			return $time->setTimezone( $this->timezone );
		} elseif ( $time instanceof \DateTime ) {
			return Carbon::instance( $time )->setTimezone( $this->timezone );
		} else {
			return null;
		}
	}

	/**
	 * Get first event of the current block.
	 *
	 * @return null|Event
	 */
	public function get_first() {
		return array_shift( $this->events );
	}

	/**
	 * Get last event of the current block.
	 *
	 * @return null|Event
	 */
	public function get_last() {
		return array_pop( $this->events );
	}

	/**
	 * Get the closest event in the future.
	 *
	 * @return null|Event
	 */
	public function get_upcoming() {
		return $this->get_closest( 'future' );
	}

	/**
	 * Get the closest event in the past.
	 *
	 * @return null|Event
	 */
	public function get_latest() {
		return $this->get_closest( 'past' );
	}

	/**
	 * Get the closest event compared to now.
	 *
	 * @access private
	 *
	 * @param  string $dir Direction: 'future' or 'past'.
	 *
	 * @return null|Event
	 */
	private function get_closest( $dir ) {
		if ( 'future' == $dir ) {
			return array_shift( $this->future()->get_events() );
		} elseif ( 'past' == $dir ) {
			return array_shift( $this->past()->get_events() );
		}
		return null;
	}

	/**
	 * Get events for the given year.
	 *
	 * @param  int $year Year.
	 *
	 * @return array Multidimensional array with month number, week number and Event objects for each weekday.
	 */
	public function get_year( $year ) {
		$y = intval( $year );
		$months = array();
		for ( $m = 1; $m <= 12; $m++ ) {
			$months[ strval( $m ) ] = $this->get_month( $y, $m );
		}
		return $months;
	}

	/**
	 * Get events for the given month in the given year.
	 *
	 * @param  int $year  Year.
	 * @param  int $month Month number.
	 *
	 * @return array Multidimensional array with week number, day of the week and array of Event objects for each week day.
	 */
	public function get_month( $year, $month ) {
		$y = intval( $year );
		$m = min( max( 1, absint( $month ) ), 12 );
		$days  = Carbon::createFromDate( $y, $m, 2, $this->timezone )->startOfMonth()->daysInMonth;
		$weeks = array();
		for ( $d = 1; $d < $days; $d++ ) {
			$current = Carbon::createFromDate( $y, $m, $d );
			$week = $current->weekOfYear;
			$day  = $current->dayOfWeek;
			$weeks[ strval( $week ) ][ strval( $day ) ] = $this->get_day( $y, $m, $d );
		}
		return $weeks;
	}

	/**
	 * Get events for the given week in the given year.
	 *
	 * @param  int $year Year.
	 * @param  int $week Week number.
	 *
	 * @return array Associative array with day of the week for key and array of Event objects for value.
	 */
	public function get_week( $year, $week ) {
		$y = intval( $year );
		$w = absint( $week );
		$m = date( 'n', strtotime( strval( $y ) . '-W' . strval( $w ) ) );
		$month_dt = Carbon::createFromDate( $y, $m, 2, $this->timezone );
		$days = array();
		for ( $d = 1; $d < $month_dt->daysInMonth; $d++ ) {
			$current = Carbon::createFromDate( $y, $m, $d );
			if ( $w == $current->weekOfYear ) {
				$days[ strval( $current->dayOfWeek ) ] = $this->get_day( $y, $m, $d );
			}
		}
		return $days;
	}

	/**
	 * Get events for the given day of the given month in the given year.
	 *
	 * @param  int $year  Year.
	 * @param  int $month Month number.
	 * @param  int $day   Day of the month number.
	 *
	 * @return array Event objects for the day.
	 */
	public function get_day( $year, $month, $day ) {
		$y = intval( $year );
		$m = min( max( 1, absint( $month ) ), 12 );
		$d = min( absint( $day ), 31 );
		$from = Carbon::createFromDate( $y, $m, $d, $this->timezone )->startOfDay()->getTimestamp();
		$to   = Carbon::createFromDate( $y, $m, $d, $this->timezone )->endOfDay()->getTimestamp();
		return $this->filter_events( $from, $to );
	}

	/**
	 * Get events for today.
	 *
	 * @return array Event objects for today.
	 */
	public function get_today() {
		$start = Carbon::today( $this->timezone )->startOfDay()->getTimestamp();
		$end   = Carbon::today( $this->timezone )->endOfDay()->getTimestamp();
		return $this->filter_events( $start, $end );
	}

	/**
	 * Get events for tomorrow.
	 *
	 * @return array Event objects for tomorrow.
	 */
	public function get_tomorrow() {
		$start = Carbon::tomorrow( $this->timezone )->startOfDay()->getTimestamp();
		$end   = Carbon::tomorrow( $this->timezone )->endOfDay()->getTimestamp();
		return $this->filter_events( $start, $end );
	}

	/**
	 * Get events for yesterday.
	 *
	 * @return array Event objects for yesterday.
	 */
	public function get_yesterday() {
		$start = Carbon::yesterday( $this->timezone )->startOfDay()->getTimestamp();
		$end   = Carbon::yesterday( $this->timezone )->endOfDay()->getTimestamp();
		return $this->filter_events( $start, $end );
	}

	/**
	 * Filter events by timestamps.
	 *
	 * @param  int $from Lower bound timestamp.
	 * @param  int $to   Upper bound timestamp.
	 *
	 * @return array Filtered array of Event objects.
	 */
	private function filter_events( $from, $to ) {
		$timestamps   = array_keys( $this->events );
		$lower_bound  = array_filter( $timestamps,  function( $ts ) use( $from ) {
			return intval( $ts ) > intval( $from );
		} );
		$higher_bound = array_filter( $lower_bound, function( $ts ) use( $to ) {
			return intval( $ts ) > intval( $to );
		} );
		$filtered = array_combine( $higher_bound, $higher_bound );
		return array_intersect_key( $this->events, $filtered );
	}

}
