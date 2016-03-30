<?php
/**
 * Grouped Calendars Feed
 *
 * @package SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Feeds;

use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Feed;
use SimpleCalendar\Feeds\Admin\Grouped_Calendars_Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grouped calendars feed.
 *
 * Feed made of multiple calendar feeds combined together.
 *
 * @since  3.0.0
 */
class Grouped_Calendars extends Feed {

	/**
	 * Feed ids to get events from.
	 *
	 * @access public
	 * @var array
	 */
	public $calendars_ids = array();

	/**
	 * Set properties.
	 *
	 * @since 3.0.0
	 *
	 * @param string|Calendar $calendar
	 */
	public function __construct( $calendar = '' ) {

		parent::__construct( $calendar );

		$this->type = 'grouped-calendars';
		$this->name = __( 'Grouped Calendar', 'google-calendar-events' );

		if ( $this->post_id > 0 ) {
			$this->set_source();
			if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
				$this->events = $this->get_events();
			}
		}

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			new Grouped_Calendars_Admin( $this );
		}
	}

	/**
	 * Set source.
	 *
	 * @since 3.0.0
	 *
	 * @param array $ids Array of calendar ids.
	 */
	public function set_source( $ids = array() ) {

		$source = get_post_meta( $this->post_id, '_grouped_calendars_source', true );

		if ( 'ids' == $source ) {

			if ( empty( $ids ) ) {
				$ids = get_post_meta( $this->post_id, '_grouped_calendars_ids', true );
			}

			$this->calendars_ids = ! empty( $ids ) && is_array( $ids ) ? array_map( 'absint', $ids ) : array();

		} elseif ( 'category' == $source ) {

			$categories = get_post_meta( $this->post_id, '_grouped_calendars_category', true );

			if ( $categories && is_array( $categories ) ) {

				$tax_query = array(
					'taxonomy' => 'calendar_category',
					'field'    => 'term_id',
					'terms'    => array_map( 'absint', $categories ),
				);

				$calendars = get_posts( array(
					'post_type' => 'calendar',
					'tax_query' => array( $tax_query ),
					'nopaging'  => true,
					'fields'    => 'ids',
				) );

				$this->calendars_ids = ! empty( $calendars ) && is_array( $calendars ) ? $calendars : array();
			}

		}
	}

	/**
	 * Get events from multiple calendars.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function get_events() {

		$ids    = $this->calendars_ids;
		$events = get_transient( '_simple-calendar_feed_id_' . strval( $this->post_id ) . '_' . $this->type );

		if ( empty( $events ) && ! empty( $ids ) && is_array( $ids ) ) {

			$events = array();

			foreach ( $ids as $cal_id ) {

				$calendar = simcal_get_calendar( intval( $cal_id ) );

				simcal_delete_feed_transients( $cal_id );

				if ( $calendar instanceof Calendar ) {

					// Sometimes the calendars might have events at the same time from different calendars
					// When merging the arrays together some of the events will be lost because the keys are the same and one will overwrite the other
					// This snippet checks if the key already exists in the master events array and if it does it subtracts 1 from it to make the key unique and then unsets the original key.
					foreach( $calendar->events as $k => $v ) {
						$calendar->events[ $this->update_array_timestamp( $events, $k ) ] = $v;
					}

					$events = is_array( $calendar->events ) ? $events + $calendar->events : $events;
				}

			}

			if ( ! empty( $events ) ) {

				// Trim events to set the earliest one as specified in feed settings.
				$earliest_event = intval( $this->time_min );
				if ( $earliest_event > 0 ) {
					$events = $this->array_filter_key( $events, array( $this, 'filter_events_before' ) );
				}

				// Trim events to set the latest one as specified in feed settings.
				$latest_event = intval( $this->time_max );
				if ( $latest_event > 0 ) {
					$events = $this->array_filter_key( $events, array( $this, 'filter_events_after' ) );
				}

				set_transient(
					'_simple-calendar_feed_id_' . strval( $this->post_id ) . '_' . $this->type,
					$events,
					absint( $this->cache )
				);
			}

		}
	
		// Sort events by start time before returning
		uasort( $events, array( $this, 'sort_by_start_time' ) );

		return $events;
	}

	/*
	 * Recursive function to adjust the timestamp array indices that are the same.
	 */
	public function update_array_timestamp( $arr, $i ) {

		if ( array_key_exists( $i, $arr ) ) {
			$i = $this->update_array_timestamp( $arr, $i - 1 );
		}

		return $i;
	}

	/**
	 * uasort helper to sort events by start time.
	 *
	 * @since  3.0.13
	 * @access private
	 */
	private function sort_by_start_time( $a, $b ) {
		if ( $a == $b ) {
			return 0;
		}

		return ( $a[0]->start < $b[0]->start ) ? -1 : 1;
	}



	/**
	 * Array filter key.
	 *
	 * `array_filter` does not allow to parse an associative array keys before PHP 5.6.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  array        $array
	 * @param  array|string $callback
	 *
	 * @return array
	 */
	private function array_filter_key( array $array, $callback ) {
		$matched_keys = array_filter( array_keys( $array ), $callback );
		return array_intersect_key( $array, array_flip( $matched_keys ) );
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
		if ( $this->time_min !== 0 ) {
			return intval( $event ) > intval( $this->time_min );
		}
		return true;
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
		if ( $this->time_max !== 0 ) {
			return intval( $event ) < intval( $this->time_max );
		}
		return true;
	}

}
