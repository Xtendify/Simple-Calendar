<?php
/**
 * Connect onboarding controller.
 *
 * Decides which onboarding step to show and wires layout + sidebar.
 *
 * Variables expected:
 * - bool $show_welcome
 */
if (!defined('ABSPATH')) {
	exit();
}

// Ensure variable exists.
$show_welcome = isset($show_welcome) ? (bool) $show_welcome : false;

$assets_base = SIMPLE_CALENDAR_ASSETS . 'images/admin/';

// Welcome context (core vs add-on).
$welcome_context = (string) get_option('simple_calendar_connect_welcome_context', '');
$welcome_context = $welcome_context ? $welcome_context : 'core';

// Detect if Google Calendar Pro (or similar) is active.
$is_pro_active = false;
if (!function_exists('is_plugin_active') && defined('ABSPATH')) {
	$plugin_file = trailingslashit(ABSPATH) . 'wp-admin/includes/plugin.php';
	if (is_readable($plugin_file)) {
		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		require_once $plugin_file;
	}
}

$is_pro_active = false;
if (defined('SIMPLE_CALENDAR_GOOGLE_PRO_VERSION')) {
	$is_pro_active = true;
} elseif (class_exists('Google_Pro')) {
	// Pro add-on exposes a unique class name.
	$is_pro_active = true;
} elseif (function_exists('is_plugin_active')) {
	$is_pro_active =
		is_plugin_active('Simple-Calendar-Google-Calendar-Pro/simple-calendar-google-calendar-pro.php') ||
		is_plugin_active('Simple-Calendar-Google-Calendar-Pro-main/simple-calendar-google-calendar-pro.php') ||
		is_plugin_active('simple-calendar-google-calendar-pro/simple-calendar-google-calendar-pro.php');
} else {
	// Fallback for cases where plugin.php isn't available yet.
	$active_plugins = (array) get_option('active_plugins', []);
	$active_plugins = array_map('strval', $active_plugins);
	foreach ($active_plugins as $p) {
		$p_lower = strtolower($p);
		if (
			strpos($p_lower, 'google-calendar-pro') !== false ||
			strpos($p_lower, 'simple-calendar-google-calendar-pro') !== false
		) {
			$is_pro_active = true;
			break;
		}
	}
}
$is_pro_active = (bool) apply_filters('simcal_is_google_calendar_pro_active', $is_pro_active);

// Pro Connect: connection type choice is stored site-wide (options) in the step template / ajax.

// Clean stale context when Pro is no longer active.
if (!$is_pro_active && 'pro' === $welcome_context) {
	$welcome_context = 'core';
	delete_option('simple_calendar_connect_welcome_context');
}

// OAuth via Simple Calendar callback: accept auth_token only on a validated callback.
if (!empty($_GET['auth_token'])) {
	$auth_token = sanitize_text_field((string) wp_unslash($_GET['auth_token']));
	$state = isset($_GET['state']) ? sanitize_text_field((string) wp_unslash($_GET['state'])) : '';

	$helper_origin_ok = false;
	$helper_domain = defined('SIMPLE_CALENDAR_OAUTH_HELPER_AUTH_DOMAIN')
		? (string) SIMPLE_CALENDAR_OAUTH_HELPER_AUTH_DOMAIN
		: '';
	$helper_host = $helper_domain ? wp_parse_url($helper_domain, PHP_URL_HOST) : '';
	$ref = wp_get_raw_referer();
	if ($helper_host && $ref) {
		$ref_host = wp_parse_url($ref, PHP_URL_HOST);
		$helper_origin_ok = $ref_host && strtolower((string) $ref_host) === strtolower((string) $helper_host);
	}

	$state_ok = $state && wp_verify_nonce($state, 'simcal_oauth_via_sc_state');

	if ($auth_token && current_user_can('manage_options') && $state_ok && $helper_origin_ok) {
		// Controlled overwrite: store/refresh the site-wide auth token.
		update_option('simple_calendar_auth_site_token', $auth_token, true);
		if ($is_pro_active) {
			update_option('simple_calendar_connect_pro_connection_type', 'via_sc', false);
		}
	}
}
// Compute onboarding state.
$feeds_options = get_option('simple-calendar_settings_feeds', []);
$api_key = isset($feeds_options['google']['api_key']) ? $feeds_options['google']['api_key'] : '';
$has_api_key = !empty(trim((string) $api_key));
$has_core_api_key_verified = function_exists('simcal_is_connect_google_api_key_verified')
	? simcal_is_connect_google_api_key_verified((string) $api_key)
	: false;
