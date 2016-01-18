<?php
/**
 * Admin Assets
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin scripts and styles.
 *
 * Handles the plugin scripts and styles for back end dashboard pages.
 *
 * @since 3.0.0
 */
class Assets {

	/**
	 * Load minified assets.
	 *
	 * @access public
	 * @var string
	 */
	public $min = '.min';

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG == true ) ? '' : '.min';

		add_action( 'admin_enqueue_scripts', array( $this, 'load' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 3.0.0
	 */
	public function load() {

		$css_path        = SIMPLE_CALENDAR_ASSETS . 'css/';
		$css_path_vendor = $css_path . 'vendor/';
		$js_path         = SIMPLE_CALENDAR_ASSETS . 'js/';
		$js_path_vendor  = $js_path . 'vendor/';

		/* ====================== *
		 * Register Admin Scripts *
		 * ====================== */

		// TipTip uses ".minified.js" filename ending.
		wp_register_script(
			'simcal-tiptip',
			$js_path_vendor . 'jquery.tipTip' . ( ( $this->min !== '' ) ? '.minified' : '' ) . '.js',
			array( 'jquery' ),
			'1.3',
			true
		);
		wp_register_script(
			'simcal-select2',
			$js_path_vendor . 'select2' . $this->min . '.js',
			array(),
			'4.0',
			true
		);
		wp_register_script(
			'simcal-admin',
			$js_path . 'admin' . $this->min . '.js',
			array(
				'jquery',
				'jquery-ui-sortable',
				'jquery-ui-datepicker',
				'wp-color-picker',
				'simcal-tiptip',
				'simcal-select2',
			),
			SIMPLE_CALENDAR_VERSION,
			true
		);
		wp_register_script(
			'simcal-admin-add-calendar',
			$js_path . 'admin-add-calendar' . $this->min . '.js',
			array( 'simcal-select2' ),
			SIMPLE_CALENDAR_VERSION,
			true
		);

		/* ===================== *
		 * Register Admin Styles *
		 * ===================== */

		wp_register_style(
			'simcal-select2',
			$css_path_vendor . 'select2' . $this->min . '.css',
			array(),
			'4.0.0'
		);
		wp_register_style(
			'simcal-admin',
			$css_path . 'admin' . $this->min . '.css',
			array(
				'wp-color-picker',
				'simcal-select2',
			),
			SIMPLE_CALENDAR_VERSION
		);
		wp_register_style(
			'simcal-admin-add-calendar',
			$css_path . 'admin-add-calendar' . $this->min . '.css',
			array( 'simcal-select2' ),
			SIMPLE_CALENDAR_VERSION
		);

		if ( simcal_is_admin_screen() !== false ) {

			wp_enqueue_script( 'simcal-admin' );
			wp_localize_script(
				'simcal-admin',
				'simcal_admin',
				simcal_common_scripts_variables()
			);

			wp_enqueue_style( 'simcal-admin' );

		} else {

			global $post_type;
			$screen = get_current_screen();

			$post_types = array();
			$settings = get_option( 'simple-calendar_settings_calendars' );
			if ( isset( $settings['general']['attach_calendars_posts'] ) ) {
				$post_types = $settings['general']['attach_calendars_posts'];
			}

			$conditions = array(
				in_array( $post_type, (array) $post_types ),
				$screen->id == 'widgets',
			);

			if ( in_array( true, $conditions ) ) {

				wp_enqueue_script( 'simcal-admin-add-calendar' );
				wp_localize_script( 'simcal-admin-add-calendar', 'simcal_admin', array(
					'locale'   => get_locale(),
					'text_dir' => is_rtl() ? 'rtl' : 'ltr',
				) );

				wp_enqueue_style( 'simcal-admin-add-calendar' );
			}

		}

	}

}
