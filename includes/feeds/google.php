<?php
/**
 * Google Calendar Feed
 *
 * @package SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Feeds;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Feed;
use SimpleCalendar\Feeds\Admin\Google_Admin as Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Calendar feed.
 *
 * A feed using a simple Google API key to pull events from public calendars.
 *
 * @since 3.0.0
 */
class Google extends Feed {


	/**
	 * Google API Client.
	 *
	 * @access private
	 * @var \Google_Client
	 */
	protected $google_client = null;

	/**
	 * Client scopes.
	 *
	 * @access private
	 * @var array
	 */
	protected $google_client_scopes = array();

	/**
	 * Google Calendar API key.
	 *
	 * @access protected
	 * @var string
	 */
	protected $google_api_key = '';

	/**
	 * Google Calendar ID.
	 *
	 * @access protected
	 * @var string
	 */
	protected $google_calendar_id = '';

	/**
	 * Google recurring events query setting.
	 *
	 * @access protected
	 * @var string
	 */
	protected $google_events_recurring = '';

	/**
	 * Google search query setting.
	 *
	 * @access protected
	 * @var string
	 */
	protected $google_search_query = '';

	/**
	 * Google max results query setting.
	 *
	 * @access protected
	 * @var int
	 */
	protected $google_max_results = 2500;

	/**
	 * Set properties.
	 *
	 * @since 3.0.0
	 *
	 * @param string|Calendar $calendar
	 * @param bool $load_admin
	 */
	public function __construct( $calendar = '', $load_admin = true ) {

		parent::__construct( $calendar );

		$this->type = 'google';
		$this->name = __( 'Google Calendar', 'google-calendar-events' );

		// Google client config.
		$settings = get_option( 'simple-calendar_settings_feeds' );
		$this->google_api_key = isset( $settings['google']['api_key'] ) ? esc_attr( $settings['google']['api_key'] ) : '';
		$this->google_client_scopes = array( \Google_Service_Calendar::CALENDAR_READONLY );
		$this->google_client = $this->get_client();

		if ( $this->post_id > 0 ) {

			// Google query args.
			$this->google_calendar_id       = $this->esc_google_calendar_id( get_post_meta( $this->post_id, '_google_calendar_id', true ) );
			$this->google_events_recurring  = esc_attr( get_post_meta( $this->post_id, '_google_events_recurring', true ) );
            // note that google_search_query is used in a URL param and not as HTML output, so don't use esc_attr() on it
			$this->google_search_query      = get_post_meta( $this->post_id, '_google_events_search_query', true );
			$this->google_max_results       = max( absint( get_post_meta( $this->post_id, '_google_events_max_results', true ) ), 1 );

			if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
				$this->events = ! empty( $this->google_api_key ) ? $this->get_events() : array();
			}
		}

