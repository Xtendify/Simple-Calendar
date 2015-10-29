<?php
/**
 * Add-ons Page
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Pages;

use SimpleCalendar\Abstracts\Admin_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add-ons page.
 *
 * @since 3.0.0
 */
class Add_Ons extends Admin_Page {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->id           = 'add-ons';
		$this->option_group = 'add-ons';
		$this->label        = __( 'Add-ons', 'google-calendar-events' );
		$this->description  = '';
		$this->sections     = $this->add_sections();
		$this->fields       = $this->add_fields();

		add_filter( 'simcal_settings_page_submit', function () {
			return false;
		} );
		add_action( 'simcal_admin_page', array( $this, 'html' ), 10, 2 );
	}

	/**
	 * Output page markup.
	 *
	 * @since 3.0.0
	 *
	 * @param string $page
	 * @param string $tab
	 */
	public function html( $page, $tab ) {

		if ( 'add-ons' == $tab ) {

			// @todo pull data from simplecalendar.io to showcase add-ons
			$js_redirect = '<script type="text/javascript">';
			$js_redirect .= 'window.location = "' . simcal_ga_campaign_url( simcal_get_url( 'add-ons' ), 'core-plugin', 'plugin-submenu-link', true ) . '"';
			$js_redirect .= '</script>';

			echo $js_redirect;

		}

	}

	/**
	 * Add sections.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_sections() {
		return array();
	}

	/**
	 * Add fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_fields() {
		return array();
	}

}
