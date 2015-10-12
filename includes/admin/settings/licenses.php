<?php
/**
 * Licenses management page
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Settings;

use SimpleCalendar\Abstracts\Settings_Page;

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
class Licenses extends Settings_Page {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->id           = 'licenses';
		$this->option_group = 'settings';
		$this->label        = __( 'Licenses', 'google-calendar-events' );
		$this->description  = __( 'Manage add-ons licenses.', 'google-calendar-events' );
		$this->sections     = $this->add_sections();
		$this->fields       = $this->add_fields();
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
			'add-ons' => array(
				'title' => __( 'Add-ons', 'google-calendar-events' ),
				'description' => __( 'Manage here your license keys for premium add-ons', 'google-calendar-events' ),
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

			if ( 'add-ons' == $section ) {

				$addons = apply_filters( 'simcal_installed_addons', array() );

				if ( ! empty( $addons ) && is_array( $addons ) ) {

					foreach ( $addons as $key => $name ) {

						$fields[ $section ][ $key ] = array(
							'type'      => 'standard',
							'subtype'   => 'text',
							'title'     => esc_attr( $name ),
							'name'      => 'simple-calendar_' . $this->option_group . '_' . $this->id . '[add-ons][' . $key . ']',
							'id'        => 'simple-calendar-' . $this->option_group . '-' . $this->id . '-add-ons-' . sanitize_key( $key ),
							'value'     => $this->get_option_value( $section, $key ),
							'class'     => array(
								'regular-text',
							)
						);

					}

				}

			}

		}

		return apply_filters( 'simcal_add_' . $this->option_group . '_' . $this->id . '_fields', $fields );
	}

}
