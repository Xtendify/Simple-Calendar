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

		$this->id           = $tab = 'licenses';
		$this->option_group = $page = 'settings';
		$this->label        = __( 'Add-ons Licenses', 'google-calendar-events' );
		$this->description  = __( 'Manage add-ons licenses.', 'google-calendar-events' );
		$this->sections     = $this->add_sections();
		$this->fields       = $this->add_fields();

		// Disabled the 'save changes' button for this page.
		add_filter( 'simcal_admin_page_' . $page . '_' . $tab . '_submit', function() { return false; } );

		// Add html to page.
		add_action( 'simcal_admin_page_' . $page . '_' . $tab . '_end', array( __CLASS__, 'html' ) );
	}

	/**
	 * Add additional html.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public static function html()  {
		// Add a nonce field used in ajax.
		wp_nonce_field( 'simcal_license_manager', 'simcal_license_manager' );
		// Add a license 'reset' button.
		?>
		<br><br>
		<a href="#" id="simcal-reset-licenses" data-dialog="<?php _e( 'WARNING: Are you sure you want to clear all license keys from the settings?', 'google-calendar-events' ) ?>">
			<?php _e( 'Clear your license keys', 'google-calendar-events' ) ?> 
			<i class="simcal-icon-spinner simcal-icon-spin" style="display: none;"></i>
		</a>
		<?php

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
				                 __( 'Enter a license key in the corresponding field to activate it and receive automatic updates according to your license terms.', 'google-calendar-events' ),
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
