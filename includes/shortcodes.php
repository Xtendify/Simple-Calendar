<?php
/**
 * Shortcodes
 *
 * @package SimpleCalendar
 */
namespace SimpleCalendar;

use SimpleCalendar\Abstracts\Calendar;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Shortcodes.
 *
 * Register and handle custom shortcodes.
 *
 * @since 3.0.0
 */
class Shortcodes
{
	/**
	 * Flag to track if shortcode assets need to be loaded.
	 *
	 * @var bool
	 */
	private $shortcode_used = false;

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		// Add shortcodes.
		add_action('init', [$this, 'register']);
		
		// Hook to ensure assets are loaded when shortcode is used
		add_action('wp_enqueue_scripts', [$this, 'maybe_load_shortcode_assets'], 5);
	}

	/**
	 * Maybe load shortcode assets if shortcode was used.
	 *
	 * @since 3.0.0
	 */
	public function maybe_load_shortcode_assets()
	{
		// Check if we're in the admin area
		if (is_admin()) {
			return;
		}

		// Only load if shortcode was used
		if (!$this->shortcode_used) {
			return;
		}

		// Load default assets for shortcode usage
		$this->load_default_assets();
	}

	/**
	 * Register shortcodes.
	 *
	 * @since 3.0.0
	 */
	public function register()
	{
		// `calendar` shortcode is conflict with other plugin so added new one.
		add_shortcode('simple_calendar', [$this, 'print_calendar']);
		add_shortcode('calendar', [$this, 'print_calendar']);
		// @deprecated legacy shortcode
		add_shortcode('gcal', [$this, 'print_calendar']);

		do_action('simcal_add_shortcodes');
	}

	/**
	 * Print a calendar.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $attributes
	 *
	 * @return string
	 */
	public function print_calendar($attributes)
	{
		// Set flag that shortcode is being used
		$this->shortcode_used = true;

		// Load required files if not already loaded
		if (!function_exists('simcal_get_calendar_view')) {
			require_once SIMPLE_CALENDAR_INC . 'functions/shared.php';
		}

		$args = shortcode_atts(
			[
				'id' => null,
			],
			$attributes
		);

		$id = absint($args['id']);

		if ($id > 0) {
			$calendar = simcal_get_calendar($id);

			if ($calendar instanceof Calendar) {
				// Load assets for this specific calendar when shortcode is used
				$this->load_shortcode_assets($calendar);

				ob_start();
				$calendar->html();
				return ob_get_clean();
			}
		}

		return '';
	}

	/**
	 * Load assets for shortcode usage.
	 *
	 * @since 3.5.7
	 *
	 * @param Calendar $calendar
	 */
	private function load_shortcode_assets($calendar)
	{
		// Check if we're in the admin area
		if (is_admin()) {
			return;
		}

		// Get the calendar's view
		$view = $calendar->get_view();
		
		if ($view) {
			// Load assets from the specific calendar view
			$this->load_assets_from_view($view);
		}
	}

	/**
	 * Load default assets for shortcode usage.
	 *
	 * @since 3.0.0
	 */
	private function load_default_assets()
	{
		// Make sure the required files are loaded
		if (!function_exists('simcal_get_calendar_view')) {
			require_once SIMPLE_CALENDAR_INC . 'functions/shared.php';
		}
		if (!class_exists('SimpleCalendar\\Assets')) {
			require_once SIMPLE_CALENDAR_INC . 'assets.php';
		}

		// Get the default calendar view for grid (most common)
		$view = simcal_get_calendar_view(0, 'default-calendar-grid');
		
		if ($view) {
			$this->load_assets_from_view($view);
		}
	}

	/**
	 * Load assets from a calendar view.
	 *
	 * @since 3.0.0
	 *
	 * @param Calendar_View $view
	 */
	private function load_assets_from_view($view)
	{
		$scripts = $view->scripts();
		$styles = $view->styles();
		
		foreach ($scripts as $handle => $script) {
			// Check if script is already registered to avoid conflicts
			if (!wp_script_is($handle, 'registered')) {
				wp_register_script(
					$handle,
					$script['src'],
					isset($script['deps']) ? $script['deps'] : [],
					SIMPLE_CALENDAR_VERSION,
					isset($script['in_footer']) ? $script['in_footer'] : false
				);
			}
			
			// Localize the script if needed
			if (isset($script['localize'])) {
				foreach ($script['localize'] as $object_name => $data) {
					wp_localize_script($handle, $object_name, $data);
				}
			}
			
			wp_enqueue_script($handle);
		}
		
		// Register and enqueue styles
		foreach ($styles as $handle => $style) {
			// Check if style is already registered to avoid conflicts
			if (!wp_style_is($handle, 'registered')) {
				wp_register_style(
					$handle,
					$style['src'],
					isset($style['deps']) ? $style['deps'] : [],
					SIMPLE_CALENDAR_VERSION,
					isset($style['media']) ? $style['media'] : 'all'
				);
			}
			wp_enqueue_style($handle);
		}

		if (wp_script_is('simcal-qtip', 'enqueued')) {
			wp_enqueue_script(
				'simplecalendar-imagesloaded',
				SIMPLE_CALENDAR_ASSETS . 'generated/vendor/imagesloaded.pkgd.min.js',
				['simcal-qtip'],
				SIMPLE_CALENDAR_VERSION,
				true
			);
		}
		
		do_action('simcal_enqueue_assets');
	}
}
