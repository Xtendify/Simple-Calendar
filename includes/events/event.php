<?php
/**
 * Event
 *
 * @package SimpleCalendar/Events
 */
namespace SimpleCalendar\Events;

use Carbon\Carbon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Event.
 *
 * @since 3.0.0
 */
class Event {

	/**
	 * Event type.
	 *
	 * @access public
	 * @var string
	 */
	public $type = '';

	/**
	 * Event title.
	 *
	 * @access public
	 * @var string
	 */
	public $title = '';

	/**
	 * Event description.
	 *
	 * @access public
	 * @var string
	 */
	public $description = '';

	/**
	 * Event visibility.
	 *
	 * @access public
	 * @var string
	 */
	public $visibility = '';

	/**
	 * Event privacy.
	 *
	 * @access public
	 * @var bool
	 */
	public $public = false;

	/**
	 * Event link URL.
	 *
	 * @access public
	 * @var
	 */
	public $link = '';

	/**
	 * Event unique identifier.
	 *
	 * @access public
	 * @var string
	 */
	public $uid = '';

	/**
	 * Event parent calendar id.
	 *
	 * @access public
	 * @var int
	 */
	public $calendar = 0;

	/**
	 * Event parent calendar timezone.
	 *
	 * @access public
	 * @var string
	 */
	public $timezone = 'UTC';

	/**
	 * Event start time.
	 *
	 * @access public
	 * @var int
	 */
	public $start = 0;

	/**
	 * Event start time in GMT.
	 *
	 * @access public
	 * @var int
	 */
	public $start_utc = 0;

	/**
	 * Event start datetime object.
	 *
	 * @access public
	 * @var Carbon
	 */
	public $start_dt = null;

	/**
	 * Event start time timezone.
	 *
	 * @access public
	 * @var string
	 */
	public $start_timezone = 'UTC';

	/**
	 * Event location at event start.
	 *
	 * @access public
	 * @var array
	 */
	public $start_location = false;

	/**
	 * Event end time.
	 *
	 * @access public
	 * @var false|int
	 */
	public $end = false;

	/**
	 * Event end time in GMT.
	 *
	 * @access public
	 * @var false|int
	 */
	public $end_utc = false;

	/**
	 * Event end datetime object.
	 *
	 * @access public
	 * @var null|Carbon
	 */
	public $end_dt = null;

	/**
	 * Event end time timezone.
	 *
	 * @access public
	 * @var string
	 */
	public $end_timezone = 'UTC';

	/**
	 * Event location at event end.
	 *
	 * @access public
	 * @var array
	 */
	public $end_location = false;

	/**
	 * Event has location.
	 *
	 * @access public
	 * @var bool
	 */
	public $venue = false;

	/**
	 * Whole day event.
	 *
	 * @access public
	 * @var bool
	 */
	public $whole_day = false;

	/**
	 * Multiple days span.
	 *
	 * @access public
	 * @var bool|int
	 */
	public $multiple_days = false;

	/**
	 * Recurring event.
	 *
	 * @access public
	 * @var false|array
	 */
	public $recurrence = false;

	/**
	 * Event meta.
	 *
	 * @access public
	 * @var array
	 */
	public $meta = array();

	/**
	 * Event default template.
	 *
	 * @access public
	 * @var string
	 */
	public $template = '';

