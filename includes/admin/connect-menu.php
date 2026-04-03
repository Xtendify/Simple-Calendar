<?php
/**
 * Connect menu page handler.
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

if (!defined('ABSPATH')) {
	exit();
}

class Connect_Menu
{
	/**
	 * Render the Connect page.
	 *
	 * @since 3.6.3
	 *
	 * @return void
	 */
	public static function html()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		$welcome_context = (string) get_option('simple_calendar_connect_welcome_context', '');
		$welcome_context = $welcome_context ? $welcome_context : 'core';

		// Handle welcome screen "Next" action.
		if (
			'POST' === $_SERVER['REQUEST_METHOD'] && 
			isset($_POST['simcal_connect_welcome_next'])
		) {
			check_admin_referer('simcal_connect_welcome_next_action', 'simcal_connect_welcome_nonce');
			if ('core' === $welcome_context) {
				update_option('simple-calendar_connect_welcome_dismissed', 1);
			} else {
				// Site-wide dismissal per context (e.g. pro).
				update_option('simple-calendar_connect_welcome_dismissed_' . $welcome_context, 1);
			}

			wp_safe_redirect(admin_url('edit.php?post_type=calendar&page=simple-calendar_connect'));
			exit();
		}

		// Allow forcing welcome via query arg (used after add-on activation).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$force_welcome = isset($_GET['sc_welcome']) && '1' === (string) $_GET['sc_welcome'];

		$core_dismissed = (bool) get_option('simple-calendar_connect_welcome_dismissed');
		$context_dismissed =
			('core' === $welcome_context)
				? $core_dismissed
				: (bool) get_option('simple-calendar_connect_welcome_dismissed_' . $welcome_context, false);

		$show_welcome = $force_welcome || !$context_dismissed;
		include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect-controller.php';
	}
}
