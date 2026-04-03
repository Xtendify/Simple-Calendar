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

		// Handle welcome screen "Next" action.
		if (
			'POST' === $_SERVER['REQUEST_METHOD'] && // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			isset($_POST['simcal_connect_welcome_next']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		) {
			check_admin_referer('simcal_connect_welcome_next_action', 'simcal_connect_welcome_nonce');
			update_option('simple-calendar_connect_welcome_dismissed', 1);

			wp_safe_redirect(admin_url('edit.php?post_type=calendar&page=simple-calendar_connect'));
			exit();
		}

		$show_welcome = !get_option('simple-calendar_connect_welcome_dismissed');
		include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect-controller.php';
	}
}
