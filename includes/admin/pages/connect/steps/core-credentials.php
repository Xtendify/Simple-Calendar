<?php
/**
 * Step: API Key.
 *
 * Variables expected:
 * - string $api_key
 * - bool   $has_api_key
 * - string $assets_base
 * - string $setup_video_url
 */
if (!defined('ABSPATH')) {
	exit();
}

$sc_google_api_key_mode = 'api_key_step';
include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/google-calendar-api-key-connect.php';
?>

<?php include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/helpful-links.php';
?>
