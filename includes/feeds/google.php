<?php
/**
 * Google Calendar Feed
 *
 * @package SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Feeds;

use Carbon\Carbon;
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
			$this->google_search_query      = esc_attr( get_post_meta( $this->post_id, '_google_events_search_query', true ) );
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

			if ( isset( $response['events'] ) && isset( $response['timezone'] ) ) {

				$calendar = array_merge( $response, array( 'events' => array() ) );

				// If no timezone has been set, use calendar feed.
				if ( 'use_calendar' == $this->timezone_setting ) {
					$this->timezone = $calendar['timezone'];
				}

				if ( ! empty( $response['events'] ) && is_array( $response['events'] ) ) {
					foreach ( $response['events'] as $event ) {
						if ( $event instanceof \Google_Service_Calendar_Event ) {

							// Visibility.
							$visibility = $event->getVisibility();
							// Public calendars may have private events which can't be properly accessed by simple api key method.
							if ( $this->type == 'google' && ( $visibility == 'private' || $visibility == 'confidential' ) ) {
								continue;
							}

							// Event title & description.
							$title = strip_tags( $event->getSummary() );
							$title = sanitize_text_field( iconv( mb_detect_encoding( $title, mb_detect_order(), true ), 'UTF-8', $title ) );
							$description = sanitize_text_field( iconv( mb_detect_encoding( $event->getDescription(), mb_detect_order(), true ), 'UTF-8', $event->getDescription() ) );

							$whole_day = false;

							// Event start properties.
							$start_timezone = ! $event->getStart()->timeZone ? $calendar['timezone'] : $event->getStart()->timeZone;
							if ( is_null( $event->getStart()->dateTime ) ) {
								// Whole day event.
								$google_start     = Carbon::parse( $event->getStart()->date )->startOfDay()->setTimezone( $start_timezone );
								$google_start_utc = Carbon::parse( $event->getStart()->date )->startOfDay()->setTimezone( 'UTC' );
								$whole_day = true;
							} else {
								$google_start     = Carbon::parse( $event->getStart()->dateTime )->setTimezone( $start_timezone );
								$google_start_utc = Carbon::parse( $event->getStart()->dateTime )->setTimezone( 'UTC' );
							}
							// Start.
							$start = $google_start->getTimestamp();
							// Start UTC.
							$start_utc = $google_start_utc->getTimestamp();

							// Event end properties.
							$end_timezone = ! $event->getEnd()->timeZone ? $calendar['timezone'] : $event->getEnd()->timeZone;
							if ( is_null( $event->getEnd()->dateTime ) ) {
								// Whole day event.
								$google_end = Carbon::parse( $event->getEnd()->date )->setTimezone( $end_timezone )->endOfDay();
								$google_end_utc = Carbon::parse( $event->getEnd()->date )->setTimezone( 'UTC' )->endOfDay();
							}  else {
								$google_end = Carbon::parse( $event->getEnd()->dateTime )->setTimezone( $end_timezone );
								$google_end_utc = Carbon::parse( $event->getEnd()->dateTime )->setTimezone( 'UTC' );
							}
							// End.
							$end = $google_end->getTimestamp();
							// End UTC.
							$end_utc = $google_end_utc->getTimestamp();

							// Count multiple days.
							$span = 0;
							if ( false == $event->getEndTimeUnspecified() ) {
								$a = intval( $google_start_utc->setTimezone( $calendar['timezone'] )->format( 'Ymd' ) );
								$b = intval( $google_end_utc->setTimezone( $calendar['timezone']  )->format( 'Ymd' ) );
								$span = max( ( $b - $a ), 0 );
							}
							$multiple_days = $span > 0 ? $span + 1 : false;

							// Google cannot have two different locations for start and end time.
							$start_location = $end_location = $event->getLocation();

							// Recurring event.
							$recurrence = $event->getRecurrence();

							// Build the event.
							$calendar['events'][ $start_utc ][] = array(
								'type'           => 'google-calendar',
								'title'          => $title,
								'description'    => $description,
								'link'           => $event->getHtmlLink(),
								'visibility'     => $visibility,
								'uid'            => $event->getICalUID(),
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
								'recurrence'     => $recurrence ? $recurrence : false,
								'template'       => $this->events_template,
							);

						}
					}

					if ( ! empty( $calendar['events'] ) ) {

						ksort( $calendar['events'], SORT_NUMERIC );

						set_transient(
							'_simple-calendar_feed_id_' . strval( $this->post_id ) . '_' . $this->type,
							$calendar,
							max( absint( $this->cache ), 60 )
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
	 * @throws \Exception On request failure will throw an exception from Google.
	 *
	 * @param  string $id        A valid Google Calendar ID.
	 * @param  int    $time_min  Lower bound timestamp.
	 * @param  int    $time_max  Upper bound timestamp.
	 *
	 * @return array
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
	 * @access protected
	 *
	 * @return null|\Google_Service_Calendar
	 */
	protected function get_service() {
		return $this->google_client instanceof \Google_Client ? new \Google_Service_Calendar( $this->google_client ) : null;
	}

}
