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
	public $start_timezone = '';

	/**
	 * Event location at event start.
	 *
	 * @access public
	 * @var false|string|array
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
	 * @var Carbon
	 */
	public $end_dt = null;

	/**
	 * Event end time timezone.
	 *
	 * @access public
	 * @var string
	 */
	public $end_timezone = '';

	/**
	 * Event location at event end.
	 *
	 * @access public
	 * @var false|string|array
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
	 * Event constructor.
	 *
	 * @param array $event
	 */
	public function __construct( array $event ) {

		// Event identifiers.
		$this->uid              = isset( $event['uid'] )            ? trim( strval( $event['uid'] ) ) : '';
		$this->calendar         = isset( $event['calendar'] )       ? absint( $event['calendar'] ) : 0;
		$this->timezone         = isset( $event['timezone'] )       ? esc_attr( $event['timezone'] ) : 'UTC';

		// Event content.
		$title                  = isset( $event['title'] )          ? strip_tags( $event['title'] ) : '';
		$this->title            = esc_attr( iconv( mb_detect_encoding( $title, mb_detect_order(), true ), 'UTF-8', $title ) );

		$description            = isset( $event['description'] )    ? $event['description'] : '';
		$this->description      = esc_attr( iconv( mb_detect_encoding( $description, mb_detect_order(), true ), 'UTF-8', $description ) );

		$this->link             = isset( $event['link'] )           ? esc_url_raw( $event['link'] ) : '';
		$this->visibility       = isset( $event['visibility'] )     ? esc_attr( $event['visibility'] ) : '';
		$this->public           = $this->visibility == 'public'     ? true : false;

		// Event start properties.
		$this->start            = isset( $event['start'] )          ? intval( $event['start'] ) : 0;
		$this->start_utc        = isset( $event['start_utc'] )      ? intval( $event['start_utc'] ) : 0;
		$this->start_timezone   = isset( $event['start_timezone'] ) ? esc_attr( $event['start_timezone'] )  : '';
		$this->start_dt         = Carbon::createFromTimestamp( $this->start, $this->start_timezone );
		$this->start_location   = isset( $event['start_location'] ) ? $this->esc_location( $event['end_location'] ) : $this->esc_location( '' );

		// Event end properties.
		$this->end              = isset( $event['end'] )            ? ( is_numeric( $event['end'] ) ? intval( $event['end'] ) : false ) : false;
		$this->end_utc          = isset( $event['end_utc'] )        ? ( is_numeric( $event['end_utc'] ) ? intval( $event['end_utc'] ) : false ) : false;
		$this->end_timezone     = isset( $event['end_timezone'] )   ? esc_attr( $event['end_timezone'] ) : '';
		$this->end_dt           = is_int( $this->end )              ? Carbon::createFromTimestamp( $this->end, $this->end_timezone ) : null;
		$this->end_location     = isset( $event['end_location'] )   ? $this->esc_location( $event['end_location'] ) : $this->esc_location( '' );

		// Event position properties.
		$this->whole_day        = isset( $event['whole_day'] )      ? ( $event['whole_day'] === true ? true : false ) : false;
		$this->multiple_days    = isset( $event['multiple_days'] )  ? ( $event['multiple_days'] === false ? false : max( absint( $event['multiple_days'] ), 2 ) ) : false;
		$this->recurrence       = isset( $event['recurrence'] )     ? ( is_array( $event['recurrence'] ) ? array_map( 'esc_attr', $event['recurrence'] ) : false ) : false;

		// Event meta.
		$this->venue            = $this->start_location['venue'] || $this->end_location['venue'] ? true : false;
		$this->meta             = isset( $event['meta'] )           ? ( is_array( $event['meta'] ) ? array_map( 'esc_attr', $event['meta'] ) : array() ) : array();

	}

	/**
	 * Escape location.
	 *
	 * @param  string|array $var
	 *
	 * @return array
	 */
	private function esc_location( $var ) {

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

		$location['name']    = isset( $var['name'] )    ? esc_attr( strip_tags( $var['name'] ) )    : '';
		$location['address'] = isset( $var['address'] ) ? esc_attr( strip_tags( $var['address'] ) ) : '';
		$location['lat']     = isset( $var['lat'] )     ? ( is_numeric( $var['lat'] ) ? floatval( $var['lat'] ) : 0 ) : 0;
		$location['lng']     = isset( $var['lng'] )     ? ( is_numeric( $var['lng'] ) ? floatval( $var['lng'] ) : 0 ) : 0;
		$location['venue']   = ! empty( $location['name'] ) || ! empty( $location['address'] ) ? true : false;

		return $location;
	}

	/**
	 * Set timezone.
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
	 * Starts today.
	 *
	 * @return bool
	 */
	public function starts_today() {
		return $this->start_dt->setTimezone( $this->timezone )->isToday();
	}

	/**
	 * Ends today.
	 *
	 * @return bool
	 */
	public function ends_today() {
		return ! is_null( $this->end_dt ) ? $this->end_dt->setTimezone( $this->timezone )->isToday() : false;
	}

	/**
	 * Starts tomorrow
	 *
	 * @return bool
	 */
	public function starts_tomorrow() {
		return $this->start_dt->setTimezone( $this->timezone )->isTomorrow();
	}

	/**
	 * Ends tomorrow.
	 *
	 * @return bool
	 */
	public function ends_tomorrow() {
		return ! is_null( $this->end_dt ) ? $this->end_dt->setTimezone( $this->timezone )->isTomorrow() : false;
	}

	/**
	 * Started yesterday.
	 *
	 * @return bool
	 */
	public function started_yesterday() {
		return $this->start_dt->setTimezone( $this->timezone )->isYesterday();
	}

	/**
	 * Ended yesterday.
	 *
	 * @return bool
	 */
	public function ended_yesterday() {
		return ! is_null( $this->end_dt ) ? $this->end_dt->setTimezone( $this->timezone )->isYesterday() : false;
	}

	/**
	 * Starts in the future.
	 *
	 * @return bool
	 */
	public function starts_future() {
		return $this->start_dt->setTimezone( $this->timezone )->isFuture();
	}

	/**
	 * Ends in the future.
	 *
	 * @return bool
	 */
	public function ends_future() {
		return ! is_null( $this->end_dt ) ? $this->end_dt->setTimezone( $this->timezone )->isFuture() : false;
	}

	/**
	 * Started in the past.
	 *
	 * @return bool
	 */
	public function started_past() {
		return $this->start_dt->setTimezone( $this->timezone )->isPast();
	}

	/**
	 * Ended in the past.
	 *
	 * @return bool
	 */
	public function ended_past() {
		return ! is_null( $this->end_dt ) ? $this->end_dt->setTimezone( $this->timezone )->isPast() : false;
	}

}
