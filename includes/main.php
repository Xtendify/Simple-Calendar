<?php
/**
 * Main Class
 *
 * @package SimpleCalendar
 */
namespace SimpleCalendar;

use SimpleCalendar\Admin\License_Manager;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Simple Calendar plugin.
 */
final class Plugin
{
	/**
	 * Plugin name.
	 *
	 * @access public
	 * @var string
	 */
	public static $name = 'Simple Calendar';

	/**
	 * Plugin version.
	 *
	 * @access public
	 * @var string
	 */
	public static $version = SIMPLE_CALENDAR_VERSION;

	/**
	 * Plugin homepage.
	 *
	 * @access public
	 * @var string
	 */
	protected static $homepage = 'https://simplecalendar.io';

	/**
	 * Locale.
	 *
	 * @access public
	 * @var string
	 */
	public $locale = 'en_US';

	/**
	 * Objects factory.
	 *
	 * @access public
	 * @var Objects
	 */
	public $objects = null;

	/**
	 * The single instance of this class.
	 *
	 * @access protected
	 * @var Plugin
	 */
	protected static $_instance = null;

	/**
	 * Get the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone()
	{
		_doing_it_wrong(__FUNCTION__, 'Cloning the main instance of this plugin is forbidden.', '1.0.0');
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup()
	{
		_doing_it_wrong(__FUNCTION__, 'Unserializing instances of this plugin is forbidden.', '1.0.0');
	}

	/**
	 * Plugin constructor.
	 *
	 * @final
	 */
	public function __construct()
	{
		// Load plugin.
		require_once 'autoload.php';
		$this->locale = apply_filters('plugin_locale', get_locale(), 'google-calendar-events');
		$this->load();

		// Installation hooks.
		register_activation_hook(SIMPLE_CALENDAR_MAIN_FILE, ['SimpleCalendar\Installation', 'activate']);
		register_deactivation_hook(SIMPLE_CALENDAR_MAIN_FILE, ['SimpleCalendar\Installation', 'deactivate']);

		// Do update call here.
		add_action('admin_init', [$this, 'update'], 999);

		// Redirect to Connect page after activation (core or supported add-on).
		// Only hook when needed.
		if (
			is_admin() &&
			(get_option('simple-calendar_redirect_to_connect') || get_option('simple_calendar_pro_redirect_to_connect'))
		) {
			add_action('admin_init', [$this, 'maybe_redirect_to_connect'], 1);
		}

		// Redirect to Connect page after supported add-on activation.
		if (is_admin()) {
			add_action('activated_plugin', [$this, 'maybe_flag_connect_redirect_after_addon_activation'], 10, 2);
		}

		// Init hooks.
		add_action('init', [$this, 'init'], 5);
		add_action('admin_init', [$this, 'register_settings'], 5);

		//Oauth Helper init
		add_action('init', [$this, 'oauth_helper_init'], 5);

		// Upon plugin loaded action hook.
		do_action('simcal_loaded');
	}

	/**
	 * Load plugin.
	 *
	 * @since 3.0.0
	 */
	public function load()
	{
		// Functions shared in both back end and front end.
		include_once 'functions/shared.php';

		// Init custom post types and taxonomies.
		new Post_Types();

		// Load back end.
		if (is_admin()) {
			$this->load_admin();
		} else {
			// Load front end scripts and styles.
			new Assets();
		}

		// Front facing ajax callbacks.
		new Ajax();

		// Add Shortcodes.
		new Shortcodes();

		// Add Widgets.
		new Widgets();

		// Deprecated functions for backwards compatibility.
		include_once 'functions/deprecated.php';
	}

	/**
	 * Load plugin admin.
	 *
	 * @since 3.0.0
	 */
	public function load_admin()
	{
		// Back end only svg functions.
		include_once 'functions/admin-svg.php';

		// Back end only functions.
		include_once 'functions/admin.php';

		// Display admin notices.
		new Admin\Notices();

		// Load back end scripts and styles.
		new Admin\Assets();

		// Custom content handling.
		new Admin\Post_Types();

		// Init menus and settings.
		new Admin\Menus();

		if (defined('DOING_AJAX')) {
			// Admin ajax callbacks.
			new Admin\Ajax();
		}
	}

	/**
	 * Init plugin when WordPress initializes.
	 *
	 * @since 3.0.0
	 */
	public function init()
	{
		// Before init action hook.
		do_action('before_simcal_init');

		// Set up localization.
		add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);

		// Init objects factory.
		$this->objects = new Objects();

