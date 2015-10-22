<?php
/**
 * Licenses management page
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Pages;

use SimpleCalendar\Abstracts\Admin_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Licenses.
 *
 * Handles the plugin add-ons licenses if at least one is installed and active.
 *
 * @since 3.0.0
 */
class Licenses extends Admin_Page {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->id           = 'licenses';
		$this->option_group = 'settings';
		$this->label        = __( 'Add-ons Licenses', 'google-calendar-events' );
		$this->description  = __( 'Manage add-ons licenses.', 'google-calendar-events' );
		$this->sections     = $this->add_sections();
		$this->fields       = $this->add_fields();

		add_filter( 'simcal_settings_page_submit', function() { return false; } );

		add_action( 'simcal_admin_page', function( $page, $tab ) {
			if ( 'settings' == $page && 'licenses' == $tab ) {
				wp_nonce_field( 'simcal_license_manager', 'simcal_license_manager' );
			}
		}, 10, 2 );
	}

	/**
	 * Add sections.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_sections() {
		$sections = array(
			'keys' => array(
				'title' => __( 'Add-ons', 'google-calendar-events' ),
				'description' => __( 'Manage here your license keys for premium add-ons.', 'google-calendar-events' ) .
				                 '<br>' .
				                 __( 'Enter a license key in the corresponding field, save changes, then activate it to receive automatic updates according to your license terms.', 'google-calendar-events' ),
			),
		);
		return apply_filters( 'simcal_add_' . $this->option_group . '_' . $this->id .'_sections', $sections );
	}

	/**
	 * Add fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_fields() {

		$fields = array();
		$this->values = get_option( 'simple-calendar_' . $this->option_group . '_' . $this->id );

		foreach ( $this->sections as $section => $contents ) {

			if ( 'keys' == $section ) {

				$addons = apply_filters( 'simcal_installed_addons', array() );

				if ( ! empty( $addons ) && is_array( $addons ) ) {

					foreach ( $addons as $addon_id => $addon_name ) {

						$fields[ $section ][ $addon_id ] = array(
							'type'      => 'license',
							'addon'     => $addon_id,
							'title'     => esc_attr( $addon_name ),
							'name'      => 'simple-calendar_' . $this->option_group . '_' . $this->id . '[' . $section . '][' . $addon_id . ']',
							'id'        => 'simple-calendar-' . $this->option_group . '-' . $this->id . '-' . $section . '-' . sanitize_key( $addon_id ),
							'value'     => $this->get_option_value( $section, $addon_id ),
							'class'     => array(
								'regular-text',
								'ltr',
							)
						);

					}

				}

			}

		}

		return apply_filters( 'simcal_add_' . $this->option_group . '_' . $this->id . '_fields', $fields );
	}

}
