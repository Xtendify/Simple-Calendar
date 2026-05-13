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
	 * Suppress all admin notices on the Connect page.
	 *
	 * This prevents third-party plugins/themes from injecting notices into the
	 * Connect onboarding UI.
	 *
	 * Runs on `in_admin_header` (hooked from `load-{hook}`) so it executes before
	 * WordPress prints notices in the admin header.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function suppress_admin_notices()
	{
		// Only suppress on the Connect screen.
		if (!function_exists('get_current_screen')) {
			return;
		}
		$screen = get_current_screen();
		$connect_ids = [
			'calendar_page_simple-calendar_connect',
			'calendar_page_simple-calendar_settings',
			'index_page_simple-calendar_connect',
			'index_page_simple-calendar_settings',
			'dashboard_page_simple-calendar_connect',
			'dashboard_page_simple-calendar_settings',
		];
		if (!$screen || !isset($screen->id) || !in_array($screen->id, $connect_ids, true)) {
			return;
		}

		// Remove all callbacks that print notices (including other plugins/themes).
		// Intentionally hides all notices on this screen to keep the onboarding UI clean.
		remove_all_actions('admin_notices');
		remove_all_actions('all_admin_notices');
		remove_all_actions('network_admin_notices');
		remove_all_actions('user_admin_notices');
	}

	/**
	 * Handle form actions before admin header output.
	 *
	 * Runs on `load-{hook}` so redirects can send headers.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function handle_actions()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		// If Pro is active but outdated, block Connect actions as well.
		if (!\simcal_is_google_calendar_pro_version_compatible('2.0.0')) {
			return;
		}
		if(!\simcal_is_google_calendar_book_an_appointment_version_compatible('2.0.0')) {
			return;
		}

		$welcome_context = (string) get_option('simple_calendar_connect_welcome_context', '');
		$welcome_context = $welcome_context ? $welcome_context : 'core';

		// Handle welcome screen "Next" action.
		if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['simcal_connect_welcome_next'])) {
			check_admin_referer('simcal_connect_welcome_next_action', 'simcal_connect_welcome_nonce');

			if ('core' === $welcome_context) {
				update_option('simple-calendar_connect_welcome_dismissed', 1);
			} else {
				// Site-wide dismissal per context (e.g. pro).
				update_option('simple-calendar_connect_welcome_dismissed_' . $welcome_context, 1);
			}

			wp_safe_redirect(admin_url('edit.php?post_type=calendar&page=simple-calendar_settings'));
			exit();
		}
	}

	/**
	 * Render the Connect page.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function html()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		$min_pro_version =  $min_book_an_appointment_version = '2.0.0';
		if (!\simcal_is_google_calendar_pro_version_compatible($min_pro_version) ) {
			$plugins_url = admin_url('plugins.php'); ?>
			<div class="wrap">
				<div class="notice notice-error">
					<p>
						<strong><?php esc_html_e('Update required.', 'google-calendar-events'); ?></strong>
						<?php printf(
      	/* translators: 1: installed version 2: required version */
      	esc_html__(
      		'Your Google Calendar Pro add-on version (%1$s) is not compatible. Please update it to %2$s or newer to use Connect and Settings.',
      		'google-calendar-events',
      	),
      	defined('SIMPLE_CALENDAR_GOOGLE_PRO_VERSION')
      		? esc_html((string) SIMPLE_CALENDAR_GOOGLE_PRO_VERSION)
      		: esc_html__('unknown', 'google-calendar-events'),
      	esc_html($min_pro_version),
      ); ?>
					</p>
					<p>
						<a class="button button-primary" href="<?php echo esc_url($plugins_url); ?>">
							<?php esc_html_e('Go to Plugins', 'google-calendar-events'); ?>
						</a>
					</p>
				</div>
			</div>
			<?php return;
		}
		
		if (!\simcal_is_google_calendar_book_an_appointment_version_compatible($min_book_an_appointment_version) ) {
				$plugins_url = admin_url('plugins.php'); ?>
				<div class="wrap">
					<div class="notice notice-error">
						<p>
							<strong><?php esc_html_e('Update required.', 'google-calendar-events'); ?></strong>
							<?php printf(
			  /* translators: 1: installed version 2: required version */
			  esc_html__(
				  'Your Book an Appointment add-on version (%1$s) is not compatible. Please update it to %2$s or newer to use Connect and Settings.',
				  'google-calendar-events',
			  ),
			  defined('SIMPLE_CALENDAR_APPOINTMENT_VERSION')
				  ? esc_html((string) SIMPLE_CALENDAR_APPOINTMENT_VERSION)
				  : esc_html__('unknown', 'google-calendar-events'),
			  esc_html($min_book_an_appointment_version),
		  ); ?>
						</p>
						<p>
							<a class="button button-primary" href="<?php echo esc_url($plugins_url); ?>">
								<?php esc_html_e('Go to Plugins', 'google-calendar-events'); ?>
							</a>
						</p>
					</div>
				</div>
				<?php return;
			}



		$welcome_context = (string) get_option('simple_calendar_connect_welcome_context', '');
		$welcome_context = $welcome_context ? $welcome_context : 'core';

		// Allow forcing welcome via query arg (used after add-on activation).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$force_welcome = isset($_GET['sc_welcome']) && '1' === (string) $_GET['sc_welcome'];

		$core_dismissed = (bool) get_option('simple-calendar_connect_welcome_dismissed');
		$context_dismissed =
			'core' === $welcome_context
				? $core_dismissed
				: (bool) get_option('simple-calendar_connect_welcome_dismissed_' . $welcome_context, false);

		$show_welcome = $force_welcome || !$context_dismissed;
		include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect-controller.php';
	}
}
