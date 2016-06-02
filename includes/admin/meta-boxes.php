<?php
/**
 * Meta Boxes
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

use SimpleCalendar\Admin\Metaboxes as Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta boxes class.
 *
 * Handles write panels in post types and post meta.
 *
 * @since 3.0.0
 */
class Meta_Boxes {

	/**
	 * Saved meta boxes status.
	 *
	 * @access private
	 * @var bool
	 */
	private static $saved_meta_boxes = false;

	/**
	 * Post types to attach calendars.
	 *
	 * @access private
	 * @var array
	 */
	private $post_types = array();

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$settings = get_option( 'simple-calendar_settings_calendars' );
		if ( isset( $settings['general']['attach_calendars_posts'] ) ) {
			$this->post_types = $settings['general']['attach_calendars_posts'];
		}

		// Load meta boxes to save settings.
		new Metabox\Settings();
		new Metabox\Attach_Calendar();
		new Metabox\Newsletter();
		do_action( 'simcal_load_meta_boxes' );

		// Add meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );

		// Process meta boxes.
		add_action( 'simcal_save_settings_meta','\SimpleCalendar\Admin\Metaboxes\Settings::save', 10, 2 );
		add_action( 'simcal_save_attach_calendar_meta','\SimpleCalendar\Admin\Metaboxes\Attach_Calendar::save', 10, 2 );

		// Save meta boxes data.
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );

		// Uncomment this for debugging $_POST while saving a meta box.
		// add_action( 'save_post', function() { echo '<pre>'; print_r( $_POST ); echo '</pre>'; die(); } );
	}

	/**
	 * Add meta boxes.
	 *
	 * @since 3.0.0
	 */
	public function add_meta_boxes() {

		add_meta_box(
			'simcal-calendar-settings',
			__( 'Calendar Settings', 'google-calendar-events' ),
			'\SimpleCalendar\Admin\Metaboxes\Settings::html',
			'calendar',
			'normal',
			'core'
		);

		$addons = apply_filters( 'simcal_installed_addons', array() );
		if ( empty( $addons ) ) {

			add_meta_box(
				'simcal-newsletter',
				__( 'Get 20% off all Pro Add-ons', 'google-calendar-events' ),
				'\SimpleCalendar\Admin\Metaboxes\Newsletter::html',
				'calendar',
				'side',
				'default'
			);

		}

		add_meta_box(
			'simcal-get-shortcode',
			__( 'Calendar Shortcode', 'google-calendar-events' ),
			'\SimpleCalendar\Admin\Metaboxes\Get_Shortcode::html',
			'calendar',
			'side',
			'default'
		);

		// Add meta box if there are calendars.
		if ( ( true == simcal_get_calendars() ) && ! empty( $this->post_types ) ) {
			foreach ( $this->post_types as $post_type ) {
				add_meta_box(
					'simcal-attach-calendar',
					__( 'Attach Calendar', 'google-calendar-events' ),
					'\SimpleCalendar\Admin\Metaboxes\Attach_Calendar::html',
					$post_type,
					'side',
					'low'
				);
			}
		}

		do_action( 'simcal_add_meta_boxes' );
	}

	/**
	 * Check if we're saving, then trigger action.
	 *
	 * @since  3.0.0
	 *
	 * @param  int    $post_id
	 * @param  object $post
	 *
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {

		// $post_id and $post are required.
		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		// Don't save meta boxes for revisions or autosaves.
		if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the nonce.
		if ( empty( $_POST['simcal_meta_nonce'] ) || ! wp_verify_nonce( $_POST['simcal_meta_nonce'], 'simcal_save_data' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
			return;
		}

		// Check user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops.
		// This would have been perfect:
		// `remove_action( current_filter(), __METHOD__ );`
		// But cannot be used due to a WordPress bug:
		// @link https://core.trac.wordpress.org/ticket/17817
		// @see also https://github.com/woothemes/woocommerce/issues/6485
		self::$saved_meta_boxes = true;

		// Check the post type.
		if ( 'calendar' == $post->post_type ) {
			do_action( 'simcal_save_settings_meta', $post_id, $post );
		} elseif ( in_array( $post->post_type, $this->post_types ) ) {
			do_action( 'simcal_save_attach_calendar_meta', $post_id, $post );
		}

		do_action( 'simcal_save_meta_boxes', $post_id, $post );
	}

}
