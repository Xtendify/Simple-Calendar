<?php
/**
 * Update Plugin
 *
 * @package SimpleCalendar/Updates
 */
namespace SimpleCalendar;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Update script.
 *
 * Updates the installed plugin to the current version.
 *
 * @since 3.0.0
 */
class Update
{
	/**
	 * Previous version.
	 *
	 * @access protected
	 * @var string
	 */
	private $installed_ver = '0.0.0';

	/**
	 * Current version.
	 *
	 * @access private
	 * @var string
	 */
	private $new_ver = '0.0.0';

	/**
	 * Existing posts.
	 *
	 * @access private
	 * @var array
	 */
	private $posts = [];

	/**
	 * Update path.
	 *
	 * @access private
	 *
	 * @var array
	 */
	private $update_path = ['2.1.0', '2.2.0', '3.0.0', '3.0.13'];

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $version (optional) Current plugin version, defaults to value in plugin constant.
	 */
	public function __construct($version = SIMPLE_CALENDAR_VERSION)
	{
		// Look for previous version in current or legacy option, null for fresh install.
		$installed = get_option('simple-calendar_version', null);
		$this->installed_ver = is_null($installed) ? get_option('gce_version', null) : $installed;
		$this->new_ver = $version;

		if (version_compare($this->installed_ver, $this->new_ver, '<')) {
			$this->run_updates();
		}
	}

	/**
	 * Update to current version.
	 *
	 * Runs all the update scripts through version steps.
	 *
	 * @since 3.0.0
	 */
	public function run_updates()
	{
		do_action('simcal_before_update', $this->installed_ver);

		if (!is_null($this->installed_ver)) {
			if (version_compare($this->installed_ver, $this->new_ver) === -1) {
				$post_type = version_compare($this->installed_ver, '3.0.0') === -1 ? 'gce_feed' : 'calendar';
				$this->posts = $this->get_posts($post_type);

				foreach ($this->update_path as $update_to) {
					if (version_compare($this->installed_ver, $update_to, '<')) {
						$this->update($update_to);
					}
				}
			}

			simcal_delete_feed_transients();
		} else {
			new Post_Types();
			flush_rewrite_rules();
		}

		do_action('simcal_updated', $this->new_ver);

		// Redirect to a welcome page if new install or major update.
		if (is_null($this->installed_ver)) {
			set_transient('_simple-calendar_activation_redirect', 'fresh', 60);
		} else {
			$major_new = substr($this->new_ver, 0, strrpos($this->new_ver, '.'));
			$major_old = substr($this->installed_ver, 0, strrpos($this->installed_ver, '.'));
			if (version_compare($major_new, $major_old, '>')) {
				set_transient('_simple-calendar_activation_redirect', 'update', 60);
			} elseif ($major_old == $major_new) {
				$version = explode('.', $this->new_ver);
				end($version);
				if (0 === intval(current($version))) {
					set_transient('_simple-calendar_activation_redirect', 'update', 60);
				}
			}
		}

		// Pro: after an upgrade, land on "own credentials" when Via SC is not verified but OAuth client creds exist.
		if (!is_null($this->installed_ver)) {
			$this->maybe_set_pro_own_credentials_ui_after_update();
			$this->maybe_backdate_pro_setup_completed_at_after_update();
			$this->maybe_backdate_core_setup_completed_at_after_update();
		}

		$this->admin_redirects();

		update_option('simple-calendar_version', $this->new_ver);
	}

	/**
	 * Handle redirects to welcome page after install and updates.
	 *
	 * Transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 *
	 * @since 3.0.0
	 */
	public function admin_redirects()
	{
		$transient = get_transient('_simple-calendar_activation_redirect');

		if (!$transient || is_network_admin() || isset($_GET['activate-multi']) || !current_user_can('manage_options')) {
			return;
		}

		delete_transient('_simple-calendar_activation_redirect');

		// Do not redirect if already on welcome page screen.
		if (!empty($_GET['page']) && in_array($_GET['page'], ['simple-calendar_connect'], true)) {
			return;
		}

		// Use Dashboard-based URL to avoid CPT permission edge-cases.
		$connect_base_url = admin_url('edit.php?post_type=calendar&page=simple-calendar_connect');

		$url = esc_url(add_query_arg('simcal_install', esc_attr($transient), $connect_base_url));
		wp_safe_redirect($url);
		exit();
	}

