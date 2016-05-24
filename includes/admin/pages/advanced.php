<?php
/**
 * General Settings Page
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Pages;

use SimpleCalendar\Abstracts\Admin_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * General settings.
 *
 * Handles the plugin general settings and outputs the markup the settings page.
 *
 * @since 3.0.0
 */
class Advanced extends Admin_Page {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->id           = 'advanced';
		$this->option_group = 'settings';
		$this->label        = __( 'Advanced', 'google-calendar-events' );
		//$this->description  = __( 'Advanced settings.', 'google-calendar-events' );
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
		return apply_filters( 'simcal_add_' . $this->option_group . '_' . $this->id .'_sections', array(
			'assets' => array(
				'title'       => __( 'Styles', 'google-calendar-events' ),
				'description' => __( 'Manage front end assets that handle the calendars appearance.', 'google-calendar-events' )
			),
			'installation' => array(
				'title'       => __( 'Installation', 'google-calendar-events' ),
				'description' => __( 'Manage your data (plugin settings and saved calendars).', 'google-calendar-events' )
			)
		) );
	}

	/**
	 * Add fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_fields() {

		$fields       = array();
		$this->values = get_option( 'simple-calendar_' . $this->option_group . '_' . $this->id );

		foreach ( $this->sections  as $section => $a ) :

			if ( 'assets' == $section ) {

				$fields[ $section ] = array(
					'disable_css' => array(
						'title'   => __( 'Disable CSS', 'google-calendar-events' ),
						'tooltip' => __( 'If ticked, this option will prevent all front end stylesheets to load. This also includes all add-on stylesheets.', 'google-calendar-events' ),
						'type'    => 'checkbox',
						'name'    => 'simple-calendar_' . $this->option_group . '_' . $this->id . '[' . $section . '][disable_css]',
						'id'      => 'simple-calendar-' . $this->option_group . '-' . $this->id . '-' . $section . '-disable-css',
						'value'   => $this->get_option_value( $section, 'disable_css' )
					),
				);

			} elseif ( 'installation' == $section ) {

				$fields[ $section ] = array(
					'delete_settings' => array(
						'title'   => __( 'Delete settings', 'google-calendar-events' ),
						'tooltip' => __( 'Tick this option if you want to wipe this plugin settings from database when uninstalling.', 'google-calendar-events' ),
						'type'    => 'checkbox',
						'name'    => 'simple-calendar_' . $this->option_group . '_' . $this->id . '[' . $section . '][delete_settings]',
						'id'      => 'simple-calendar-' . $this->option_group . '-' . $this->id . '-' . $section . '-delete-settings',
						'value'   => $this->get_option_value( $section, 'delete_settings' ),
					),
					'erase_data' => array(
						'title'   => __( 'Erase calendar data', 'google-calendar-events' ),
						'tooltip' => __( 'By default your data will be retained in database even after uninstall. Tick this option if you want to delete all your calendar data when uninstalling.', 'google-calendar-events' ),
						'type'    => 'checkbox',
						'name'    => 'simple-calendar_' . $this->option_group . '_' . $this->id . '[' . $section . '][erase_data]',
						'id'      => 'simple-calendar_' . $this->option_group . '_' . $this->id . '-delete-data',
						'value'   => $this->get_option_value( $section, 'erase_data' ),
					)
				);

			}

		endforeach;

		return apply_filters( 'simcal_add_' . $this->option_group . '_' . $this->id . '_fields', $fields );
	}

}
