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

$scope = simcal_prepare_connect_sidebar_scope();
extract($scope);

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