		// Upon init action hook.
		do_action('simcal_init');
	}

	/**
	 * Loads the plugin textdomain for translation.
	 *
	 * @since 3.1.3
	 */
	public function load_plugin_textdomain()
	{
		load_plugin_textdomain(
			'google-calendar-events',
			false,
			dirname(plugin_basename(SIMPLE_CALENDAR_MAIN_FILE)) . '/i18n/'
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 3.0.0
	 */
	public function register_settings()
	{
		if (
			(!empty($_POST) && is_admin() && !defined('DOING_AJAX')) ||
			(isset($_GET['page']) && 'simple-calendar_settings' === $_GET['page'])
		) {
			$settings = new Admin\Pages();
			$settings->register_settings($settings->get_settings());
		}
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 3.4.1
	 */
	public function oauth_helper_init()
	{
		if (defined('SIMPLE_CALENDAR_GOOGLE_PRO_VERSION') || defined('SIMPLE_CALENDAR_APPOINTMENT_VERSION')) {
			if (defined('SC_OAUTH_HELPER_VERSION')) {
				add_action('admin_notices', function () {
					echo '<div class="error"><p>' .
						sprintf(
							__(
								'The Simple Calendar plugin now includes the features previously provided by the Google Calendar OAuth Helper add-on. Please deactivate and remove the OAuth Helper add-on to avoid redundancy.',
								'google-calendar-events'
							),
							simcal_ga_campaign_url(simcal_get_url('addons'), 'core-plugin', 'admin-notice')
						) .
						'</p></div>';
				});
			} else {
				require plugin_dir_path(__FILE__) . 'oauthhelper/oauth-service-actions.php';
				require plugin_dir_path(__FILE__) . 'oauthhelper/class-oauth-service.php';
			}
		}
	}
	/**
	 * Get Ajax URL.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	public function ajax_url()
	{
		return admin_url('admin-ajax.php', 'relative');
	}

	/**
	 * Get URL.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $case Requested url.
	 *
	 * @return string
	 */
	public function get_url($case)
	{
		switch ($case) {
			case 'codex':
			case 'apidocs':
				return 'http://codex.simplecalendar.io';
			case 'addons':
				return self::$homepage . '/addons/';
			case 'gcal-pro':
				return self::$homepage . '/addons/google-calendar-pro/';
			case 'fullcal':
				return self::$homepage . '/addons/full-calendar/';
			case 'docs':
				return 'http://docs.simplecalendar.io';
			case 'github':
				return 'https://github.com/Xtendify/Simple-Calendar';
			case 'support':
				return 'https://wordpress.org/support/plugin/google-calendar-events';
			case 'gdev-console':
				return 'https://console.developers.google.com';
			case 'home':
			default:
				return self::$homepage;
		}
	}

	/**
	 * Run upgrade scripts.
	 *
	 * @since 3.0.0
	 */
	public static function update()
	{
		$update = new Update(SIMPLE_CALENDAR_VERSION);
	}

	/**
	 * Redirect to the Connect page on first admin load after activation.
	 *
	 * @since 3.6.3
	 *
	 * @return void
	 */
	public function maybe_redirect_to_connect()
	{
		// Only run in admin and for users who can manage options.
		if (!is_admin() || !current_user_can('manage_options')) {
			return;
		}

		// Do not redirect during AJAX.
		if (defined('DOING_AJAX') && DOING_AJAX) {
			return;
		}

		$core_flag = (bool) get_option('simple-calendar_redirect_to_connect');
		$addon_flag = 1 === (int) get_option('simple_calendar_pro_redirect_to_connect', 0);

		// Do not redirect if no redirect flag is set.
		if (!$core_flag && !$addon_flag) {
			return;
		}

		// Avoid redirect on bulk activation.
		if (isset($_GET['activate-multi'])) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			delete_option('simple-calendar_redirect_to_connect');
			delete_option('simple_calendar_pro_redirect_to_connect');
			return;
		}

		// Clear the flag so we only redirect once.
		delete_option('simple-calendar_redirect_to_connect');
		delete_option('simple_calendar_pro_redirect_to_connect');

		$redirect_url = admin_url(
			$addon_flag
				? 'edit.php?post_type=calendar&page=simple-calendar_connect&sc_welcome=1'
				: 'edit.php?post_type=calendar&page=simple-calendar_connect'
		);

		wp_safe_redirect($redirect_url);
		exit();
	}

	/**
	 * When a supported add-on is activated, flag a one-time redirect
	 * to the Connect page and show the Welcome step for that add-on.
	 *
	 * @since 3.6.3
	 *
	 * @param string $plugin       Plugin basename just activated.
	 * @param bool   $network_wide Whether activated network-wide.
	 *
	 * @return void
	 */
	public function maybe_flag_connect_redirect_after_addon_activation($plugin, $network_wide)
	{
		if (!is_admin() || !current_user_can('manage_options')) {
			return;
		}

		$plugin = (string) $plugin;

		$simcal_addon_list = [
			// Google Calendar Pro (common plugin basenames).
			'simple-calendar-google-calendar-pro/simple-calendar-google-calendar-pro.php',
		];
		$simcal_addons = apply_filters('simcal_connect_redirect_addon_plugins', $simcal_addon_list);
		$simcal_addons = array_values(array_unique(array_filter(array_map('strval', (array) $simcal_addons))));

		$is_simcal_addon = in_array($plugin, $simcal_addons, true);

		if ($is_simcal_addon) {
			update_option('simple_calendar_pro_redirect_to_connect', 1, false);
			update_option('simple_calendar_connect_welcome_context', 'pro', false);
		}
	}

}

/**
 * Simple Calendar.
 *
 * @return Plugin
 */
function plugin()
{
	return Plugin::get_instance();
}

plugin();
