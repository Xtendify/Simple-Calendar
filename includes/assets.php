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
 */
class Assets {

	/**
	 * Load minified assets.
	 *
	 * @access private
	 * @var string
	 */
	private $min = '';

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
	 * Hook in tabs.
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

		add_action( 'init', array( $this, 'enqueue' ), 20 );
		add_action( 'wp_print_styles', array( $this, 'disable' ), 100 );
	}

	/**
	 * Load assets.
	 */
	public function enqueue() {

		add_action( 'wp_enqueue_scripts', array( $this, 'load' ), 10 );

		// Improves compatibility with themes and plugins using Isotope and Masonry.
		$min = $this->min;
		add_action( 'wp_enqueue_scripts',
			function() use ( $min ) {
				if ( wp_script_is( 'simcal-qtip', 'enqueued' )  ) {
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

	/**
	 * Load scripts and styles.
	 */
	public function load() {

		$id = 0;
		$scripts = $styles = array();

		if ( is_singular() ) {

			global $post, $post_type;

			if ( 'calendar' == $post_type ) {

				$id = get_queried_object_id();

			} else {

				$id = absint( get_post_meta( $post->ID, '_simcal_attach_calendar_id', true ) );

				if ( $id === 0 ) {

					preg_match_all( '/' . get_shortcode_regex() . '/s', $post->post_content, $matches, PREG_SET_ORDER );

					if ( ! empty( $matches ) && is_array( $matches ) ) {
						foreach ( $matches as $shortcode ) {
							if ( 'calendar' === $shortcode[2] || 'gcal' === $shortcode[2] ) {
								$atts = shortcode_parse_atts( $shortcode[3] );
								$id   = isset( $atts['id'] ) ? intval( $atts['id'] ) : 0;
							}
						}
					}
				}
			}
		}

		if ( $id > 0 ) {

			$view = simcal_get_calendar_view( $id );

			if ( $view instanceof Calendar_View ) {
				$scripts = $view->scripts( $this->min );
				$styles  = $view->styles( $this->min );
			}
		}

		$this->scripts = apply_filters( 'simcal_front_end_scripts', $scripts, $this->min );
		$this->load_scripts( $this->scripts );

		$this->styles = apply_filters( 'simcal_front_end_styles', $styles, $this->min );
		$this->load_styles( $this->styles );

		do_action( 'simcal_load_assets', $this->min );
	}

	/**
	 * Disable scripts and styles.
	 */
	public function disable() {
		if ( $this->disable_scripts === true ) {
			$scripts = apply_filters( 'simcal_front_end_scripts', $this->scripts, $this->min );
			foreach ( $scripts as $script => $v ) {
				wp_dequeue_script( $script );
			}
		}
		if ( $this->disable_styles === true ) {
			$styles = apply_filters( 'simcal_front_end_styles', $this->styles, $this->min );
			foreach ( $styles as $style => $v ) {
				wp_dequeue_style( $style );
			}
		}
	}

	/**
	 * Scripts.
	 *
	 * @access private
	 *
	 * @param array $scripts
	 */
	public function load_scripts( $scripts ) {

		if ( ! empty( $scripts ) && is_array( $scripts ) ) {

			foreach ( $scripts as $script => $v ) {

				if ( isset( $v['src'] ) ) {

					$src        = esc_url( $v['src'] );
					$deps       = isset( $v['deps'] )      ? $v['deps']      : array();
					$ver        = isset( $v['ver'] )       ? $v['ver']       : SIMPLE_CALENDAR_VERSION;
					$in_footer  = isset( $v['in_footer'] ) ? $v['in_footer'] : false;

					wp_enqueue_script( $script, $src, $deps, $ver, $in_footer );
				}

				if ( isset( $v['localize'] ) ) {

					$localize = $v['localize'];

					if ( $localize && is_array( $localize ) ) {
						foreach ( $localize as $object => $l10n ) {
							wp_localize_script( $script, $object, $l10n );
						}
					}

				}

			}

		}
	}

	/**
	 * Styles.
	 *
	 * @access private
	 *
	 * @param array $styles
	 */
	public function load_styles( $styles ) {

		if ( ! empty( $styles ) && is_array( $styles ) ) {

			foreach ( $styles as $style => $v ) {

				if ( isset( $v['src'] ) ) {

					$src    = esc_url( $v['src'] );
					$deps   = isset( $v['deps'] )   ? $v['deps']    : array();
					$ver    = isset( $v['ver'] )    ? $v['ver']     : SIMPLE_CALENDAR_VERSION;
					$media  = isset( $v['media'] )  ? $v['media']   : 'all';

					wp_enqueue_style( $style, $src, $deps, $ver, $media );
				}

			}

		}
	}

}
