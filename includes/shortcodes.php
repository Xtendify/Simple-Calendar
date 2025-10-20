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
	 * Flag to track if assets have been loaded for shortcodes.
	 *
	 * @since 3.5.6
	 * @var bool
	 */
	private static $assets_loaded = false;

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		// Add shortcodes.
		add_action('init', [$this, 'register']);
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
		$args = shortcode_atts(
			[
				'id' => null,
			],
			$attributes
		);

		$id = absint($args['id']);

		if ($id > 0) {
			// Ensure assets are loaded when shortcode is rendered
			$this->ensure_assets_loaded();

			$calendar = simcal_get_calendar($id);

			if ($calendar instanceof Calendar) {
				ob_start();
				$calendar->html();
				return ob_get_clean();
			}
		}

		return '';
	}

	/**
	 * Ensure assets are loaded when shortcode is rendered.
	 * This handles cases where shortcodes are used in page builders like Avada.
	 *
	 * @since 3.5.6
	 */
	private function ensure_assets_loaded()
	{
		// Prevent duplicate loading
		if (self::$assets_loaded) {
			return;
		}

		// Check if assets are already being loaded
		if (!did_action('wp_enqueue_scripts')) {
			// Assets haven't been enqueued yet, so we need to trigger them
			add_action('wp_enqueue_scripts', [$this, 'load_shortcode_assets'], 5);
		} else {
			// Assets have already been enqueued, but we need to ensure they're loaded
			$this->load_shortcode_assets();
		}
	}

	/**
	 * Load assets for shortcode usage.
	 *
	 * @since 3.5.6
	 */
	public function load_shortcode_assets()
	{
		// Prevent duplicate loading
		if (self::$assets_loaded) {
			return;
		}

		// Get the Assets instance and load the assets
		$assets = new \SimpleCalendar\Assets();
		$assets->load();

		// Mark as loaded
		self::$assets_loaded = true;

		// Trigger action for other plugins/themes to hook into
		do_action('simcal_shortcode_assets_loaded');
	}
}