$auth_site_token = (string) get_option('simple_calendar_auth_site_token', '');
$has_oauth_connection = !empty(trim($auth_site_token));
$google_pro =
	isset($feeds_options['google-pro']) && is_array($feeds_options['google-pro']) ? $feeds_options['google-pro'] : [];
$has_client_credentials =
	!empty(trim((string) ($google_pro['client_id'] ?? ''))) &&
	!empty(trim((string) ($google_pro['client_secret'] ?? '')));

// Pro onboarding completion requires a Pro calendar feed configured (e.g. google-pro).
// Some installs store the feed type in taxonomy `calendar_feed`, others in meta `_feed_type`.
$has_published_pro_calendar = false;

// Prefer taxonomy check (current storage). Fall back to legacy meta only if needed.
$pro_query_base = [
	'post_type' => 'calendar',
	'post_status' => 'publish',
	'posts_per_page' => 1,
	'fields' => 'ids',
];

if (taxonomy_exists('calendar_feed')) {
	$pro_calendar_query = new WP_Query(
		array_merge($pro_query_base, [
			'tax_query' => [
				[
					'taxonomy' => 'calendar_feed',
					'field' => 'slug',
					'terms' => ['google-pro', 'google_pro'],
				],
			],
		]),
	);
	$has_published_pro_calendar = $pro_calendar_query->have_posts();
	wp_reset_postdata();
}

if (!$has_published_pro_calendar) {
	// Legacy meta key used by some versions.
	$pro_calendar_query = new WP_Query(
		array_merge($pro_query_base, [
			'meta_query' => [
				'relation' => 'OR',
				[
					'key' => '_feed_type',
					'value' => 'google-pro',
					'compare' => '=',
				],
				[
					'key' => '_feed_type',
					'value' => 'google_pro',
					'compare' => '=',
				],
			],
		]),
	);
	$has_published_pro_calendar = $pro_calendar_query->have_posts();
	wp_reset_postdata();
}

$calendar_query = new WP_Query([
	'post_type' => 'calendar',
	'post_status' => 'publish',
	'posts_per_page' => 1,
	'fields' => 'ids',
]);
$has_published_calendar = $calendar_query->have_posts();
wp_reset_postdata();

// Track when setup first reached 100% so we can hide the progress card after a day.
// Core: 100% means verified API key + published calendar (not calendar alone).
$completed_timestamp = (int) get_option('simple-calendar_connect_setup_completed_at', 0);
$core_setup_complete = !$is_pro_active && $has_core_api_key_verified && $has_published_calendar;
if ($core_setup_complete && $completed_timestamp <= 0) {
	$completed_timestamp = time();
	update_option('simple-calendar_connect_setup_completed_at', $completed_timestamp);
}
if (!$is_pro_active && !$core_setup_complete && $completed_timestamp > 0) {
	// User regressed (e.g. removed key): reset so progress bar is not wrong.
	delete_option('simple-calendar_connect_setup_completed_at');
	$completed_timestamp = 0;
}
$hide_progress_after = DAY_IN_SECONDS;
$should_hide_progress =
	$core_setup_complete && $completed_timestamp > 0 && time() - $completed_timestamp >= $hide_progress_after;