	/**
	 * Event constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param array $event
	 */
	public function __construct( array $event ) {

		/* ================= *
		 * Event Identifiers *
		 * ================= */

		// Event unique id.
		$this->uid = isset( $event['uid'] ) ? trim( strval( $event['uid'] ) ) : '';

		// Parent calendar id.
		$this->calendar = isset( $event['calendar'] ) ? absint( $event['calendar'] ) : 0;

		// Parent calendar timezone.
		$this->timezone = isset( $event['timezone'] ) ? esc_attr( $event['timezone'] ) : 'UTC';

		/* ============= *
		 * Event Content *
		 * ============= */

		// Event title.
		$this->title = ! empty( $event['title'] ) ? esc_attr( $event['title'] ) : '';

		// Event description.
		$this->description = ! empty( $event['description'] ) ? $event['description'] : '';

		// Event link URL.
		$this->link = ! empty( $event['link'] ) ? esc_url_raw( $event['link'] ) : '';

		// Event visibility.
		if ( ! empty( $event['visibility'] ) ) {
			$this->visibility = esc_attr( $event['visibility'] );
			$this->public = $this->visibility == 'public' ? true : false;
		}

		/* =========== *
		 * Event Start *
		 * =========== */

		if ( ! empty( $event['start'] ) ) {
			$this->start = is_numeric( $event['start'] ) ? $this->esc_midnight( intval( $event['start'] ), $this->timezone, 'start' ) : 0;
			if ( ! empty( $event['start_utc'] ) ) {
				$this->start_utc = is_numeric( $event['start_utc'] ) ? $this->esc_midnight( intval( $event['start_utc'] ), 'UTC', 'start' ) : 0;
			}
			if ( ! empty( $event['start_timezone'] ) ) {
				$this->start_timezone = esc_attr( $event['start_timezone'] );
			}
			$this->start_dt = Carbon::createFromTimestamp( $this->start, $this->start_timezone );
			$start_location = isset( $event['start_location'] ) ? $event['start_location'] : '';
			$this->start_location = $this->esc_location( $start_location );
		}

		/* ========= *
		 * Event End *
		 * ========= */

		if ( ! empty( $event['end'] ) ) {
			$this->end = is_numeric( $event['end'] ) ? $this->esc_midnight( intval( $event['end'] ), $this->timezone, 'end' ) : false;
			if ( ! empty( $event['end_timezone'] ) ) {
				$this->end_timezone = esc_attr( $event['end_timezone'] );
			}
			if ( ! empty( $event['end_utc'] ) ) {
				$this->end_utc = is_numeric( $event['end_utc'] ) ? $this->esc_midnight( intval( $event['end_utc'] ), 'UTC', 'end' ) : false;
				$this->end_dt = Carbon::createFromTimestamp( $this->end, $this->end_timezone );
			}
			$end_location = isset( $event['end_location'] ) ? $event['end_location'] : '';
			$this->end_location = $this->esc_location( $end_location );
		}

		/* ================== *
		 * Event Distribution *
		 * ================== */

		// Whole day event.
		if ( ! empty( $event['whole_day'] ) ) {
			$this->whole_day = true === $event['whole_day'] ? true: false;
		}

		// Multi day event.
		if ( ! empty( $event['multiple_days'] ) ) {
			$this->multiple_days = max( absint( $event['multiple_days'] ), 2 );
		}

		// Event recurrence.
		if ( isset( $event['recurrence'] ) ) {
			$this->recurrence = is_array( $event['recurrence'] ) ? array_map( 'esc_attr', $event['recurrence'] ) : false;
		}

		/* ========== *
		 * Event Meta *
		 * ========== */

		// Event has venue(s).
		if ( $this->start_location['venue'] || $this->end_location['venue'] ) {
			$this->venue = true;
		}

		// Event meta.
		if ( ! empty( $event['meta'] ) ) {
			$this->meta = is_array( $event['meta'] ) ? $event['meta'] : array();
		}

		// Event template.
		$this->template  = isset( $event['template'] ) ? wp_kses_post( $event['template'] ) : '';
	}

	/**
	 * Check for midnight event boundaries.
	 *
	 * Adjust potential inconsistencies when time is exactly at 00:00:00 or 24:00.
	 *
	 * @param  int $ts Timestamp
	 * @param  string|\DateTimezone $tz
	 * @param  string $bound
	 *
	 * @return int Timestamp
	 */
	private function esc_midnight( $ts, $tz, $bound ) {

		$time = Carbon::createFromTimestamp( $ts, $tz );

		if ( 'start' == $bound ) {
			return 0 === intval( $time->format( 'GHi' ) ) ? $ts + 1 : $ts;
		} elseif ( 'end' == $bound ) {
			return 240000 === intval( $time->format( 'GHi' ) ) ? $ts - 1 : $ts;
		} else {
			return $ts;
		}
	}