		if ( is_admin() && $load_admin ) {
			$admin = new Admin( $this, $this->google_api_key, $this->google_calendar_id );
			$this->settings = $admin->settings_fields();
		}
	}

	/**
	 * Decode a calendar id.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $id Base64 encoded id.
	 *
	 * @return string
	 */
	public function esc_google_calendar_id( $id ) {
		return base64_decode( $id );
	}

	/**
	 * Get events feed.
	 *
	 * Normalizes Google data into a standard array object to list events.
	 *
	 * @since  3.0.0
	 *
	 * @return string|array
	 */
	public function get_events() {

		$calendar = get_transient( '_simple-calendar_feed_id_' . strval( $this->post_id ) . '_' . $this->type );

		if ( empty( $calendar ) && ! empty( $this->google_calendar_id ) ) {

			$error = '';

			try {
				$response = $this->make_request( $this->google_calendar_id );
			} catch ( \Exception $e ) {
				$error .= $e->getMessage();
			}

			if ( empty( $error ) && isset( $response['events'] ) && isset( $response['timezone'] ) ) {

				$calendar = array_merge( $response, array( 'events' => array() ) );

				// If no timezone has been set, use calendar feed.
				if ( 'use_calendar' == $this->timezone_setting ) {
					$this->timezone = $calendar['timezone'];
				}

				$source = isset( $response['title'] ) ? sanitize_text_field( $response['title'] ) : '';

				if ( ! empty( $response['events'] ) && is_array( $response['events'] ) ) {
					foreach ( $response['events'] as $event ) {
						if ( $event instanceof \Google_Service_Calendar_Event ) {

							// Visibility and status.
							// Public calendars may have private events which can't be properly accessed by simple api key method.
							// Also want to skip cancelled events (single occurences deleted from repeating events)
							$visibility = $event->getVisibility();
							$status = $event->getStatus();
							if ( $this->type == 'google' && ( $visibility == 'private' || $visibility == 'confidential' || $status == 'cancelled' ) ) {
								continue;
							}

							// Event title & description.
							$title = strip_tags( $event->getSummary() );
							$title = sanitize_text_field( iconv( mb_detect_encoding( $title, mb_detect_order(), true ), 'UTF-8', $title ) );
							$description = wp_kses_post( iconv( mb_detect_encoding( $event->getDescription(), mb_detect_order(), true ), 'UTF-8', $event->getDescription() ) );

							$whole_day = false;

							// Event start properties.
							if( 'use_calendar' == $this->timezone_setting ) {
								$start_timezone = ! $event->getStart()->timeZone ? $calendar['timezone'] : $event->getStart()->timeZone;
							} else {
								$start_timezone = $this->timezone;
							}

							if ( is_null( $event->getStart()->dateTime ) ) {
								// Whole day event.
								$date = Carbon::parse( $event->getStart()->date );
								$google_start = Carbon::createFromDate( $date->year, $date->month, $date->day, $start_timezone )->startOfDay()->addSeconds( 59 );
								$google_start_utc = Carbon::createFromDate( $date->year, $date->month, $date->day, 'UTC' )->startOfDay()->addSeconds( 59 );
								$whole_day = true;
							} else {
								$date = Carbon::parse( $event->getStart()->dateTime );

								// Check if there is an event level timezone
								if( $event->getStart()->timeZone && 'use_calendar' == $this->timezone_setting ) {

									// Get the two different times with the separate timezones so we can check the offsets next
									$google_start1 = Carbon::create( $date->year, $date->month, $date->day, $date->hour, $date->minute, $date->second, $date->timezone );
									$google_start2 = Carbon::create( $date->year, $date->month, $date->day, $date->hour, $date->minute, $date->second, $event->getStart()->timeZone );

									// Get the offset in hours
									$offset1 = $google_start1->offsetHours;
									$offset2 = $google_start2->offsetHours;

									// Get the difference between the two timezones
									$total_offset = ( $offset2 - $offset1 );

									// Add the hours offset to the date hour
									$date->hour += $total_offset;
								}

								$google_start = Carbon::create( $date->year, $date->month, $date->day, $date->hour, $date->minute, $date->second, $start_timezone );
								$google_start_utc = Carbon::create( $date->year, $date->month, $date->day, $date->hour, $date->minute, $date->second, 'UTC' );

								$this->timezone = $start_timezone;
							}
							// Start.
							$start = $google_start->getTimestamp();
							// Start UTC.
							$start_utc = $google_start_utc->getTimestamp();

							$end = $end_utc = $end_timezone = '';
							$span = 0;
							if ( false == $event->getEndTimeUnspecified() ) {

								// Event end properties.
								if( 'use_calendar' == $this->timezone_setting ) {
									$end_timezone = ! $event->getEnd()->timeZone ? $calendar['timezone'] : $event->getEnd()->timeZone;
								} else {
									$end_timezone = $this->timezone;
								}

								if ( is_null( $event->getEnd()->dateTime ) ) {
									// Whole day event.
									$date           = Carbon::parse( $event->getEnd()->date );
									$google_end     = Carbon::createFromDate( $date->year, $date->month, $date->day, $end_timezone )->startOfDay()->subSeconds( 59 );
									$google_end_utc = Carbon::createFromDate( $date->year, $date->month, $date->day, 'UTC' )->startOfDay()->subSeconds( 59 );
								} else {
									$date = Carbon::parse( $event->getEnd()->dateTime );

									// Check if there is an event level timezone
									if( $event->getEnd()->timeZone && 'use_calendar' == $this->timezone_setting ) {

										// Get the two different times with the separate timezones so we can check the offsets next
										$google_start1 = Carbon::create( $date->year, $date->month, $date->day, $date->hour, $date->minute, $date->second, $date->timezone );
										$google_start2 = Carbon::create( $date->year, $date->month, $date->day, $date->hour, $date->minute, $date->second, $event->getEnd()->timeZone );

										// Get the offset in hours
										$offset1 = $google_start1->offsetHours;
										$offset2 = $google_start2->offsetHours;

										// Get the difference between the two timezones
										$total_offset = ( $offset2 - $offset1 );

										// Add the hours offset to the date hour
										$date->hour += $total_offset;
									}

									$google_end     = Carbon::create( $date->year, $date->month, $date->day, $date->hour, $date->minute, $date->second, $end_timezone );
									$google_end_utc = Carbon::create( $date->year, $date->month, $date->day, $date->hour, $date->minute, $date->second, 'UTC' );
								}
								// End.
								$end = $google_end->getTimestamp();
								// End UTC.
								$end_utc = $google_end_utc->getTimestamp();

								// Count multiple days.
								$span = $google_start->setTimezone( $calendar['timezone'] )->diffInDays( $google_end->setTimezone( $calendar['timezone'] ) );

								if ( $span == 0 ) {
									if ( $google_start->toDateString() !== $google_end->toDateString() ) {
										$span = 1;
									}
								}
							}

							// Multiple days.
							$multiple_days = $span > 0 ? $span : false;

							// Google cannot have two different locations for start and end time.
							$start_location = $end_location = $event->getLocation();

							// Recurring event.
							$recurrence = $event->getRecurrence();
							$recurring_id = $event->getRecurringEventId();
							if ( ! $recurrence && $recurring_id ) {
								$recurrence = true;
							}

							// Event link.
							if ( 'use_calendar' == $this->timezone_setting ) {
								$link = add_query_arg( array( 'ctz' => $this->timezone ), $event->getHtmlLink() );
							} else {
								$link = $event->getHtmlLink();
							}

							// Build the event.
							$calendar['events'][ intval( $start ) ][] = array(
								'type'           => 'google-calendar',
								'source'         => $source,
								'title'          => $title,
								'description'    => $description,
								'link'           => $link,
								'visibility'     => $visibility,
								'uid'            => $event->id,
								'ical_id'        => $event->getICalUID(),
								'calendar'       => $this->post_id,
								'timezone'       => $this->timezone,
								'start'          => $start,
								'start_utc'      => $start_utc,
								'start_timezone' => $start_timezone,
								'start_location' => $start_location,
								'end'            => $end,
								'end_utc'        => $end_utc,
								'end_timezone'   => $end_timezone,
								'end_location'   => $end_location,
								'whole_day'      => $whole_day,
								'multiple_days'  => $multiple_days,
								'recurrence'     => $recurrence,
								'template'       => $this->events_template,
							);

						}
					}

					if ( ! empty( $calendar['events'] ) ) {

						ksort( $calendar['events'], SORT_NUMERIC );

						set_transient(
							'_simple-calendar_feed_id_' . strval( $this->post_id ) . '_' . $this->type,
							$calendar,
							max( absint( $this->cache ), 1 ) // Since a value of 0 means forever we set the minimum here to 1 if the user has set it to be 0
						);
					}
				}

			} else {

				$message  = __( 'While trying to retrieve events, Google returned an error:', 'google-calendar-events' );
				$message .= '<br><br>' . $error . '<br><br>';
				$message .= __( 'Please ensure that both your Google Calendar ID and API Key are valid and that the Google Calendar you want to display is public.', 'google-calendar-events' ) . '<br><br>';
				$message .= __( 'Only you can see this notice.', 'google-calendar-events' );

				return $message;
			}

		}

		// If no timezone has been set, use calendar feed.
		if ( 'use_calendar' == $this->timezone_setting && isset( $calendar['timezone'] ) ) {
			$this->timezone = $calendar['timezone'];
		}

		return isset( $calendar['events'] ) ? $calendar['events'] : array();
	}

	/**
	 * Query Google Calendar.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $id        A valid Google Calendar ID.
	 * @param  int    $time_min  Lower bound timestamp.
	 * @param  int    $time_max  Upper bound timestamp.
	 *
	 * @return array
	 *
	 * @throws \Exception On request failure will throw an exception from Google.
	 */
	public function make_request( $id = '', $time_min = 0, $time_max = 0 ) {

		$calendar = array();
		$google = $this->get_service();

		if ( ! is_null( $google ) && ! empty( $id ) ) {

			// Build the request args.
			$args = array();

			// Expand recurring events.
			if ( $this->google_events_recurring == 'show' ) {
				$args['singleEvents'] = true;
			}

			// Query events using search terms.
			if ( ! empty( $this->google_search_query ) ) {
				$args['q'] = rawurlencode( $this->google_search_query );
			}

			// Max results to query.
			$args['maxResults'] = strval( min( absint( $this->google_max_results ), 2500 ) );

			// Specify a timezone.
			$timezone = '';
			if ( 'use_calendar' != get_post_meta( $this->post_id, '_feed_timezone_setting', true ) ) {
				$args['timeZone'] = $timezone = $this->timezone;
			}

			// Lower bound (inclusive) for an event's end time to filter by.
			$earliest_event = intval( $this->time_min );
			if ( $earliest_event > 0 ) {
				$timeMin = Carbon::now();
				if ( ! empty( $timezone ) ) {
					$timeMin->setTimezone( $timezone );
				}
				$timeMin->setTimestamp( $earliest_event );
				$args['timeMin'] = $timeMin->toRfc3339String();
			}

			// Upper bound (exclusive) for an event's start time to filter by.
			$latest_event = intval( $this->time_max );
			if ( $latest_event > 0 ) {
				$timeMax = Carbon::now();
				if ( ! empty( $timezone ) ) {
					$timeMax->setTimezone( $timezone );
				}
				$timeMax->setTimestamp( $latest_event );
				$args['timeMax'] = $timeMax->toRfc3339String();
			}

			// Query events in calendar.
			$response = $google->events->listEvents( $id, $args );

			if ( $response instanceof \Google_Service_Calendar_Events ) {
				$calendar = array(
					'id'            => $id,
					'title'         => $response->getSummary(),
					'description'   => $response->getDescription(),
					'timezone'      => $response->getTimeZone(),
					'url'           => esc_url( '//www.google.com/calendar/embed?src=' . $id ),
					'events'        => $response->getItems(),
				);
			}
		}

		return $calendar;
	}

	/**
	 * Google API Client.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @return \Google_Client
	 */
	private function get_client() {

		$client = new \Google_Client();
		$client->setApplicationName( 'Simple Calendar' );
		$client->setScopes( $this->google_client_scopes );
		$client->setDeveloperKey( $this->google_api_key );
		$client->setAccessType( 'online' );

		return $client;
	}

	/**
	 * Google Calendar Service.
	 *
	 * @since  3.0.0
	 * @access protected
	 *
	 * @return null|\Google_Service_Calendar
	 */
	protected function get_service() {
		return $this->google_client instanceof \Google_Client ? new \Google_Service_Calendar( $this->google_client ) : null;
	}

}
