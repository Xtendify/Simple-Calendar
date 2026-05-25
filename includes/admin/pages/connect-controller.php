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

$welcome_context_for_template =
	$is_pro_flow && 'appointment' !== (string) $welcome_context ? 'pro' : (string) $welcome_context;

$core_setup_video_url = 'https://youtu.be/whpV-uM4RZg?si=Qza-4B8l0zG1he_X';
$pro_setup_video_url = 'https://youtu.be/V-TkmyTwdI8?si=VyjMFbG8fUk54hlk';
$setup_video_url = $is_pro_flow ? $pro_setup_video_url : $core_setup_video_url;

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
	// Helpful links (core / pro setup videos only; no appointment variant).
	'setup_video_url' => $setup_video_url,
	// Welcome step embed URLs.
	'video_url' => (function () use ($is_pro_flow, $welcome_context_for_template) {
		$core_video_embed = 'https://www.youtube.com/embed/whpV-uM4RZg?si=Ze8syeOg6N6KMLvb&amp;start=39';
		$pro_video_embed = 'https://www.youtube.com/embed/V-TkmyTwdI8?si=Lc5K8S7EWQB6Vu8a&amp;start=13';
		$appointment_video_embed = 'https://www.youtube.com/embed/EMHgh9UG80k?si=2sleDAqR1ppobNkE&amp;start=25';
		$default =
			'appointment' === $welcome_context_for_template
				? $appointment_video_embed
				: ($is_pro_flow
					? $pro_video_embed
					: $core_video_embed);

		/**
		 * Filter the welcome video embed URL shown on the Connect welcome step.
		 *
		 * @since 3.6.2
		 *
		 * @param string $default_embed_url Default YouTube embed URL.
		 * @param string $context          'core', 'pro', or 'appointment'.
		 */
		return apply_filters('simple_calendar_connect_welcome_video_url', $default, $welcome_context_for_template);
	})(),
	'welcome_context' => $welcome_context_for_template,
	// Sidebar.
	'sidebar_template_path' => SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/sidebar.php',
];

include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/layout.php';
