<?php
/**
 * Step: Add New Calendar (placeholder for future step-specific UI).
 *
 * For now this step reuses the API key step UI because the current free plugin
 * Connect screen is a single combined page. We keep this file so future work
 * can move calendar-creation guidance here without touching layout/controller.
 *
 * Variables expected:
 * - string $api_key
 * - bool   $has_api_key
 * - string $assets_base
 */
if (!defined('ABSPATH')) {
	exit();
}

// Reuse current free Connect markup for now.
include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/steps/api-key.php';