// Determine whether we're in Pro onboarding flow.
// Pro flow must never be shown when Pro is inactive.
$is_pro_flow = $is_pro_active;

// For Pro onboarding, mirror core: keep the progress card until setup is complete, then hide it after one day
// (rating card only; no Pro upsell — see sidebar).
if ($is_pro_flow) {
	// For Pro, "100% complete" should require a published Pro calendar feed.
	// OAuth health checks indicate connectivity only and should not mark onboarding complete.
	$pro_onboarding_complete = (bool) $has_published_pro_calendar;

	$pro_completed_timestamp = (int) get_option('simple-calendar_connect_pro_setup_completed_at', 0);
	if ($pro_onboarding_complete && $pro_completed_timestamp <= 0) {
		$pro_completed_timestamp = time();
		update_option('simple-calendar_connect_pro_setup_completed_at', $pro_completed_timestamp, false);
	}

	if (
		$pro_onboarding_complete &&
		$pro_completed_timestamp > 0 &&
		time() - $pro_completed_timestamp >= $hide_progress_after
	) {
		$should_hide_progress = true;
	} else {
		$should_hide_progress = false;
	}
}

// Decide current step.
$step = 'api_key';
if ($show_welcome) {
	$step = 'welcome';
} elseif ($is_pro_flow) {
	// Pro onboarding: always start on Credentials step.
	// (This step contains "OAuth via Simple Calendar" and can route to own credentials.)
	$step = 'credentials';
} elseif ($has_core_api_key_verified && !$has_published_calendar) {
	$step = 'add_calendar';
}

$step_title_map = [
	'welcome' => __('Video guide', 'google-calendar-events'),
	'credentials' => __('Connect', 'google-calendar-events'),
	'api_key' => __('Add API Key', 'google-calendar-events'),
	'add_calendar' => __('Display', 'google-calendar-events'),
];
$step_title = isset($step_title_map[$step]) ? $step_title_map[$step] : __('Connect', 'google-calendar-events');

$step_template_map = [
	'welcome' => SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/steps/welcome.php',
	'credentials' => SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/steps/pro-credentials.php',
	'api_key' => SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/steps/core-credentials.php',
	'add_calendar' => SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/steps/core-credentials.php',
];
$step_template_path = isset($step_template_map[$step]) ? $step_template_map[$step] : $step_template_map['api_key'];

$context = [
	// Shared.
	'assets_base' => $assets_base,
	'hide_sidebar' => 'welcome' === $step,
	'current_step' => $step,
	// Step state.
	'api_key' => $api_key,
	'has_api_key' => $has_api_key,
	'has_core_api_key_verified' => $has_core_api_key_verified,
	'has_oauth_connection' => $has_oauth_connection,
	'has_client_credentials' => $has_client_credentials,
	'has_published_calendar' => $has_published_calendar,
	'has_published_pro_calendar' => $has_published_pro_calendar,
	'should_hide_progress' => $should_hide_progress,
	// Welcome.
	'video_url' => (function () use ($is_pro_flow) {
		$core_video_url = 'https://www.youtube.com/embed/3QveIbm5Oc0?si=fMEUU0Za7KFzlJk5';
		$pro_video_url = 'https://www.youtube.com/embed/lmN774Fk3rw?si=zBMoOck0BjI7Q39j';
		$default = $is_pro_flow ? $pro_video_url : $core_video_url;

		/**
		 * Filter the welcome video embed URL shown on the Connect welcome step.
		 *
		 * @since 3.6.2
		 *
		 * @param string $default_embed_url Default YouTube embed URL.
		 * @param string $context          'core' or 'pro'.
		 */
		return apply_filters('simple_calendar_connect_welcome_video_url', $default, $is_pro_flow ? 'pro' : 'core');
	})(),
	'welcome_context' => $is_pro_flow ? 'pro' : $welcome_context,
	// Sidebar.
	'sidebar_template_path' => SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/sidebar.php',
];

include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/layout.php';
