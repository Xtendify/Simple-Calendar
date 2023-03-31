<?php
/**
 * Feed
 *
 * @package SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Abstracts;

use SimpleCalendar\plugin_deps\Carbon\Carbon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Feed.
 *
 * Source of events supplied to calendars.
 *
 * @since 3.0.0
 */
abstract class Feed {

	/**
	 * Feed type.
	 *
	 * @access public
	 * @var string
	 */
	public $type = '';

	/**
	 * Feed name.
	 *
	 * @access public
	 * @var string
	 */
	public $name = '';

	/**
	 * Calendar post id.
	 *
	 * @access public
	 * @var int
	 */
	public $post_id = 0;

	/**
	 * Calendar opening.
	 *
	 * @access protected
	 * @var int
	 */
	protected $calendar_start = 0;

	/**
	 * Start of week.
	 *
	 * @access protected
	 * @var int
	 */
	protected $week_starts = 0;

	/**
	 * Events.
	 *
	 * @access public
	 * @var array
	 */
	public $events = array();

	/**
	 * Events template.
	 *
	 * @access protected
	 * @var string
	 */
	protected $events_template = '';

	/**
	 * Timezone setting.
	 *
	 * @access protected
	 * @var string
	 */
	protected  $timezone_setting = '';

	/**
	 * Timezone.
	 *
	 * @access public
	 * @var string
	 */
	public $timezone = '';

	/**
	 * Earliest possible event.
	 *
	 * @access public
	 * @var int
	 */
	public $time_min = 0;

	/**
	 * Latest possible event.
	 *
	 * @access public
	 * @var int
	 */
	public $time_max = 0;

	/**
	 * Feed cache interval.
	 *
	 * @access protected
	 * @var int
	 */
	protected $cache = 7200;

	/**
	 * Feed settings.
	 *
	 * @access protected
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string|Calendar $calendar
	 */
	public function __construct( $calendar = '' ) {

		if ( $calendar instanceof Calendar ) {

			if ( isset( $calendar->id ) ) {
				$this->post_id = $calendar->id;
			}
			if ( isset( $calendar->start ) ) {
				$this->calendar_start = $calendar->start;
			}
			$this->week_starts      = isset( $calendar->week_starts ) ? $calendar->week_starts : get_option( 'start_of_week' );
			$this->events_template  = ! empty( $calendar->events_template ) ? $calendar->events_template : simcal_default_event_template();

			if ( $this->post_id > 0 ) {
				$this->set_cache();
				$this->timezone_setting = get_post_meta( $this->post_id, '_feed_timezone_setting', true );
				$this->timezone = $calendar->timezone;
				$this->set_earliest_event();
				$this->set_latest_event();
			}
		}
	}

	/**
	 * Input fields for settings page.
	 *
	 * @since  3.0.0
	 *
	 * @return false|array
	 */
	public function settings_fields() {
		return $this->settings;
	}

	/**
	 * Set earliest event.
	 *
	 * @since 3.0.0
	 *
	 * @param int $timestamp
	 */
	public function set_earliest_event( $timestamp = 0 ) {

		$earliest = intval( $timestamp );

		if ( $earliest === 0 ) {

			$start = Carbon::createFromTimestamp( $this->calendar_start, $this->timezone );

			$earliest_date  = esc_attr( get_post_meta( $this->post_id, '_feed_earliest_event_date', true ) );
			$earliest_range = max( absint( get_post_meta( $this->post_id, '_feed_earliest_event_date_range', true ) ), 1 );

			if ( 'days_before' == $earliest_date ) {
				$earliest = $start->subDays( $earliest_range )->getTimestamp();
			} elseif ( 'weeks_before' == $earliest_date ) {
				$earliest = $start->subWeeks( $earliest_range )->addDay()->getTimestamp();
			} elseif ( 'months_before' == $earliest_date ) {
				$earliest = $start->subMonths( $earliest_range )->addDay()->getTimestamp();
			} elseif ( 'years_before' == $earliest_date ) {
				$earliest = $start->subYears( $earliest_range )->addDay()->getTimestamp();
			} else {
				$earliest = $start->getTimestamp();
			}
		}

		$this->time_min = $earliest;
	}

	/**
	 * Set latest event.
	 *
	 * @since 3.0.0
	 *
	 * @param int $timestamp
	 */
	public function set_latest_event( $timestamp = 0 ) {

		$latest = intval( $timestamp );

		if ( $latest === 0 ) {

			$start = Carbon::createFromTimestamp( $this->calendar_start, $this->timezone )->endOfDay();

			$latest_date  = esc_attr( get_post_meta( $this->post_id, '_feed_latest_event_date', true ) );
			$latest_range = max( absint( get_post_meta( $this->post_id, '_feed_latest_event_date_range', true ) ), 1 );

			if ( 'days_after' == $latest_date ) {
				$latest = $start->addDays( $latest_range )->getTimestamp();
			} elseif ( 'weeks_after' == $latest_date ) {
				$latest = $start->addWeeks( $latest_range )->subDay()->getTimestamp();
			} elseif ( 'months_after' == $latest_date ) {
				$latest = $start->addMonths( $latest_range )->subDay()->getTimestamp();
			} elseif ( 'years_after' == $latest_date ) {
				$latest = $start->addYears( $latest_range )->subDay()->getTimestamp();
			} else {
				$latest = $start->getTimestamp();
			}

		}

		$this->time_max = $latest;
	}

	/**
	 * Set cache.
	 *
	 * @since 3.0.0
	 *
	 * @param int $time
	 */
	public function set_cache( $time = 0 ) {
		if ( $time === 0 || ! is_numeric( $time ) ) {
			$cache = get_post_meta( $this->post_id, '_feed_cache', true );
			$time  = is_numeric( $cache ) && $cache >= 0 ? absint( $cache ) : $this->cache;
		}
		$this->cache = absint( $time );
	}

	/**
	 * Get events feed.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	abstract public function get_events();

}
