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
	 * Disable scripts.
	 *
	 * @access public
	 * @var bool
	 */
	public $disable_scripts = false;

	/**
	 * Disable styles.
	 *
	 * @access public
	 * @var bool
	 */
	public $disable_styles = false;

	/**
	 * Disable styles.
	 *
	 * @access public
	 * @var bool
	 */
	public $always_enqueue = false;

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG == true ) ? '' : '.min';

		$settings = get_option( 'simple-calendar_settings_advanced' );
		if ( isset( $settings['assets']['disable_js'] ) ) {
			$this->disable_scripts = 'yes' == $settings['assets']['disable_js'] ? true : false;
		}

		if ( isset( $settings['assets']['disable_css'] ) ) {
			$this->disable_styles = 'yes' == $settings['assets']['disable_css'] ? true : false;
		}

		if ( isset( $settings['assets']['always_enqueue'] ) ) {
			$this->always_enqueue = 'yes' == $settings['assets']['always_enqueue'] ? true : false;
		}

		add_action( 'init', array( $this, 'register' ), 20 );
		add_action( 'init', array( $this, 'enqueue' ), 40 );
		add_action( 'wp_print_styles', array( $this, 'disable' ), 100 );
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

		if ( false === $this->disable_scripts ) {
			$min = $this->min;
			// Improves compatibility with themes and plugins using Isotope and Masonry.
			add_action( 'wp_enqueue_scripts',
				function () use ( $min ) {
					if ( wp_script_is( 'simcal-qtip', 'enqueued' ) ) {
						wp_enqueue_script(
							'simplecalendar-imagesloaded',
							SIMPLE_CALENDAR_ASSETS . 'js/vendor/imagesloaded' . $min . '.js',
							array( 'simcal-qtip' ),
							'3.1.8',
							true
						);
					}
				}, 1000 );
		}
	}

	/**
	 * Load scripts and styles.
	 *
	 * @since 3.0.0
	 */
	public function load() {

		if ( $this->always_enqueue ) {
			$scripts = $this->get_default_scripts();
			$styles  = $this->get_default_styles();

			$this->scripts = apply_filters( 'simcal_front_end_scripts', $scripts, $this->min );
			$this->styles  = apply_filters( 'simcal_front_end_styles', $styles, $this->min );

			$this->load_scripts( $this->scripts );
			$this->load_styles( $this->styles );

			return;
		}

		$id = 0;
		$cal_id = array();
		$scripts = $styles = array();

		if ( is_singular() ) {

			global $post, $post_type;

			if ( 'calendar' == $post_type ) {

				$id = get_queried_object_id();

				$view = simcal_get_calendar_view( $id );
				if ( $view instanceof Calendar_View ) {
					$scripts[] = $view->scripts( $this->min );
					$styles[]  = $view->styles( $this->min );
				}

			} else {

				$id = absint( get_post_meta( $post->ID, '_simcal_attach_calendar_id', true ) );

				if ( $id === 0 ) {

					preg_match_all( '/' . get_shortcode_regex() . '/s', $post->post_content, $matches, PREG_SET_ORDER );

					if ( ! empty( $matches ) && is_array( $matches ) ) {
						foreach ( $matches as $shortcode ) {
							if ( 'calendar' === $shortcode[2] || 'gcal' === $shortcode[2] ) {
								$atts = shortcode_parse_atts( $shortcode[3] );
								$cal_id[]   = isset( $atts['id'] ) ? intval( $atts['id'] ) : 0;
							}
						}
					}
				}
			}
		}

		foreach( $cal_id as $i ) {

			if ( $i > 0 ) {

				$view = simcal_get_calendar_view( $i );

				if ( $view instanceof Calendar_View ) {
					$scripts[] = $view->scripts( $this->min );
					$styles[] = $view->styles( $this->min );
				}
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
	 * Disable scripts and styles.
	 *
	 * @since 3.0.0
	 */
	public function disable() {
		if ( true === $this->disable_scripts ) {
			$scripts = apply_filters( 'simcal_front_end_scripts', $this->scripts, $this->min );
			foreach ( $scripts as $script => $v ) {
				wp_dequeue_script( $script );
			}
		}
		if ( true === $this->disable_styles ) {
			$styles = apply_filters( 'simcal_front_end_styles', $this->styles, $this->min );
			foreach ( $styles as $style => $v ) {
				wp_dequeue_style( $style );
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

		if ( ! empty( $styles ) && is_array( $styles ) ) {

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

	/**
	 * Return the default scripts that are loaded. Used mainly for the always enqueue scripts option.
	 *
	 * This can be improved.
	 */
	public function get_default_scripts() {
		return array(
			'simcal-qtip' => array(
				'src'       => SIMPLE_CALENDAR_ASSETS . 'js/vendor/qtip' . $this->min . '.js',
				'deps'      => array( 'jquery' ),
				'ver'       => '2.2.1',
				'in_footer' => true,
			),
			'simcal-default-calendar' => array(
				'src'       => SIMPLE_CALENDAR_ASSETS . 'js/default-calendar' . $this->min . '.js',
				'deps'      => array(
					'jquery',
					'simcal-qtip',
				),
				'var'       => SIMPLE_CALENDAR_VERSION,
				'in_footer' => true,
				'localize'  => array(
					'simcal_default_calendar' => simcal_common_scripts_variables(),
				),
			),
		);
	}

	/**
	 * Return the default styles that are loaded. Used mainly for the always enqueue scripts option.
	 *
	 * This can be improved.
	 */
	public function get_default_styles() {
		return array(
			'simcal-qtip' => array(
				'src'   => SIMPLE_CALENDAR_ASSETS . 'css/vendor/qtip' . $this->min . '.css',
				'ver'   => '2.2.1',
				'media' => 'all',
			),
			'simcal-default-calendar-grid' => array(
				'src'   => SIMPLE_CALENDAR_ASSETS . 'css/default-calendar-grid' . $this->min . '.css',
				'deps'  => array(
					'simcal-qtip',
				),
				'ver'   => SIMPLE_CALENDAR_VERSION,
				'media' => 'all',
			),
			'simcal-default-calendar-list' => array(
				'src'   => SIMPLE_CALENDAR_ASSETS . 'css/default-calendar-list' . $this->min . '.css',
				'deps'  => array(
					'simcal-qtip',
				),
				'ver'   => SIMPLE_CALENDAR_VERSION,
				'media' => 'all',
			),
		);
	}

}
