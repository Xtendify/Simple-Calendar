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

// Compute onboarding state.
$feeds_options = get_option('simple-calendar_settings_feeds', []);
$api_key = isset($feeds_options['google']['api_key']) ? $feeds_options['google']['api_key'] : '';
$has_api_key = !empty(trim((string) $api_key));

$calendar_query = new WP_Query([
	'post_type' => 'calendar',
	'post_status' => 'publish',
	'posts_per_page' => 1,
	'fields' => 'ids',
]);
$has_published_calendar = $calendar_query->have_posts();
wp_reset_postdata();

// Track when setup first reached 100% so we can hide the progress card after a day.
$completed_timestamp = (int) get_option('simple-calendar_connect_setup_completed_at', 0);
if ($has_published_calendar && $completed_timestamp <= 0) {
	$completed_timestamp = time();
	update_option('simple-calendar_connect_setup_completed_at', $completed_timestamp);
}
$hide_progress_after = DAY_IN_SECONDS;
$should_hide_progress =
	$has_published_calendar && $completed_timestamp > 0 && time() - $completed_timestamp >= $hide_progress_after;

// Decide current step.
$step = 'api_key';
if ($show_welcome) {
	$step = 'welcome';
} elseif ($has_api_key && !$has_published_calendar) {
	$step = 'add_calendar';
}

$step_title_map = [
	'welcome' => __('Welcome', 'google-calendar-events'),
	'api_key' => __('Add API Key', 'google-calendar-events'),
	'add_calendar' => __('Add New Calendar', 'google-calendar-events'),
];
$step_title = isset($step_title_map[$step]) ? $step_title_map[$step] : __('Connect', 'google-calendar-events');

$step_template_map = [
	'welcome' => SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/steps/welcome.php',
	'api_key' => SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/steps/api-key.php',
	'add_calendar' => SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/steps/add-calendar.php',
];
$step_template_path = isset($step_template_map[$step]) ? $step_template_map[$step] : $step_template_map['api_key'];

$context = [
	// Shared.
	'assets_base' => $assets_base,
	// Step state.
	'api_key' => $api_key,
	'has_api_key' => $has_api_key,
	'has_published_calendar' => $has_published_calendar,
	'should_hide_progress' => $should_hide_progress,
	// Welcome.
	'video_url' => apply_filters('simple_calendar_connect_welcome_video_url', 'https://www.youtube.com/embed/VIDEO_ID'),
	// Sidebar.
	'sidebar_template_path' => SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/sidebar.php',
];

include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/layout.php';
