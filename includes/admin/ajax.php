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
				sprintf(__('An error occurred: %s', 'google-calendar-events'), 'You don\'t have permission to make changes.'),
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
			],
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
					$response->get_error_message(),
				),
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
				sprintf(__('An error occurred: %s', 'google-calendar-events'), 'You don\'t have permission to make changes.'),
			);
		}
		delete_option('simple-calendar_settings_licenses');
		delete_option('simple-calendar_licenses_status');

		wp_send_json_success('success');
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
		if (empty($api_key)) {
			wp_send_json_error([
				'message' => __('Please enter an API key.', 'google-calendar-events'),
			]);
		}

		// A known public calendar id we can query without OAuth.
		$public_calendar_id = apply_filters(
			'simcal_validate_api_key_public_calendar_id',
			'en.usa%23holiday%40group.v.calendar.google.com'
		);

		$url = add_query_arg(
			[
				'key' => rawurlencode($api_key),
			],
			'https://www.googleapis.com/calendar/v3/calendars/' . $public_calendar_id
		);

		$response = wp_remote_get($url, [
			'timeout' => 15,
		]);

		if (is_wp_error($response)) {
			wp_send_json_error([
				'message' => $response->get_error_message(),
			]);
		}

		$code = (int) wp_remote_retrieve_response_code($response);

		if (200 === $code) {
			wp_send_json_success([
				'message' => __('API key looks valid and Google Calendar API is reachable.', 'google-calendar-events'),
			]);
		}

		$body = wp_remote_retrieve_body($response);
		$decoded = json_decode($body, true);
		$error_message = '';
		if (is_array($decoded) && isset($decoded['error']['message'])) {
			$error_message = (string) $decoded['error']['message'];
		}

		// Some Google Calendar endpoints have started rejecting API keys entirely and
		// require OAuth tokens instead. In that case, we can't reliably know whether
		// the key is valid or not, so we return a special "reason" and let the UI
		// treat this as "cannot be validated here" instead of hard failure.
		if (401 === $code && $error_message && strpos($error_message, 'API keys are not supported by this API') !== false) {
			wp_send_json_error([
				'message' => $error_message,
				'reason' => 'api_keys_not_supported',
			]);
		}

		wp_send_json_error([
			'message' => $error_message
				? $error_message
				: __(
					'Unable to validate the API key. Please check that it is correct and that the Google Calendar API is enabled in Google Cloud.',
					'google-calendar-events'
				),
		]);
	}
}
