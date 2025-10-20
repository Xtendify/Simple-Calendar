<?php
/**
 * Front End Assets
 *
 * @package SimpleCalendar;
 */
namespace SimpleCalendar;

use SimpleCalendar\Abstracts\Calendar_View;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Front end scripts and styles.
 *
 * Loads scripts and styles based on the requested calendar view.
 *
 * @since 3.0.0
 */
class Assets
{
	/**
	 * Scripts.
	 *
	 * @access private
	 * @var array
	 */
	private $scripts = [];

	/**
	 * Styles.
	 *
	 * @access private
	 * @var array
	 */
	private $styles = [];

	/**
	 * Disable styles.
	 *
	 * @access public
	 * @var bool
	 */
	public $disable_styles = false;

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		$settings = get_option('simple-calendar_settings_advanced');

		if (isset($settings['assets']['disable_css'])) {
			$this->disable_styles = 'yes' == $settings['assets']['disable_css'] ? true : false;
		}

		add_action('init', [$this, 'register'], 20);
		add_action('init', [$this, 'enqueue'], 40);
	}

	/**
	 * Register scripts and styles.
	 *
	 * @since 3.0.0
	 */
	public function register()
	{
		do_action('simcal_register_assets');
	}

	/**
	 * Enqueue scripts and styles.
	 * Add action to check post type and shortcode when post is available
	 * @since 3.0.0
	 */
	public function enqueue()
	{
		add_action('wp', [$this, 'check_load_assets']);
	}

	/**
	 * Check if we should load assets.
	 *
	 * @since 3.5.6
	 */
	public function check_load_assets()
	{
		// Only load assets if it's a calendar post type or if we detect calendar shortcode
		$load_assets = false;

		if (is_singular('calendar')) {
			$load_assets = true;
		}

		if (is_singular() && !$load_assets) {
			global $post;
			if (isset($post->post_content) && has_shortcode($post->post_content, 'calendar')) {
				$load_assets = true;
			}

			// Check for shortcodes in Avada builder content and other meta fields
			if (!$load_assets && isset($post->ID)) {
				$load_assets = $this->check_builder_content($post->ID);
			}
		}

		if ($load_assets) {
			add_action('wp_enqueue_scripts', [$this, 'load'], 10);

			do_action('simcal_enqueue_assets');

			// Improves compatibility with themes and plugins using Isotope and Masonry.
			add_action(
				'wp_enqueue_scripts',
				function () {
					if (wp_script_is('simcal-qtip', 'enqueued')) {
						wp_enqueue_script(
							'simplecalendar-imagesloaded',
							SIMPLE_CALENDAR_ASSETS . 'generated/vendor/imagesloaded.pkgd.min.js',
							['simcal-qtip'],
							SIMPLE_CALENDAR_VERSION,
							true
						);
					}
				},
				1000
			);
		}
	}

	/**
	 * Check for shortcodes in Avada builder content and other meta fields.
	 *
	 * @since 3.5.6
	 *
	 * @param int $post_id Post ID to check
	 * @return bool True if shortcode found, false otherwise
	 */
	private function check_builder_content($post_id)
	{
		// Check Avada builder content
		$avada_content = get_post_meta($post_id, '_fusion_builder_content', true);
		if (!empty($avada_content) && has_shortcode($avada_content, 'calendar')) {
			return true;
		}

		// Check for other common page builder meta fields
		$builder_fields = [
			'_elementor_data', // Elementor
			'_wpb_shortcodes_content', // Visual Composer
			'_et_pb_old_content', // Divi Builder
			'panels_data', // Page Builder by SiteOrigin
			'_beaver_builder_data', // Beaver Builder
			'_fl_builder_data', // Beaver Builder Lite
		];

		foreach ($builder_fields as $field) {
			$content = get_post_meta($post_id, $field, true);
			if (!empty($content)) {
				// For JSON data, decode and search
				if (is_string($content) && (strpos($content, '{') === 0 || strpos($content, '[') === 0)) {
					$decoded = json_decode($content, true);
					if (is_array($decoded) && $this->search_shortcode_in_array($decoded)) {
						return true;
					}
				}
				// For regular content, use has_shortcode
				elseif (has_shortcode($content, 'calendar')) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Recursively search for shortcode in array data.
	 *
	 * @since 3.5.6
	 *
	 * @param array $data Array to search
	 * @return bool True if shortcode found, false otherwise
	 */
	private function search_shortcode_in_array($data)
	{
		foreach ($data as $key => $value) {
			if (is_string($value) && has_shortcode($value, 'calendar')) {
				return true;
			} elseif (is_array($value) && $this->search_shortcode_in_array($value)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Load scripts and styles.
	 *
	 * @since 3.0.0
	 */
	public function load()
	{
		$types = simcal_get_calendar_types();

		foreach ($types as $calendar => $views) {
			foreach ($views as $key => $view) {
				$view = simcal_get_calendar_view(0, $calendar . '-' . $view);

				$scripts[] = $view->scripts();
				$styles[] = $view->styles();
			}
		}

		// Prevent duplicate localization variables for default calendar.
		if (isset($scripts[1]['simcal-default-calendar']['localize'])) {
			unset($scripts[1]['simcal-default-calendar']['localize']);
		}

		$this->get_widgets_assets();
		$this->scripts = apply_filters('simcal_front_end_scripts', $scripts);
		// First check if there is a multi-dimensional array of scripts
		if (isset($this->scripts[0])) {
			foreach ($this->scripts as $script) {
				$this->load_scripts($script);
			}
		} else {
			$this->load_scripts($this->scripts);
		}
		$this->styles = apply_filters('simcal_front_end_styles', $styles);
		// First check if there is a multi-dimensional array of styles
		if (isset($this->styles[0])) {
			foreach ($this->styles as $style) {
				$this->load_styles($style);
			}
		} else {
			$this->load_styles($this->styles);
		}
	}

	/**
	 * Get widgets assets.
	 *
	 * @since 3.0.0
	 */
	public function get_widgets_assets()
	{
		$widgets = get_option('widget_gce_widget');

		if (!empty($widgets) && is_array($widgets)) {
			foreach ($widgets as $settings) {
				if (!empty($settings) && is_array($settings)) {
					if (isset($settings['calendar_id'])) {
						$view = simcal_get_calendar_view(absint($settings['calendar_id']));

						if ($view instanceof Calendar_View) {
							add_filter(
								'simcal_front_end_scripts',
								function ($scripts) use ($view) {
									if (is_array($scripts)) {
										return array_merge($scripts, $view->scripts());
									} else {
										return $view->scripts();
									}
								},
								100,
								2
							);
							add_filter(
								'simcal_front_end_styles',
								function ($styles) use ($view) {
									if (is_array($styles)) {
										return array_merge($styles, $view->styles());
									} else {
										return $view->styles();
									}
								},
								100,
								2
							);
						}
					}
				}
			}
		}
	}

	/**
	 * Scripts.
	 *
	 * @since 3.0.0
	 *
	 * @param array $scripts
	 */
	public function load_scripts($scripts)
	{
		// Only load if not disabled in the settings
		if (!empty($scripts) && is_array($scripts)) {
			foreach ($scripts as $script => $v) {
				/** Plugin compatibility fixes */

				// Dequeue moment.js if detected from WP Simple Pay Pro.
				if (wp_script_is('stripe-checkout-pro-moment', 'enqueued') && $script == 'simcal-fullcal-moment') {
					continue;
				}

				if (!empty($v['src'])) {
					// Enqueued individually so we can dequeue if already enqueued by another plugin.
					// TODO Rework dependencies part (or remove completely).

					$src = esc_url($v['src']);
					$in_footer = isset($v['in_footer']) ? $v['in_footer'] : false;
					$deps = isset($v['deps']) ? $v['deps'] : [];

					wp_enqueue_script($script, $src, $deps, SIMPLE_CALENDAR_VERSION, $in_footer);

					if (!empty($v['localize']) && is_array($v['localize'])) {
						foreach ($v['localize'] as $object => $l10n) {
							wp_localize_script($script, $object, $l10n);
						}
					}
				} elseif (is_string($v) && !empty($v)) {
					wp_enqueue_script($v);
				}
			}
		}
	}

	/**
	 * Styles.
	 *
	 * @since 3.0.0
	 *
	 * @param array $styles
	 */
	public function load_styles($styles)
	{
		// Only load if not disabled in the settings
		if (!empty($styles) && is_array($styles) && false === $this->disable_styles) {
			foreach ($styles as $style => $v) {
				if (!empty($v['src'])) {
					// Enqueued individually so we can dequeue if already enqueued by another plugin.
					// TODO Rework dependencies part (or remove completely).

					$src = esc_url($v['src']);
					$media = isset($v['media']) ? $v['media'] : 'all';

					wp_enqueue_style($style, $src, [], SIMPLE_CALENDAR_VERSION, $media);
				} elseif (is_string($v) && !empty($v)) {
					wp_enqueue_style($v);
				}
			}
		}
	}
}
