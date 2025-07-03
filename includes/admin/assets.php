<?php
/**
 * Admin Assets
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Admin scripts and styles.
 *
 * Handles the plugin scripts and styles for back end dashboard pages.
 *
 * @since 3.0.0
 */
class Assets
{
	/**
	 * Check for SC setting page.
	 *
	 * @since 3.4.1
	 */

	protected $current_page = '';

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		add_action('admin_enqueue_scripts', [$this, 'load']);

		$this->current_page = sanitize_text_field(wp_unslash($_GET['page'] ?? ''));
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 3.0.0
	 */
	public function load()
	{
		$css_path = SIMPLE_CALENDAR_ASSETS . 'generated/';
		$css_path_vendor = $css_path . 'vendor/';
		$js_path = SIMPLE_CALENDAR_ASSETS . 'generated/';
		$js_path_vendor = $js_path . 'vendor/';
		$sc_screen = get_current_screen();

		/* ====================== *
		 * Register Admin Scripts *
		 * ====================== */

		// TipTip uses ".minified.js" filename ending.
		wp_register_script(
			'simcal-tiptip',
			$js_path_vendor . 'jquery.tipTip.minified.js',
			['jquery'],
			SIMPLE_CALENDAR_VERSION,
			true
		);
		wp_register_script('simcal-select2', $js_path_vendor . 'select2.min.js', [], SIMPLE_CALENDAR_VERSION, true);
		wp_register_script(
			'simcal-admin',
			$js_path . 'admin.min.js',
			['jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker', 'wp-color-picker', 'simcal-tiptip', 'simcal-select2'],
			SIMPLE_CALENDAR_VERSION,
			true
		);
		wp_register_script(
			'simcal-admin-add-calendar',
			$js_path . 'admin-add-calendar.min.js',
			['simcal-select2'],
			SIMPLE_CALENDAR_VERSION,
			true
		);
		wp_register_script(
			'simcal-oauth-helper-admin',
			$js_path . 'oauth-helper-admin.min.js',
			['jquery'],
			SIMPLE_CALENDAR_VERSION,
			true
		);

		if (class_exists('SimpleCalendar\Feeds\Google_Pro') && $this->current_page === 'simple-calendar_settings') {
			wp_enqueue_script('simcal-oauth-helper-admin');
			wp_localize_script('simcal-oauth-helper-admin', 'oauth_admin', simcal_common_scripts_variables());
		}
		/* ===================== *
		 * Register Admin Styles *
		 * ===================== */

		wp_register_style('simcal-select2', $css_path_vendor . 'select2.min.css', [], SIMPLE_CALENDAR_VERSION);
		wp_register_style(
			'simcal-admin',
			$css_path . 'admin.min.css',
			['wp-color-picker', 'simcal-select2'],
			SIMPLE_CALENDAR_VERSION
		);
		wp_register_style(
			'simcal-admin-add-calendar',
			$css_path . 'admin-add-calendar.min.css',
			['simcal-select2'],
			SIMPLE_CALENDAR_VERSION
		);

		if (simcal_is_admin_screen() !== false) {
			wp_enqueue_script('simcal-admin');
			wp_localize_script('simcal-admin', 'simcal_admin', simcal_common_scripts_variables());

			wp_enqueue_style('simcal-admin');
		} else {
			global $post_type;
			$screen = get_current_screen();

			$post_types = [];
			$settings = get_option('simple-calendar_settings_calendars');
			if (isset($settings['general']['attach_calendars_posts'])) {
				$post_types = $settings['general']['attach_calendars_posts'];
			}

			$conditions = [in_array($post_type, (array) $post_types), $screen->id == 'widgets'];

			if (in_array(true, $conditions)) {
				wp_enqueue_script('simcal-admin-add-calendar');
				wp_localize_script('simcal-admin-add-calendar', 'simcal_admin', [
					'locale' => get_locale(),
					'text_dir' => is_rtl() ? 'rtl' : 'ltr',
				]);

				wp_enqueue_style('simcal-admin-add-calendar');
			}
		}

		// Load the style on where that needed.
		if ('calendar_page_simple-calendar_settings' == $sc_screen->id) {
			wp_enqueue_style('sc-admin-style', $css_path . 'admin-sett-style.min.css', [], SIMPLE_CALENDAR_VERSION);
			wp_enqueue_style('sc-tail-style', $css_path . 'tailwind.min.css', [], SIMPLE_CALENDAR_VERSION);
		}
		if (
			'dashboard_page_simple-calendar_about' == $sc_screen->id ||
			'dashboard_page_simple-calendar_credits' == $sc_screen->id ||
			'dashboard_page_simple-calendar_translators' == $sc_screen->id
		) {
			wp_enqueue_style('sc-welcome-style', $css_path . 'sc-welcome-pg-style.min.css', [], SIMPLE_CALENDAR_VERSION);
			wp_enqueue_style('sc-tail-style', $css_path . 'tailwind.min.css', [], SIMPLE_CALENDAR_VERSION);
		}

		if ($sc_screen->id == 'calendar') {
			wp_enqueue_style('sc-setting-style', $css_path . 'admin-post-settings.min.css', [], SIMPLE_CALENDAR_VERSION);
		}

		if (class_exists('SimpleCalendar\Feeds\Google_Pro') && $this->current_page === 'simple-calendar_settings') {
			wp_enqueue_style('sc-oauth-helper-style', $css_path . 'oauth-helper-admin.min.css', [], SIMPLE_CALENDAR_VERSION);
		}
	}
}
