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
		return apply_filters( 'simcal_add_' . $this->option_group . '_' . $this->id .'_sections', array() );
	}

	/**
	 * Add fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_fields() {
		return apply_filters( 'simcal_add_' . $this->option_group . '_' . $this->id . '_fields', array() );
	}

}
