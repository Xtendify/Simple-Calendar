<?php
namespace SimpleCalendar\Feeds;
use SimpleCalendar\Admin\Oauth_Ajax;
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit();
}

/**
 * Class Auth_Service_Helpers
 *
 * This class contains repetitive functions that
 * are used globally within the plugin.
 *
 * @package		AUTHSERVICE
 * @subpackage	Classes/Auth_Service_Helpers
 * @author		Simple Calendar
 * @since		1.0.0
 */
class Auth_Service_Helpers
{
	public function __construct()
	{
		add_filter('simple_calendar_oauth_list_events', [$this, 'oauth_helper_event_display'], 10, 3);

		add_filter('simple_calendar_oauth_get_calendars', [$this, 'oauth_helper_get_calendar'], 10, 3);

		add_filter('simple_calendar_oauth_schedule_events', [$this, 'oauth_helper_schedule_events'], 10, 2);

		add_filter(
			'simple_calendar_oauth_get_events_cover_base64image',
			[$this, 'auth_get_events_cover_base64image'],
			10,
			2,
		);
	}

	/**
	 * Display event on front end.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 */
	public function oauth_helper_event_display($default_valuem, $id, $args)
	{
		$Oauth_Ajax = new Oauth_Ajax();
		$response = $Oauth_Ajax->auth_get_calendarsevents($id, $args);
		return $response;
	}
	/**
	 * Get calendar.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 */
	public function oauth_helper_get_calendar()
	{
		$Oauth_Ajax = new Oauth_Ajax();
		$get_calendars = $Oauth_Ajax->auth_get_calendarlist();
		return $get_calendars;
	}

	/**
	 * Schedule event.
	 *
	 * @since 3.4.1
	 *
	 * @param int $post_id
	 */
	public function oauth_helper_schedule_events($calendarId, $event_data)
	{
		$Oauth_Ajax = new Oauth_Ajax();
		$get_calendars = $Oauth_Ajax->oauth_helper_schedule_event_action($calendarId, $event_data);
		return $get_calendars;
	}
	/**
	 * Get events cover base64 image.
	 *
	 * @since 1.0.0
	 *
	 * @param string $fileid
	 * @param array $args
	 * @return array
	 */
	public function auth_get_events_cover_base64image($fileid, $args)
	{
		$Oauth_Ajax = new Oauth_Ajax();
		$get_events_cover_image = $Oauth_Ajax->auth_get_events_cover_base64image($fileid, $args);
		return $get_events_cover_image;
	}
}

new Auth_Service_Helpers();
