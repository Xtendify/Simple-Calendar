<?php
/**
 * Oauth Ajax
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Oauth Ajax.
 *
 * @since 3.0.0
 */
class Oauth_Ajax
{
	/*
	 *Auth site URL
	 */
	public static $url = SIMPLE_CALENDAR_OAUTH_HELPER_AUTH_DOMAIN . 'wp-json/api-oauth-helper/v1/';

	/**
	 * My site URL
	 */
	public static $my_site_url = '';

	/**
	 * Whether AJAX/admin hooks were registered.
	 *
	 * @var bool
	 */
	private static $booted = false;

	/**
	 * Set up ajax hooks (once).
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		self::$my_site_url = site_url();

		if (self::$booted) {
			return;
		}
		self::$booted = true;

		add_action('wp_ajax_oauth_deauthenticate_site', [$this, 'oauth_deauthenticate_site']);

		if ($this->is_calendar_admin_screen()) {
			add_action('admin_init', [$this, 'oauth_check_iftoken_expired']);
		}
	}

	/**
	 * Whether the current admin request is a calendar screen.
	 *
	 * @return bool
	 */
	private function is_calendar_admin_screen()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_GET['post_type']) && 'calendar' === $_GET['post_type']) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (!empty($_GET['post'])) {
			$post_id = absint($_GET['post']);
			return $post_id > 0 && 'calendar' === get_post_type($post_id);
		}

		return false;
	}

	/**
	 * Default auth payload for remote requests.
	 *
	 * @param array $extra Extra body fields.
	 * @return array
	 */
	private function auth_payload(array $extra = [])
	{
		return array_merge(
			[
				'site_url' => self::$my_site_url,
				'auth_token' => get_option('simple_calendar_auth_site_token'),
			],
			$extra,
		);
	}

	/**
	 * POST to the OAuth helper API.
	 *
	 * @param string $endpoint API route (without base URL).
	 * @param array  $body     Request body.
	 * @param int    $timeout  Request timeout in seconds.
	 * @return array|\WP_Error
	 */
	private function post($endpoint, array $body, $timeout = 30)
	{
		return wp_remote_post(self::$url . $endpoint, [
			'method' => 'POST',
			'body' => $body,
			'timeout' => $timeout,
			'cookies' => [],
		]);
	}

	/**
	 * Decode a JSON API response body.
	 *
	 * @param string $body Raw response body.
	 * @return array|null
	 */
	private function decode_response($body)
	{
		$data = json_decode($body, true);
		return is_array($data) ? $data : null;
	}

	/**
	 * Deauthenticate this site from OAuth via Simple Calendar.
	 */
	public function oauth_deauthenticate_site()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (!wp_verify_nonce($nonce, 'oauth_action_deauthentication') || !current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permission denied.', 'google-calendar-events')], 403);
		}

		$request = $this->post('de_authenticate_site', $this->auth_payload());

		if (is_wp_error($request) || 200 !== (int) wp_remote_retrieve_response_code($request)) {
			if (is_wp_error($request)) {
				error_log($request->get_error_message());
			}
			wp_send_json_error(['message' => __('Deauthentication failed.', 'google-calendar-events')]);
		}

		$response_arr = $this->decode_response(wp_remote_retrieve_body($request));
		delete_option('simple_calendar_auth_site_token');

		if (!empty($response_arr['response'])) {
			delete_option('simple_calendar_connect_pro_connection_type');
			delete_option('simple_calendar_connect_pro_oauth_health_ok');
			delete_option('simple_calendar_connect_pro_own_oauth_health_ok');
			delete_option('simple-calendar_connect_pro_setup_completed_at');

			wp_send_json_success([
				'message' => __('DeAuthenticate Successfully.', 'google-calendar-events'),
			]);
		}

		$message = !empty($response_arr['message'])
			? (string) $response_arr['message']
			: __('Deauthentication Failed.', 'google-calendar-events');

		wp_send_json_error(['message' => $message]);
	}

	/**
	 * On calendar admin screens, clear expired auth tokens.
	 *
	 * Only delete the token when the auth service explicitly reports it invalid.
	 * Do not delete on network errors or missing responses (matches pre-cleanup behavior).
	 */
	public function oauth_check_iftoken_expired()
	{
		$request = $this->post('check_iftoken_expired', $this->auth_payload());
		if (is_wp_error($request)) {
			return;
		}

		$response_arr = $this->decode_response(wp_remote_retrieve_body($request));

		if (isset($response_arr['response']) && !empty($response_arr['response'])) {
			if ($response_arr['response']) {
				return;
			}

			delete_option('simple_calendar_auth_site_token');
		}
	}

	/**
	 * Get calendar list from the auth service.
	 *
	 * @return array
	 */
	public function auth_get_calendarlist()
	{
		$request = $this->post('auth_get_calendarlist', $this->auth_payload());
		if (is_wp_error($request)) {
			return ['Error' => __('Network issue.', 'google-calendar-events')];
		}

		$response_arr = $this->decode_response(wp_remote_retrieve_body($request));
		if (!empty($response_arr['response'])) {
			return isset($response_arr['data']) ? $response_arr['data'] : [];
		}

		return [
			'Error' => !empty($response_arr['message'])
				? (string) $response_arr['message']
				: __('There is something wrong. please re-try.', 'google-calendar-events'),
		];
	}

	/**
	 * Get calendar events from the auth service.
	 *
	 * @param string $id   Calendar ID.
	 * @param array  $args Query arguments.
	 * @return array
	 */
	public function auth_get_calendarsevents($id, $args)
	{
		$request = $this->post(
			'get_calendar_events',
			$this->auth_payload([
				'id' => $id,
				'arguments' => $args,
			]),
		);

		if (is_wp_error($request)) {
			return ['Error' => __('Network issue.', 'google-calendar-events')];
		}

		$response_arr = $this->decode_response(wp_remote_retrieve_body($request));
		if (!empty($response_arr['response'])) {
			return $response_arr;
		}

		if (!empty($response_arr['message'])) {
			return ['Error' => (string) $response_arr['message']];
		}

		return ['Error' => __('Network issue.', 'google-calendar-events')];
	}

	/**
	 * Schedule an appointment event via the auth service.
	 *
	 * @param string $calendar_id Calendar ID.
	 * @param array  $event_data  Event payload.
	 * @return array|mixed
	 */
	public function oauth_helper_schedule_event_action($calendar_id, $event_data)
	{
		$request = $this->post(
			'appointment_schedule_event',
			$this->auth_payload([
				'calendarid' => $calendar_id,
				'event_data' => $event_data,
			]),
		);

		if (is_wp_error($request)) {
			return ['Error' => __('Network issue.', 'google-calendar-events')];
		}

		$response_arr = $this->decode_response(wp_remote_retrieve_body($request));
		if (isset($response_arr['response']) && !empty($response_arr['response']) && $response_arr['response']) {
			$response_message = isset($response_arr['message']) ? $response_arr['message'] : '';
			if (!empty($response_message) && isset($response_arr['data'])) {
				return unserialize($response_arr['data']);
			}

			if (!empty($response_arr['message'])) {
				return [
					'Error' => $response_arr['message'],
				];
			}
		} elseif (is_array($response_arr) && !empty($response_arr['message'])) {
			return [
				'Error' => $response_arr['message'],
			];
		}

		return [
			'Error' => !empty($response_arr['message'])
				? (string) $response_arr['message']
				: __('There is something wrong. please re-try.', 'google-calendar-events'),
		];
	}

	/**
	 * Get event cover image as base64 from the auth service.
	 *
	 * @param string $fileid File ID.
	 * @param array  $args   Optional arguments.
	 * @return array
	 */
	public function auth_get_events_cover_base64image($fileid, $args)
	{
		$request = $this->post(
			'auth_get_events_cover_base64image',
			$this->auth_payload([
				'fileid' => $fileid,
				'arguments' => $args,
			]),
		);

		if (is_wp_error($request)) {
			return [
				'response' => false,
				'message' => __('Network issue.', 'google-calendar-events'),
			];
		}

		$response_arr = $this->decode_response(wp_remote_retrieve_body($request));
		if (!empty($response_arr['response'])) {
			return $response_arr;
		}

		if (!empty($response_arr['message'])) {
			return [
				'response' => false,
				'message' => (string) $response_arr['message'],
			];
		}

		return [
			'response' => false,
			'message' => __('Network issue.', 'google-calendar-events'),
		];
	}
}

new Oauth_Ajax();
