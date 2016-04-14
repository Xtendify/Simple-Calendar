<?php
/**
 * Front End Assets
 *
 * @package SimpleCalendar;
 */
namespace SimpleCalendar;

use SimpleCalendar\Abstracts\Calendar_View;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front end scripts and styles.
 *
 * Loads scripts and styles based on the requested calendar view.
 *
 * @since 3.0.0
 */
class Assets {

	/**
	 * Load minified assets.
	 *
	 * @access private
	 * @var string
	 */
	private $min = '.min';

	/**
	 * Scripts.
	 *
	 * @access private
	 * @var array
	 */
	private $scripts = array();

	/**
	 * Styles.
	 *
	 * @access private
	 * @var array
	 */
	private $styles = array();

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
	public function __construct() {

		$this->min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG == true ) ? '' : '.min';

		$settings = get_option( 'simple-calendar_settings_advanced' );

		if ( isset( $settings['assets']['disable_css'] ) ) {
			$this->disable_styles = 'yes' == $settings['assets']['disable_css'] ? true : false;
		}

		add_action( 'init', array( $this, 'register' ), 20 );
		add_action( 'init', array( $this, 'enqueue' ), 40 );
	}

	/**
	 * Register scripts and styles.
	 *
	 * @since 3.0.0
	 */
	public function register() {
		do_action( 'simcal_register_assets', $this->min );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 3.0.0
	 */
	public function enqueue() {

		add_action( 'wp_enqueue_scripts', array( $this, 'load' ), 10 );

		do_action( 'simcal_enqueue_assets', $this->min );


		$min = $this->min;
		// Improves compatibility with themes and plugins using Isotope and Masonry.
		add_action( 'wp_enqueue_scripts',
			function () use ( $min ) {
				if ( wp_script_is( 'simcal-qtip', 'enqueued' ) ) {
					wp_enqueue_script(
						'simplecalendar-imagesloaded',
						SIMPLE_CALENDAR_ASSETS . 'js/vendor/imagesloaded.pkgd' . $min . '.js',
						array( 'simcal-qtip' ),
						'3.1.8',
						true
					);
				}
			}, 1000 );
	}

	/**
	 * Load scripts and styles.
	 *
	 * @since 3.0.0
	 */
	public function load() {

		$types = simcal_get_calendar_types();

		foreach ( $types as $calendar => $views ) {
			foreach( $views as $key => $view ) {

				$view = simcal_get_calendar_view( 0, $calendar . '-' . $view );

				$scripts[] = $view->scripts( $this->min );
				$styles[] = $view->styles( $this->min );
			}
		}

		$this->get_widgets_assets();
		$this->scripts = apply_filters( 'simcal_front_end_scripts', $scripts, $this->min );
		// First check if there is a multi-dimensional array of scripts
		if ( isset( $this->scripts[0] ) ) {
			foreach ( $this->scripts as $script ) {
				$this->load_scripts ( $script );
			}
		} else {
			$this->load_scripts( $this->scripts );
		}
		$this->styles = apply_filters( 'simcal_front_end_styles', $styles, $this->min );
		// First check if there is a multi-dimensional array of styles
		if ( isset( $this->styles[0] ) ) {
			foreach( $this->styles as $style ) {
				$this->load_styles( $style );
			}
		} else {
			$this->load_styles( $this->styles );
		}
	}

	/**
	 * Get widgets assets.
	 *
	 * @since 3.0.0
	 */
	public function get_widgets_assets() {

		$widgets = get_option( 'widget_gce_widget' );

		if ( ! empty( $widgets ) && is_array( $widgets ) ) {

			foreach ( $widgets as $settings ) {

				if ( ! empty( $settings ) && is_array( $settings ) ) {

					if ( isset( $settings['calendar_id'] ) ) {

						$view = simcal_get_calendar_view( absint( $settings['calendar_id'] ) );

						if ( $view instanceof Calendar_View ) {
							add_filter( 'simcal_front_end_scripts', function ( $scripts, $min ) use ( $view ) {
								return array_merge( $scripts, $view->scripts( $min ) );
							}, 100, 2 );
							add_filter( 'simcal_front_end_styles', function ( $styles, $min ) use ( $view ) {
								return array_merge( $styles, $view->styles( $min ) );
							}, 100, 2 );
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
	public function load_scripts( $scripts ) {

		// Only load if not disabled in the settings
		if ( ! empty( $scripts ) && is_array( $scripts ) ) {

			foreach ( $scripts as $script => $v ) {

				if ( ! empty( $v['src'] ) ) {

					$src        = esc_url( $v['src'] );
					$deps       = isset( $v['deps'] )        ? $v['deps']       : array();
					$ver        = isset( $v['ver'] )         ? $v['ver']        : SIMPLE_CALENDAR_VERSION;
					$in_footer  = isset( $v['in_footer'] )   ? $v['in_footer']  : false;

					wp_enqueue_script( $script, $src, $deps, $ver, $in_footer );

					if ( ! empty( $v['localize'] ) && is_array( $v['localize'] ) ) {
						foreach ( $v['localize'] as $object => $l10n ) {
							wp_localize_script( $script, $object, $l10n );
						}
					}

				} elseif ( is_string( $v ) && ! empty( $v ) ) {

					wp_enqueue_script( $v );
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
	public function load_styles( $styles ) {

		// Only load if not disabled in the settings
		if ( ! empty( $styles ) && is_array( $styles ) && false === $this->disable_styles ) {

			foreach ( $styles as $style => $v ) {

				if ( ! empty( $v['src'] ) ) {

					$src    = esc_url( $v['src'] );
					$deps   = isset( $v['deps'] )   ? $v['deps']    : array();
					$ver    = isset( $v['ver'] )    ? $v['ver']     : SIMPLE_CALENDAR_VERSION;
					$media  = isset( $v['media'] )  ? $v['media']   : 'all';

					wp_enqueue_style( $style, $src, $deps, $ver, $media );

				} elseif ( is_string( $v ) && ! empty( $v ) ) {

					wp_enqueue_style( $v );
				}

			}

		}
	}
}
