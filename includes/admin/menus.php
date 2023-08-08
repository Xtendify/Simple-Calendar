<?php
/**
 * Admin Menus
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Admin Menus.
 *
 * Handles the plugin admin dashboard menus.
 *
 * @since 3.0.0
 */
class Menus
{
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
	public function __construct()
	{
		self::$main_menu = 'edit.php?post_type=calendar';

		add_action('admin_menu', [__CLASS__, 'add_menu_items']);

		self::$plugin = plugin_basename(SIMPLE_CALENDAR_MAIN_FILE);

		new Welcome();

		// Links and meta content in plugins page.
		add_filter('plugin_action_links_' . self::$plugin, [__CLASS__, 'plugin_action_links'], 10, 5);
		add_filter('plugin_row_meta', [__CLASS__, 'plugin_row_meta'], 10, 2);
		// Custom text in admin footer.
		add_filter('admin_footer_text', [$this, 'admin_footer_text'], 1);
	}

	/**
	 * Add menu items.
	 *
	 * @since 3.0.0
	 */
	public static function add_menu_items()
	{
		add_submenu_page(
			self::$main_menu,
			__('Settings', 'google-calendar-events'),
			__('Settings', 'google-calendar-events'),
			'manage_options',
			'simple-calendar_settings',
			function () {
				$page = new Pages('settings');
				$page->html();
			}
		);

		add_submenu_page(
			self::$main_menu,
			__('Add-ons', 'google-calendar-events'),
			__('Add-ons', 'google-calendar-events'),
			'manage_options',
			'simple-calendar_add_ons',
			function () {
				$page = new Pages('add-ons');
				$page->html();
			}
		);

		add_submenu_page(
			self::$main_menu,
			__('Tools', 'google-calendar-events'),
			__('Tools', 'google-calendar-events'),
			'manage_options',
			'simple-calendar_tools',
			function () {
				$page = new Pages('tools');
				$page->html();
			}
		);

		do_action('simcal_admin_add_menu_items');
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
	public static function plugin_action_links($action_links, $file)
	{
		if (self::$plugin == $file) {
			$links = [];
			$links['settings'] =
				'<a href="' .
				admin_url('edit.php?post_type=calendar&page=simple-calendar_settings') .
				'">' .
				__('Settings', 'google-calendar-events') .
				'</a>';
			$links['feeds'] =
				'<a href="' .
				admin_url('edit.php?post_type=calendar') .
				'">' .
				__('Calendars', 'google-calendar-events') .
				'</a>';

			return apply_filters('simcal_plugin_action_links', array_merge($links, $action_links));
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
	public static function plugin_row_meta($meta_links, $file)
	{
		if (self::$plugin == $file) {
			$links = [];
			$links['add-ons'] =
				'<a href="' .
				simcal_ga_campaign_url(simcal_get_url('addons'), 'core-plugin', 'plugin-listing') .
				'" target="_blank" >' .
				__('Add-ons', 'google-calendar-events') .
				'</a>';

			return apply_filters('simcal_plugin_action_links', array_merge($meta_links, $links));
		}

		return $meta_links;
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
	public function admin_footer_text($footer_text)
	{
		// Check to make sure we're on a SimpleCal admin page
		$screen = simcal_is_admin_screen();
		if ($screen !== false) {
			// Change the footer text
			if (!get_option('simple-calendar_admin_footer_text_rated')) {
				$footer_text = sprintf(
					__(
						'If you like <strong>Simple Calendar</strong> please leave us a %s&#9733;&#9733;&#9733;&#9733;&#9733; rating on WordPress.org%s. A huge thank you in advance!',
						'google-calendar-events'
					),
					'<a href="https://wordpress.org/support/view/plugin-reviews/google-calendar-events?filter=5#postform" target="_blank" class="simcal-rating-link" data-rated="' .
						esc_attr__('Thanks :)', 'google-calendar-events') .
						'">',
					'</a>'
				);
				// Add a nonce field used in ajax.
				$footer_text .= wp_nonce_field('simcal_rating_nonce', 'simcal_rating_nonce');
				$footer_text .= '<script type="text/javascript">';
				$footer_text .=
					"jQuery( 'a.simcal-rating-link' ).click( function() {
						jQuery.post( '" .
					\SimpleCalendar\plugin()->ajax_url() .
					"', { action: 'simcal_rated', nonce: jQuery( '#simcal_rating_nonce' ).val() } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});";
				$footer_text .= '</script>';
			} else {
				$footer_text = __('Thanks for using Simple Calendar!', 'google-calendar-events');
			}
		}

		return $footer_text;
	}
}
