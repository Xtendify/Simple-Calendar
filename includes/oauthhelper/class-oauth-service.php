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
		add_action('simple_calendar_auth_via_google_button', [$this, 'add_auth_via_google_button']);
		add_action('simple_calendar_auth_via_xtendify_button', [$this, 'add_auth_via_xtendify_button']);

		add_filter('simple_calendar_oauth_list_events', [$this, 'oauth_helper_event_display'], 10, 3);

		add_filter('simple_calendar_oauth_get_calendars', [$this, 'oauth_helper_get_calendar'], 10, 3);

		add_filter('simple_calendar_oauth_schedule_events', [$this, 'oauth_helper_schedule_events'], 10, 2);
	}

	/**
	 * Add a panel to the settings meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 */
	public function add_auth_via_google_button()
	{
		$auth_btn = '';

		$auth_btn .=
			'<div class="simcal-auth-tabs"><ul id="simcal-auth-tabs-nav"><li><a href="#selfcredentials">' .
			__('Authenticate with google', 'google-calendar-events') .
			'</a></li><li><a href="#authViaXtendify">' .
			__('Auth Via Simple Calendar', 'google-calendar-events') .
			'</a></li></ul>';

		$auth_btn .= '<div id="simcal-auth-tabs-content"><div id="selfcredentials" class="simcal-auth-tab-content">';

		echo $auth_btn;
	}

	/**
	 * Add a panel to the settings meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 */
	public function add_auth_via_xtendify_button()
	{
		$site_url = get_site_url();
		$query_arg = [
			'request_from' => $site_url,
		];
		$authredirect = add_query_arg($query_arg, SIMPLE_CALENDAR_OAUTH_HELPER_AUTH_DOMAIN . 'helper/');
		$admin_image_about_path = SIMPLE_CALENDAR_ASSETS . '/images';

		if (!empty($_GET['auth_token'])) {
			$auth_token = sanitize_text_field($_GET['auth_token']);
			add_option('simple_calendar_auth_site_token', $auth_token, '', true);
		}

		$simple_calendar_auth_site_token = get_option('simple_calendar_auth_site_token');
		wp_nonce_field('oauth_action_deauthentication', 'oauth_action_deauthentication');
		echo '</div><div id="authViaXtendify" class="simcal-auth-tab-content">';

		$display_auth_via_xtendiyf_cta = '';
		$afterauth_via_xtendiyf_cta = '';

		if (isset($simple_calendar_auth_site_token) && $simple_calendar_auth_site_token) {
			$display_auth_via_xtendiyf_cta = 'hide';
		} else {
			$afterauth_via_xtendiyf_cta = 'hide';
		}
		echo '<div class="afterauth-via-xtendify-cta ' . $afterauth_via_xtendiyf_cta . '">';
		echo '<img class="simcal-m-auto" src="' . esc_url($admin_image_about_path) . '/pages/settings/auth_success.png" />';
		echo '<h2>' . __('Successfully Aunthenticate Via Simple Calendar', 'google-calendar-events') . '</h2>';
		echo '<p>' .
			__('Integrate with Google via Simple Calendar (no pain of credetials needed)', 'google-calendar-events') .
			'</p>';
		echo '<a href="javascript:void(0);" id="oauth_deauthentication" class="action_deauthentication simcal-mt-[50px] simcal-m-auto simcal-flex simcal-justify-center simcal-items-center simcal-w-[40%] simcal-h-[40px] simcal-bg-sc_green-200 hover:simcal-text-white simcal-text-white simcal-text-xl simcal-font-medium simcal-rounded-md" data-dialog="Are you sure you want to DeAuthenticate.">';
		_e('Deauthenticate', 'google-calendar-events');
		echo '<i class="simcal-icon-spinner simcal-icon-spin" style="display: none;"></i>';
		echo '</a>';
		echo '</div>';
		echo '<div class="auth-via-xtendify-cta ' . $display_auth_via_xtendiyf_cta . '">';
		echo '<h2>' . __('Authenticate With Google Via Simple Calendar', 'google-calendar-events') . '</h2>';
		echo '<p>' .
			__('Integrate with Google via Simple Calendar (no pain of credetials needed)', 'google-calendar-events') .
			'</p>';
		echo '<a href="' .
			$authredirect .
			'" class="action_auth_via_xtendify simcal-mt-[50px] simcal-m-auto simcal-flex simcal-justify-center simcal-items-center simcal-w-[40%] simcal-h-[40px] simcal-bg-sc_green-200 hover:simcal-text-white simcal-text-white simcal-text-xl simcal-font-medium simcal-rounded-md">';
		_e('Authenticate', 'google-calendar-events');
		echo '</a>';
		echo '</div>';
		echo '</div>';
		echo '</div>';

		echo '</div>';
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
}

new Auth_Service_Helpers();
