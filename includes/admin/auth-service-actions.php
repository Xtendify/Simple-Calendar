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
	public static $url = SIMPLE_CALENDAR_AUTH_DOMAIN . 'wp-json/api-test/v1/';

	/**
	 * My site URL
	 */
	public static $my_site_url = '';

	/**
	 * Set up ajax hooks.
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		// Set an option if the user rated the plugin.
		add_action('wp_ajax_oauth_deauthenticate_site', [$this, 'oauth_deauthenticate_site']);
		//add_action('wp_ajax_nopriv_oauth_deauthenticate_site', [$this, 'oauth_deauthenticate_site']);
		// Do toke check here.
		$post_type = get_post_type();
		if ($post_type == 'calendar') {
			add_action('admin_init', [$this, 'oauth_check_iftoken_expired']);
		}

		self::$my_site_url = site_url();
	}

	/**
	 * DeAuthenticate.
	 *
	 */
	public function oauth_deauthenticate_site()
	{
		$nonce = isset($_POST['nonce']) ? esc_attr($_POST['nonce']) : '';
		if (!wp_verify_nonce($nonce, 'oauth_action_deauthentication') && !current_user_can('edit_posts')) {
			return;
		}
		$send_data = [
			'site_url' => self::$my_site_url,
			'auth_token' => get_option('simple_calendar_auth_site_token'),
		];

		$request = wp_remote_post(self::$url . 'de_authenticate_site', [
			'method' => 'POST',
			'body' => $send_data,
			'cookies' => [],
		]);

		if (is_wp_error($request) || wp_remote_retrieve_response_code($request) != 200) {
			error_log(print_r($request, true));
		}

		$response = wp_remote_retrieve_body($request);

		$error_msg = [];
		if ($response == true || $response >= 1) {
			delete_option('simple_calendar_auth_site_token');
			$message = __('DeAuthenticate Successfully.', 'google-calendar-events');
			$send_msg = ['message' => $message];
			wp_send_json_success($send_msg);
		} else {
			if (isset($message['message']) && !empty($message['message'])) {
				$message = $message['message'];
			} else {
				$message = __('DeAuthentication Faild.', 'google-calendar-events');
			}

			$error_msg = ['message' => $message];
			wp_send_json_error($error_msg);
		}
		die();
	}

	/*
	 * Check if token expire
	 */
	public function oauth_check_iftoken_expired()
	{
		$send_data = [
			'site_url' => self::$my_site_url,
			'auth_token' => get_option('simple_calendar_auth_site_token'),
		];
		$request = wp_remote_post(self::$url . 'check_iftoken_expired', [
			'method' => 'POST',
			'body' => $send_data,
			'cookies' => [],
		]);

		$response = wp_remote_retrieve_body($request);

		if ($response == true || $response >= 1) {
			return 'valid';
		} else {
			delete_option('simple_calendar_auth_site_token');
			return 'invalid';
		}
	}

	/*
	 * Get calendar Events
	 */
	public function auth_get_calendarsevents($id, $args)
	{
		$send_data = [
			'site_url' => self::$my_site_url,
			'auth_token' => get_option('simple_calendar_auth_site_token'),
			'id' => $id,
			'arguments' => $args,
		];
		$request = wp_remote_post(self::$url . 'get_calendar_events', [
			'method' => 'POST',
			'body' => $send_data,
			'timeout' => 30,
			'cookies' => [],
		]);

		$response = wp_remote_retrieve_body($request);
		$response_arr = json_decode($response, true);

		if (isset($response_arr['response']) && !empty($response_arr['response'])) {
			if ($response_arr['response']) {
				$response_data = $response_arr['data'];

				return unserialize($response_data);
			}
		} else {
			$response = [
				'Error' => __('Network issue.', 'google-calendar-events'),
			];
			return $response;
		}
		$response = [
			'Error' => __('There is something wrong. please re-try.', 'google-calendar-events'),
		];
		return $response;
	}
} //class End

new Oauth_Ajax();
