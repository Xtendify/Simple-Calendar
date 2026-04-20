<?php
/**
 * Admin Ajax
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Admin ajax.
 *
 * @since 3.0.0
 */
class Ajax
{
	/**
	 * Set up ajax hooks.
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		// Set an option if the user rated the plugin.
		add_action('wp_ajax_simcal_rated', [$this, 'rate_plugin']);

		// Set an option if the user rated the plugin.
		add_action('wp_ajax_simcal_clear_cache', [$this, 'clear_cache']);

		// Convert a datetime format.
		add_action('wp_ajax_simcal_date_i18n_input_preview', [$this, 'date_i18n']);

		// Manage an add-on license activation or deactivation.
		add_action('wp_ajax_simcal_manage_add_on_license', [$this, 'manage_add_on_license']);

		// Reset add-ons licenses.
		add_action('wp_ajax_simcal_reset_add_ons_licenses', [$this, 'reset_licenses']);

		// Validate Google API key (Connect page).
		add_action('wp_ajax_simcal_validate_google_api_key', [$this, 'validate_google_api_key']);

		// Saved API key health: can we reach public Calendar API? (Connect page badge).
		add_action('wp_ajax_simcal_connect_google_api_key_health_check', [$this, 'connect_google_api_key_health_check']);

		// Check OAuth connection health (Connect page).
		add_action('wp_ajax_simcal_connect_oauth_via_sc_check', [$this, 'connect_oauth_via_sc_check']);
		add_action('wp_ajax_simcal_connect_own_oauth_check', [$this, 'connect_own_oauth_check']);

		// Pro Connect: remember "OAuth via Simple Calendar" choice for progress (before redirect).
		add_action('wp_ajax_simcal_mark_pro_connection_via_sc', [$this, 'mark_pro_connection_via_sc']);
	}

	/**
	 * Store Pro Connect choice: OAuth via Simple Calendar (helper).
	 *
	 * @since 3.6.3
	 */
	public function mark_pro_connection_via_sc()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (!wp_verify_nonce($nonce, 'simcal_mark_pro_connection')) {
			wp_send_json_error(['message' => __('Nonce verification failed.', 'google-calendar-events')], 403);
		}
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('You do not have permission to do this.', 'google-calendar-events')], 403);
		}
		// Site-wide admin setting: store for all users.
		update_option('simple_calendar_connect_pro_connection_type', 'via_sc', false);
		wp_send_json_success();
	}

	/**
	 * Check if OAuth connection is actually working by fetching calendar list.
	 *
	 * @since 3.6.3
	 */
	public function connect_oauth_via_sc_check()
	{
		$nonce = isset($_POST['nonce']) ? esc_attr($_POST['nonce']) : '';
		if (!wp_verify_nonce($nonce, 'simcal_connect_oauth_via_sc_check')) {
			wp_send_json_error(['message' => __('Nonce verification failed.', 'google-calendar-events')], 403);
		}
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('You do not have permission to do this.', 'google-calendar-events')], 403);
		}
		if (!class_exists('\SimpleCalendar\Admin\Oauth_Ajax')) {
			wp_send_json_error(['message' => __('OAuth service is unavailable.', 'google-calendar-events')], 500);
		}

		$oauth = new \SimpleCalendar\Admin\Oauth_Ajax();
		$list = $oauth->auth_get_calendarlist();

		// `auth_get_calendarlist()` returns ['Error' => '...'] on failures.
		if (is_array($list) && isset($list['Error'])) {
			delete_option('simple_calendar_connect_pro_oauth_health_ok');
			wp_send_json_error(['message' => (string) $list['Error']], 200);
		}

		// Treat any non-empty list as connected.
		if (is_array($list) && !empty($list)) {
			update_option('simple_calendar_connect_pro_oauth_health_ok', '1', false);
			wp_send_json_success(['connected' => true]);
		}

		delete_option('simple_calendar_connect_pro_oauth_health_ok');
		wp_send_json_error(['message' => __('Unable to verify connection.', 'google-calendar-events')], 200);
	}

	/**
	 * Check if Own OAuth connection is working (Connect page).
	 *
	 * The Pro add-on should provide the calendar list via filter.
	 *
	 * @since 3.6.3
	 */
	public function connect_own_oauth_check()
	{
		$nonce = isset($_POST['nonce']) ? esc_attr($_POST['nonce']) : '';
		if (!wp_verify_nonce($nonce, 'simcal_connect_oauth_via_sc_check')) {
			wp_send_json_error(['message' => __('Nonce verification failed.', 'google-calendar-events')], 403);
		}
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('You do not have permission to do this.', 'google-calendar-events')], 403);
		}

		$result = apply_filters('simcal_connect_own_oauth_calendarlist', null);

		if (is_wp_error($result)) {
			delete_option('simple_calendar_connect_pro_own_oauth_health_ok');
			wp_send_json_error(['message' => $result->get_error_message()], 200);
		}

		// If the add-on isn't available or there's no token yet, treat as not connected.
		if (!is_array($result) || empty($result)) {
			delete_option('simple_calendar_connect_pro_own_oauth_health_ok');
			wp_send_json_success(['connected' => false]);
		}

		update_option('simple_calendar_connect_pro_own_oauth_health_ok', '1', false);
		wp_send_json_success(['connected' => true]);
	}

	/**
	 * Clear transients.
	 *
	 * @since 3.0.0
	 */
	public function clear_cache()
	{
		$nonce = isset($_POST['nonce']) ? esc_attr($_POST['nonce']) : '';
		if (!wp_verify_nonce($nonce, 'simcal') && !current_user_can('edit_posts')) {
			return;
		}

		$id = isset($_POST['id'])
			? (is_array($_POST['id'])
				? array_map('intval', $_POST['id'])
				: intval($_POST['id']))
			: '';

		if (!empty($id)) {
			simcal_delete_feed_transients($id);
		}
	}

	/**
	 * Ajax callback when a user clicks on the rate plugin link.
	 *
	 * @since 3.0.0
	 */
	public function rate_plugin()
	{
		// Verify this request comes from the add-ons licenses activation settings page.
		$nonce = isset($_POST['nonce']) ? esc_attr($_POST['nonce']) : '';
		if (!wp_verify_nonce($nonce, 'simcal_rating_nonce')) {
			wp_send_json_error(sprintf(__('An error occurred: %s', 'google-calendar-events'), 'Nonce verification failed.'));
		}
		update_option('simple-calendar_admin_footer_text_rated', date('Y-m-d', time()));
	}

	/**
	 * Ajax callback to return a formatted datetime string.
	 *
	 * @since 3.0.0
	 */
	public function date_i18n()
	{
		$value = isset($_POST['value']) ? esc_attr($_POST['value']) : ' ';
		$timestamp = isset($_POST['timestamp']) ? absint($_POST['timestamp']) : time();

		wp_send_json_success(date_i18n($value, $timestamp));
	}

	/**
	 * Activate add-on license.
	 *
	 * This code is run only when an add-on requiring a license is installed and active.
	 *
	 * @since 3.0.0
	 */
	public function manage_add_on_license()
	{
		// Verify this request comes from the add-ons licenses activation settings page.
		$nonce = isset($_POST['nonce']) ? esc_attr($_POST['nonce']) : '';
		if (!wp_verify_nonce($nonce, 'simcal_license_manager')) {
			wp_send_json_error(sprintf(__('An error occurred: %s', 'google-calendar-events'), 'Nonce verification failed.'));
		}
		// Check for user capabilities.
		if (!current_user_can('edit_posts')) {
			wp_send_json_error(
				sprintf(__('An error occurred: %s', 'google-calendar-events'), 'You don\'t have permission to make changes.')
			);
		}

		$addon = isset($_POST['add_on']) ? sanitize_key($_POST['add_on']) : false;
		$action = isset($_POST['license_action']) ? esc_attr($_POST['license_action']) : false;
		$key = isset($_POST['license_key']) ? esc_attr($_POST['license_key']) : '';

		// Verify that there are valid variables to process.
		if (false === $addon || !in_array($action, ['activate_license', 'deactivate_license'])) {
			wp_send_json_error(__('Add-on unspecified or invalid action.', 'google-calendar-events'));
		}

		// Removes the prefix and converts simcal_{id_no} to {id_no}.
		$id = intval(substr($addon, 7));

		// Data to send in API request.
		$api_request = [
			'edd_action' => $action,
			'license' => $key,
			'item_id' => urlencode($id),
			'url' => home_url(),
		];

		// Call the custom API.
		$response = wp_remote_post(
			defined('SIMPLE_CALENDAR_STORE_URL') ? SIMPLE_CALENDAR_STORE_URL : simcal_get_url('home'),
			[
				'timeout' => 15,
				'sslverify' => false,
				'body' => $api_request,
			]
		);

		// Update license in db.
		$keys = get_option('simple-calendar_settings_licenses', []);
		$keys['keys'][$addon] = $key;
		update_option('simple-calendar_settings_licenses', $keys);

		// Make sure there is a response.
		if (is_wp_error($response)) {
			wp_send_json_error(
				sprintf(
					__('There was an error processing your request: %s', 'google-calendar-events'),
					$response->get_error_message()
				)
			);
		}

		// Decode the license data and save.
		$license_data = json_decode(wp_remote_retrieve_body($response));
		$status = simcal_get_license_status();

		if (!empty($license_data)) {
			if ('deactivated' == $license_data->license) {
				unset($status[$addon]);
				update_option('simple-calendar_licenses_status', $status);
				wp_send_json_success($license_data->license);
			} elseif (in_array($license_data->license, ['valid', 'invalid'])) {
				$status[$addon] = $license_data->license;
				update_option('simple-calendar_licenses_status', $status);
				$message =
					'valid' == $license_data->license ? 'valid' : __('License key is invalid.', 'google-calendar-events');
				wp_send_json_success($message);
			} else {
				wp_send_json_error('');
			}
		} else {
			wp_send_json_error(__('An error has occurred, please try again.', 'google-calendar-events'));
		}
	}

	/**
	 * Reset licenses.
	 *
	 * @since 3.0.0
	 */
	public function reset_licenses()
	{
		$nonce = isset($_POST['nonce']) ? esc_attr($_POST['nonce']) : '';

		// Verify this request comes from the add-ons licenses activation settings page.
		if (empty($nonce) || !wp_verify_nonce($nonce, 'simcal_license_manager')) {
			wp_send_json_error(sprintf(__('An error occurred: %s', 'google-calendar-events'), 'Nonce verification failed.'));
		}

		// Check for user capabilities.
		if (!current_user_can('edit_posts')) {
			wp_send_json_error(
				sprintf(__('An error occurred: %s', 'google-calendar-events'), 'You don\'t have permission to make changes.')
			);
		}
		delete_option('simple-calendar_settings_licenses');
		delete_option('simple-calendar_licenses_status');

		wp_send_json_success('success');
	}

	/**
	 * Probe Google Calendar API with an API key (list events on a known public calendar).
	 *
	 * Uses `events.list` so behaviour matches the front end (“retrieve events”). Metadata-only
	 * `calendars.get` can return 200 for some public IDs even when the API key is bogus.
	 *
	 * @since 3.6.3
	 *
	 * @param string $api_key API key.
	 * @return array{ok:bool,message:string,reason?:string}
	 */
	private function test_google_api_key_public_calendar_access($api_key)
	{
		$api_key = trim((string) $api_key);
		if ($api_key === '') {
			return [
				'ok' => false,
				'message' => __('Please enter an API key.', 'google-calendar-events'),
			];
		}

		$public_calendar_id = apply_filters(
			'simcal_validate_api_key_public_calendar_id',
			'en.usa%23holiday%40group.v.calendar.google.com'
		);

		$time_min = gmdate('Y-m-d\TH:i:s\Z', time() - 365 * DAY_IN_SECONDS);
		$events_path = 'https://www.googleapis.com/calendar/v3/calendars/' . $public_calendar_id . '/events';

		$url = add_query_arg(
			[
				'key' => rawurlencode($api_key),
				'maxResults' => 1,
				'singleEvents' => 'true',
				'orderBy' => 'startTime',
				'timeMin' => $time_min,
			],
			$events_path
		);

		$response = wp_remote_get($url, [
			'timeout' => 15,
		]);

		if (is_wp_error($response)) {
			return [
				'ok' => false,
				'message' => $response->get_error_message(),
			];
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);
		$raw_body = (string) $body;
		$decoded = json_decode($body, true);
		if (!is_array($decoded)) {
			$decoded = [];
		}

		// Success: events list payload (invalid keys get 400 before this shape).
		if (
			200 === $code
			&& isset($decoded['kind'])
			&& 'calendar#events' === $decoded['kind']
			&& empty($decoded['error'])
		) {
			return ['ok' => true, 'message' => ''];
		}

		// Invalid key: match JSON or raw body (proxies / odd responses).
		if (
			stripos($raw_body, 'API_KEY_INVALID') !== false
			|| stripos($raw_body, 'API key not valid') !== false
		) {
			return [
				'ok' => false,
				'message' => __('API key is not valid.', 'google-calendar-events'),
				'reason' => 'api_key_invalid',
			];
		}

		$error_message = '';
		if (isset($decoded['error']['message'])) {
			$error_message = (string) $decoded['error']['message'];
		}

		if (isset($decoded['error']['errors']) && is_array($decoded['error']['errors'])) {
			foreach ($decoded['error']['errors'] as $err) {
				if (!is_array($err) || empty($err['message'])) {
					continue;
				}
				$em = (string) $err['message'];
				if (strpos($em, 'API key not valid') !== false) {
					return [
						'ok' => false,
						'message' => __('API key is not valid.', 'google-calendar-events'),
						'reason' => 'api_key_invalid',
					];
				}
			}
		}

		// Prefer stable error reasons when available.
		// Example payload: error.details[0].reason = "API_KEY_INVALID".
		$error_reason = '';
		if (isset($decoded['error']['details']) && is_array($decoded['error']['details'])) {
			foreach ($decoded['error']['details'] as $detail) {
				if (!is_array($detail)) {
					continue;
				}
				if (!empty($detail['reason']) && is_string($detail['reason'])) {
					$error_reason = (string) $detail['reason'];
					break;
				}
				if (!empty($detail['metadata']['reason']) && is_string($detail['metadata']['reason'])) {
					$error_reason = (string) $detail['metadata']['reason'];
					break;
				}
			}
		}

		if ($error_reason === 'API_KEY_INVALID' || strpos($error_message, 'API key not valid') !== false) {
			return [
				'ok' => false,
				'message' => __('API key is not valid.', 'google-calendar-events'),
				'reason' => 'api_key_invalid',
			];
		}

		if (401 === $code && $error_message && strpos($error_message, 'API keys are not supported by this API') !== false) {
			return [
				'ok' => false,
				'message' => $error_message,
				'reason' => 'api_keys_not_supported',
			];
		}

		// HTTP 200 but not a calendar resource and no recognized error above.
		if (200 === $code) {
			return [
				'ok' => false,
				'message' => __(
					'Unable to validate the API key. Please check that it is correct and that the Google Calendar API is enabled in Google Cloud.',
					'google-calendar-events'
				),
			];
		}

		return [
			'ok' => false,
			'message' => $error_message
				? $error_message
				: __(
					'Unable to validate the API key. Please check that it is correct and that the Google Calendar API is enabled in Google Cloud.',
					'google-calendar-events'
				),
		];
	}

	/**
	 * Connect page: check saved Google API key against public Calendar API (header badge).
	 *
	 * @since 3.6.3
	 *
	 * @return void
	 */
	public function connect_google_api_key_health_check()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (empty($nonce) || !wp_verify_nonce($nonce, 'simcal_connect_google_api_key_health')) {
			wp_send_json_error(['message' => __('Nonce verification failed.', 'google-calendar-events')], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('You do not have permission to do this.', 'google-calendar-events')], 403);
		}

		$settings = get_option('simple-calendar_settings_feeds', []);
		$api_key = isset($settings['google']['api_key']) ? trim((string) $settings['google']['api_key']) : '';

		if ($api_key === '') {
			wp_send_json_success([
				'connected' => false,
				'message' => __('No API key is saved yet.', 'google-calendar-events'),
			]);
		}

		$test = $this->test_google_api_key_public_calendar_access($api_key);

		if (!empty($test['ok'])) {
			wp_send_json_success(['connected' => true]);
		}

		$payload = [
			'connected' => false,
			'message' => isset($test['message']) ? (string) $test['message'] : '',
		];
		if (!empty($test['reason'])) {
			$payload['reason'] = (string) $test['reason'];
		}

		wp_send_json_success($payload);
	}

	/**
	 * Validate a Google Calendar API key.
	 *
	 * Checks whether the provided API key can successfully access
	 * a known public Google Calendar resource.
	 *
	 * @since 3.6.3
	 *
	 * @return void
	 */
	public function validate_google_api_key()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (empty($nonce) || !wp_verify_nonce($nonce, 'simcal_connect_validate_google_api_key')) {
			wp_send_json_error([
				'message' => __('Nonce verification failed.', 'google-calendar-events'),
			]);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error([
				'message' => __('You do not have permission to do that.', 'google-calendar-events'),
			]);
		}

		$api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
		if ($api_key === '') {
			wp_send_json_error([
				'message' => __('Please enter an API key.', 'google-calendar-events'),
			]);
		}

		$test = $this->test_google_api_key_public_calendar_access($api_key);

		if (!empty($test['ok'])) {
			wp_send_json_success([
				'message' => __('API key looks valid and Google Calendar API is reachable.', 'google-calendar-events'),
			]);
		}

		if (!empty($test['reason']) && 'api_keys_not_supported' === $test['reason']) {
			wp_send_json_error([
				'message' => $test['message'],
				'reason' => 'api_keys_not_supported',
			]);
		}
		if (!empty($test['reason']) && 'api_key_invalid' === $test['reason']) {
			wp_send_json_error([
				'message' => $test['message'],
				'reason' => 'api_key_invalid',
			]);
		}

		wp_send_json_error([
			'message' => $test['message'],
		]);
	}
}