	/**
	 * Escape location.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  string|array $var
	 *
	 * @return array
	 */
	private function esc_location( $var = '' ) {

		$location = array();

		if ( is_string( $var ) ) {
			$var = array(
				'name'    => $var,
				'address' => $var,
			);
		} elseif ( is_bool( $var ) || is_null( $var ) ) {
			$var = array();
		} else {
			$var = (array) $var;
		}

		$location['name']    = isset( $var['name'] )    ? esc_attr( strip_tags( $var['name'] ) ) : '';
		$location['address'] = isset( $var['address'] ) ? esc_attr( strip_tags( $var['address'] ) ) : '';
		$location['lat']     = isset( $var['lat'] )     ? $this->esc_coordinate( $var['lat'] ) : 0;
		$location['lng']     = isset( $var['lng'] )     ? $this->esc_coordinate( $var['lng'] ) : 0;

		if ( ! empty( $location['name'] ) || ! empty( $location['address'] ) ) {
			$location['venue'] = true;
		} else {
			$location['venue'] = false;
		}

		return $location;
	}

	/**
	 * Escape coordinate.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  int|float $latlng
	 *
	 * @return int|float
	 */
	private function esc_coordinate( $latlng = 0 ) {
		return is_numeric( $latlng ) ? floatval( $latlng ) : 0;
	}

	/**
	 * Set timezone.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  string $tz Timezone.
	 *
	 * @return bool
	 */
	public function set_timezone( $tz ) {
		if ( in_array( $tz, timezone_identifiers_list() ) ) {
			$this->timezone = $tz;
			return true;
		}
		return false;
	}

	/**
	 * Starts or ends today.
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function is_today() {
		return $this->starts_today() || $this->ends_today();
	}

	/**
	 * Starts today.
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function starts_today() {
		return $this->start_dt->setTimezone( $this->timezone )->isToday();
	}

	/**
	 * Ends today.
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function ends_today() {
		return ! is_null( $this->end_dt ) ? $this->end_dt->setTimezone( $this->timezone )->isToday() : true;
	}

	/**
	 * Starts tomorrow
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function starts_tomorrow() {
		return $this->start_dt->setTimezone( $this->timezone )->isTomorrow();
	}

	/**
	 * Ends tomorrow.
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function ends_tomorrow() {
		return ! is_null( $this->end_dt ) ? $this->end_dt->setTimezone( $this->timezone )->isTomorrow() : false;
	}

	/**
	 * Started yesterday.
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function started_yesterday() {
		return $this->start_dt->setTimezone( $this->timezone )->isYesterday();
	}

	/**
	 * Ended yesterday.
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function ended_yesterday() {
		return ! is_null( $this->end_dt ) ? $this->end_dt->setTimezone( $this->timezone )->isYesterday() : false;
	}

	/**
	 * Starts in the future.
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function starts_future() {
		return $this->start_dt->setTimezone( $this->timezone )->isFuture();
	}

	/**
	 * Ends in the future.
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function ends_future() {
		return ! is_null( $this->end_dt ) ? $this->end_dt->setTimezone( $this->timezone )->isFuture() : false;
	}

	/**
	 * Started in the past.
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function started_past() {
		return $this->start_dt->setTimezone( $this->timezone )->isPast();
	}

	/**
	 * Ended in the past.
	 *
	 * @since  3.0.0
	 *
	 * @return bool
	 */
	public function ended_past() {
		return ! is_null( $this->end_dt ) ? $this->end_dt->setTimezone( $this->timezone )->isPast() : false;
	}

}
