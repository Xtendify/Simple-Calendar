<?php
/**
 * Step: API Key.
 *
 * Variables expected:
 * - string $api_key
 * - bool   $has_api_key
 * - string $assets_base
 */
if (!defined('ABSPATH')) {
	exit();
}

$is_pro_active = false;
if (defined('SIMPLE_CALENDAR_GOOGLE_PRO_VERSION')) {
	$is_pro_active = true;
} elseif (class_exists('Google_Pro')) {
	$is_pro_active = true;
} elseif (isset($welcome_context) && 'pro' === (string) $welcome_context) {
	$is_pro_active = true;
}

$setup_video_url = $is_pro_active
	? 'https://youtu.be/lmN774Fk3rw?si=zBMoOck0BjI7Q39j'
	: 'https://youtu.be/3QveIbm5Oc0?si=fMEUU0Za7KFzlJk5';

$sc_google_api_key_mode = 'api_key_step';
include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/google-calendar-api-key-connect.php';
?>

<?php include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/helpful-links.php';
?>