	/**
	 * Get posts.
	 *
	 * @since  3.0.0
	 *
	 * @param  $post_type
	 *
	 * @return array
	 */
	private function get_posts($post_type)
	{
		$posts = [];

		if (!empty($post_type)) {
			// https://core.trac.wordpress.org/ticket/18408
			$posts = get_posts([
				'post_type' => $post_type,
				'post_status' => ['draft', 'future', 'publish', 'pending', 'private', 'trash'],
				'nopaging' => true,
			]);

			wp_reset_postdata();
		}

		return $posts;
	}

	/**
	 * Update.
	 *
	 * Runs an update script for the specified version passed in argument.
	 *
	 * @since 3.0.0
	 *
	 * @param string $version
	 */
	private function update($version)
	{
		$update_v = '\\' . __NAMESPACE__ . '\Updates\\Update_V' . str_replace('.', '', $version);

		if (class_exists($update_v)) {
			new $update_v($this->posts);
		}
	}

	/**
	 * After a plugin upgrade, default Pro Connect to "own credentials" when appropriate.
	 *
	 * If Google Calendar Pro is active, OAuth via Simple Calendar is not verified, and the
	 * site already has a Client ID + Secret saved, set connection type to `own` so the
	 * credentials step opens the own-credentials UI (same as visiting with sc_pro_own=1).
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function maybe_set_pro_own_credentials_ui_after_update()
	{
		if (!$this->is_google_calendar_pro_active_for_update()) {
			return;
		}

		if ('1' === (string) get_option('simple_calendar_connect_pro_oauth_health_ok', '')) {
			return;
		}

		$feeds = get_option('simple-calendar_settings_feeds', []);
		$google_pro = isset($feeds['google-pro']) && is_array($feeds['google-pro']) ? $feeds['google-pro'] : [];
		$client_id = isset($google_pro['client_id']) ? trim((string) $google_pro['client_id']) : '';
		$client_secret = isset($google_pro['client_secret']) ? trim((string) $google_pro['client_secret']) : '';

		if ('' === $client_id || '' === $client_secret) {
			return;
		}

		update_option('simple_calendar_connect_pro_connection_type', 'own', false);
	}

	/**
	 * After a plugin upgrade, mark Pro setup as already complete when appropriate.
	 *
	 * Used for existing installs that were fully set up pre-Connect UI. If a published Pro
	 * calendar feed already exists but the new "setup completed" timestamp was never set,
	 * backdate it so post-setup UI (rating/pro sidebar) shows immediately after update.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function maybe_backdate_pro_setup_completed_at_after_update()
	{
		if (!$this->is_google_calendar_pro_active_for_update()) {
			return;
		}

		$existing = (int) get_option('simple-calendar_connect_pro_setup_completed_at', 0);
		if ($existing > 0) {
			return;
		}

		// Mirror Connect controller logic: "complete" means a published Pro calendar exists.
		$has_published_pro_calendar = false;

		$pro_query_base = [
			'post_type' => 'calendar',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'fields' => 'ids',
		];

		if (taxonomy_exists('calendar_feed')) {
			$pro_calendar_query = new \WP_Query(
				array_merge($pro_query_base, [
					'tax_query' => [
						[
							'taxonomy' => 'calendar_feed',
							'field' => 'slug',
							'terms' => ['google-pro', 'google_pro'],
						],
					],
				])
			);
			$has_published_pro_calendar = $pro_calendar_query->have_posts();
			wp_reset_postdata();
		}

		if (!$has_published_pro_calendar) {
			$pro_calendar_query = new \WP_Query(
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
				])
			);
			$has_published_pro_calendar = $pro_calendar_query->have_posts();
			wp_reset_postdata();
		}

		if (!$has_published_pro_calendar) {
			return;
		}

		$day = defined('DAY_IN_SECONDS') ? (int) DAY_IN_SECONDS : 86400;
		$backdated = time() - 2 * $day;
		update_option('simple-calendar_connect_pro_setup_completed_at', $backdated, false);
	}

	/**
	 * After a plugin upgrade, mark Core setup as already complete when appropriate.
	 *
	 * Used for existing installs that were fully set up pre-Connect UI. If a verified API key
	 * already exists and at least one calendar is published but the new "setup completed"
	 * timestamp was never set, backdate it so post-setup UI (rating card) shows immediately
	 * after update.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function maybe_backdate_core_setup_completed_at_after_update()
	{
		// Only relevant for Core flow (when Pro isn't active).
		if ($this->is_google_calendar_pro_active_for_update()) {
			return;
		}

		$existing = (int) get_option('simple-calendar_connect_setup_completed_at', 0);
		if ($existing > 0) {
			return;
		}

		$feeds_options = get_option('simple-calendar_settings_feeds', []);
		$api_key = '';
		if (is_array($feeds_options) && isset($feeds_options['google']) && is_array($feeds_options['google'])) {
			$api_key = (string) ($feeds_options['google']['api_key'] ?? '');
		}
		$api_key = trim($api_key);
		if ('' === $api_key) {
			return;
		}

		$has_core_api_key_verified = function_exists('simcal_is_connect_google_api_key_verified')
			? (bool) simcal_is_connect_google_api_key_verified((string) $api_key)
			: false;
		if (!$has_core_api_key_verified) {
			return;
		}

		$calendar_query = new \WP_Query([
			'post_type' => 'calendar',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'fields' => 'ids',
		]);
		$has_published_calendar = $calendar_query->have_posts();
		wp_reset_postdata();

		if (!$has_published_calendar) {
			return;
		}

		$day = defined('DAY_IN_SECONDS') ? (int) DAY_IN_SECONDS : 86400;
		$backdated = time() - 2 * $day;
		update_option('simple-calendar_connect_setup_completed_at', $backdated);
	}

	/**
	 * Whether Google Calendar Pro appears active (lightweight check for upgrade routine).
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	private function is_google_calendar_pro_active_for_update()
	{
		$detected = false;

		if (defined('SIMPLE_CALENDAR_GOOGLE_PRO_VERSION') && SIMPLE_CALENDAR_GOOGLE_PRO_VERSION) {
			$detected = true;
		} elseif (class_exists('Google_Pro')) {
			$detected = true;
		} else {
			if (!function_exists('is_plugin_active')) {
				$plugin_file = trailingslashit((string) ABSPATH) . 'wp-admin/includes/plugin.php';
				if (is_readable($plugin_file)) {
					// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
					require_once $plugin_file;
				}
			}

			if (function_exists('is_plugin_active')) {
				$paths = ['simple-calendar-google-calendar-pro/simple-calendar-google-calendar-pro.php'];
				foreach ($paths as $path) {
					if (is_plugin_active($path)) {
						$detected = true;
						break;
					}
				}
			}

			if (!$detected && function_exists('is_multisite') && is_multisite()) {
				$sitewide = (array) get_site_option('active_sitewide_plugins', []);
				foreach (array_keys($sitewide) as $basename) {
					$p = strtolower((string) $basename);
					if (
						strpos($p, 'google-calendar-pro') !== false ||
						strpos($p, 'simple-calendar-google-calendar-pro') !== false
					) {
						$detected = true;
						break;
					}
				}
			}

			if (!$detected) {
				$active = (array) get_option('active_plugins', []);
				foreach ($active as $plugin) {
					$p = strtolower((string) $plugin);
					if (
						strpos($p, 'google-calendar-pro') !== false ||
						strpos($p, 'simple-calendar-google-calendar-pro') !== false
					) {
						$detected = true;
						break;
					}
				}
			}
		}

		return (bool) apply_filters('simcal_is_google_calendar_pro_active', $detected);
	}
}
