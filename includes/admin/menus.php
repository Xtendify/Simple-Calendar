<?php
/**
 * Admin Menus
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Menus.
 *
 * Handles the plugin admin dashboard menus.
 *
 * @since 3.0.0
 */
class Menus {

	/**
	 * The main menu screen hook.
	 *
	 * @access public
	 * @var string
	 */
	public static $main_menu = '';

	/**
	 * Plugin basename.
	 *
	 * @access private
	 * @var string
	 */
	private static $plugin = '';

	/**
	 * Set properties.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		self::$main_menu = 'edit.php?post_type=calendar';

		add_action( 'admin_menu', array( __CLASS__, 'add_menu_items' ) );

		self::$plugin = plugin_basename( SIMPLE_CALENDAR_MAIN_FILE );

		new Welcome();

		// Conditional redirect to welcome page on activation.
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		// Links and meta content in plugins page.
		add_filter( 'plugin_action_links_' . self::$plugin, array( __CLASS__, 'plugin_action_links' ), 10, 5 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		// Custom text in admin footer.
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Add menu items.
	 *
	 * @since 3.0.0
	 */
	public static function add_menu_items() {

		add_submenu_page(
			self::$main_menu,
			__( 'Add ons', 'google-calendar-events' ),
			__( 'Add ons', 'google-calendar-events' ),
			'manage_options',
			'simple-calendar_add_ons',
			function() {
				wp_redirect( 'https://simplecalendar.io/addons/' );
			}
		);

		add_submenu_page(
			self::$main_menu,
			__( 'Settings', 'google-calendar-events' ),
			__( 'Settings', 'google-calendar-events' ),
			'manage_options',
			'simple-calendar_settings',
			function () {
				$settings_pages = new Settings_Pages( 'settings' );
				$settings_pages->html();
			}
		);

		add_submenu_page(
			self::$main_menu,
			__( 'Tools', 'google-calendar-events' ),
			__( 'Tools', 'google-calendar-events' ),
			'manage_options',
			'simple-calendar_tools',
			function () {
				$settings_pages = new Settings_Pages( 'tools' );
				$settings_pages->html();
			}
		);

		do_action( 'simcal_admin_add_menu_items' );
	}

	/**
	 * Action links in plugins page.
	 *
	 * @since  3.0.0
	 *
	 * @param  array  $action_links
	 * @param  string $file
	 *
	 * @return array
	 */
	public static function plugin_action_links( $action_links, $file ) {

		if ( self::$plugin == $file ) {

			$links = array();
			$links['settings']  = '<a href="' . admin_url( 'edit.php?post_type=calendar&page=simple-calendar_settings' ) . '">' . __( 'Settings', 'google-calendar-events' ) . '</a>';
			$links['feeds']     = '<a href="' . admin_url( 'edit.php?post_type=calendar' ) . '">' . __( 'Calendars', 'google-calendar-events' ) . '</a>';

			return apply_filters( 'simcal_plugin_action_links', array_merge( $links, $action_links ) );
		}

		return $action_links;
	}

	/**
	 * Links in plugin meta in plugins page.
	 *
	 * @since  3.0.0
	 *
	 * @param  array  $meta_links
	 * @param  string $file
	 *
	 * @return array
	 */
	public static function plugin_row_meta( $meta_links, $file ) {

		if ( self::$plugin == $file ) {

			$links = array();
			$links['github']         = '<a href="' . \SimpleCalendar\plugin()->get_url( 'github' ) . '" target="_blank" >GitHub</a>';
			$links['documentation']  = '<a href="' . \SimpleCalendar\plugin()->get_url( 'docs' ) . '" target="_blank" >' . __( 'Documentation', 'google-calendar-events' ) . '</a>';
			$links['support']        = '<a href="' . \SimpleCalendar\plugin()->get_url( 'support' ) . '" target="_blank" >' . __( 'Support', 'google-calendar-events' ) . '</a>';

			return apply_filters( 'simcal_plugin_action_links', array_merge( $meta_links, $links ) );
		}

		return $meta_links;
	}

	/**
	 * Handle redirects to welcome page after install and updates.
	 *
	 * Transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 *
	 * @since 3.0.0
	 */
	public function admin_redirects() {

		$transient = get_transient( '_simple-calendar_activation_redirect' );

		if ( ! $transient || is_network_admin() || isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		delete_transient( '_simple-calendar_activation_redirect' );

		// Do not redirect if already on welcome page screen.
		if ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( 'simple-calendar_about' ) ) ) {
			return;
		}

		$url = add_query_arg(
			'simcal_install',
			esc_attr( $transient ),
			admin_url( 'index.php?page=simple-calendar_about' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Admin footer text filter callback.
	 *
	 * Change this plugin screens admin footer text.
	 *
	 * @since  3.0.0
	 *
	 * @param  $footer_text
	 *
	 * @return string|void
	 */
	public function admin_footer_text( $footer_text ) {

		// Check to make sure we're on a WooCommerce admin page
		if ( simcal_is_admin_screen() !== false ) {

			// Change the footer text
			if ( ! get_option( 'simple-calendar_admin_footer_text_rated' ) ) {

				$footer_text = sprintf(
					__( 'If you like <strong>Simple Calendar</strong> please leave us a %s&#9733;&#9733;&#9733;&#9733;&#9733;%s rating on WordPress.org. A huge thank you in advance!', 'google-calendar-events' ),
					'<a href="https://wordpress.org/support/view/plugin-reviews/google-calendar-events?filter=5#postform" target="_blank" class="simcal-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'google-calendar-events' ) . '">', '</a>'
				);

				$footer_text .= '<script type="text/javascript">';
				$footer_text .= "jQuery( 'a.simcal-rating-link' ).click( function() {
						jQuery.post( '" . \SimpleCalendar\plugin()->ajax_url() . "', { action: 'simcal_rated' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});";
				$footer_text .= '</script>';

			} else {

				$footer_text = __( 'Thank you for using Simple Calendar!', 'google-calendar-events' );

			}

		}

		return $footer_text;
	}

}
