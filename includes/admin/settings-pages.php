<?php
/**
 * Settings Pages
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

use SimpleCalendar\Abstracts\Field;
use SimpleCalendar\Abstracts\Settings_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings pages class.
 *
 * Handles settings pages and settings UI in admin dashboard.
 */
class Settings_Pages {

	/**
	 * Current settings page.
	 *
	 * @access private
	 * @var string
	 */
	private $page = '';

	/**
	 * Default tab.
	 *
	 * @access private
	 * @var string
	 */
	private $tab = '';

	/**
	 * Settings pages.
	 *
	 * @access private
	 * @var array
	 */
	private $settings = array();

	/**
	 * Constructor.
	 *
	 * @param string $page
	 */
	public function __construct( $page = 'settings' ) {

		$this->page = $page;
		$settings_pages = ! is_null( \SimpleCalendar\plugin()->objects ) ? simcal_get_settings_pages() : '';
		$settings_page_tabs = array();
		$tabs = isset( $settings_pages[ $page ] ) ? $settings_pages[ $page ] : false;

		if ( $tabs && is_array( $tabs ) ) {
			foreach ( $tabs as $tab ) {

				$settings_page = simcal_get_settings_page( $tab );

				if ( $settings_page instanceof Settings_Page ) {
					$settings_page_tabs[ $settings_page->id ] = $settings_page;
				}
			}

			$this->settings = $settings_page_tabs;
		}

		$this->tab = isset( $tabs[0] ) ? $tabs[0] : '';

		do_action( 'simcal_settings_pages', $page );
	}

	/**
	 * Get settings pages.
	 *
	 * @access private
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = array();

		if ( ! empty( $this->settings ) && is_array( $this->settings ) ) {
			foreach ( $this->settings as $id => $object ) {

				if ( $object instanceof Settings_Page ) {

					$settings_page = $object->get_settings();

					if ( isset( $settings_page[ $id ] ) ) {
						$settings[ $id ] = $settings_page[ $id ];
					}
				}

			}
		}

		return $settings;
	}

	/**
	 * Register settings.
	 *
	 * Adds settings sections and fields to settings pages.
	 *
	 * @param array $settings
	 */
	public function register_settings( $settings = array() ) {

		$settings = $settings ? $settings : $this->get_settings();

		if ( ! empty( $settings ) && is_array( $settings ) ) {

			foreach ( $settings as $tab_id => $settings_page ) {

				if ( isset( $settings_page['sections'] ) ) {

					$sections = $settings_page['sections'];

					if ( ! empty( $sections ) && is_array( $sections ) ) {

						foreach ( $sections as $section_id => $section ) {

							add_settings_section(
								$section_id,
								isset( $section['title'] ) ? $section['title'] : '',
								isset( $section['callback'] ) ? $section['callback'] : '',
								'simple-calendar_' . $this->page . '_' . $tab_id
							);

							if ( isset( $section['fields'] ) ) {

								$fields = $section['fields'];

								if ( ! empty( $fields ) && is_array( $fields ) ) {

									foreach ( $fields as $field ) {

										if ( isset( $field['id'] ) && isset( $field['type'] ) ) {

											$field_object = simcal_get_field( $field, $field['type'] );

											if ( $field_object instanceof Field ) {

												add_settings_field(
													$field['id'],
													isset( $field['title'] ) ? $field['title'] : '',
													array( $field_object, 'html' ),
													'simple-calendar_' . $this->page . '_' . $tab_id,
													$section_id
												);

											} // add field

										} // is field valid?

									} // loop fields

								} // are fields non empty?

							} // are there fields?

							$page = simcal_get_settings_page( $tab_id );

							register_setting(
								'simple-calendar_' . $this->page . '_' . $tab_id,
								'simple-calendar_' . $this->page . '_' . $tab_id,
								$page instanceof Settings_Page ? array( $page, 'validate' ) : ''
							);

						} // loop sections

					} // are sections non empty?

				} // are there sections?

			} // loop settings

		} // are there settings?

	}

	/**
	 * Print Settings Pages.
	 */
	public function html() {

		global $current_tab;

		// Get current tab/section
		$current_tab = empty( $_GET['tab'] ) ? $this->tab : sanitize_title( $_GET['tab'] );

		do_action( 'simcal_settings_page_start' );

		?>
		<div class="wrap" id="simcal-settings-page">
			<form id="simcal-settings-page-form"
			      method="post"
			      action="options.php">
				<?php

				// Include settings pages
				$settings_pages = self::get_settings();
				if ( ! empty( $settings_pages ) && is_array( $settings_pages ) ) {

					echo '<h2 class="nav-tab-wrapper simcal-nav-tab-wrapper">';

					// Get tabs for the settings page
					if ( ! empty( $settings_pages ) && is_array( $settings_pages ) ) {

						foreach ( $settings_pages as $id => $settings ) {

							$tab_id    = isset( $id ) ? $id : '';
							$tab_label = isset( $settings['label'] ) ? $settings['label'] : '';
							$tab_link  = admin_url( 'edit.php?post_type=calendar&page=simple-calendar_' . $this->page . '&tab=' . $tab_id );

							echo '<a href="' . $tab_link . '" class="nav-tab ' . ( $current_tab == $tab_id ? 'nav-tab-active' : '' ) . '">' . $tab_label . '</a>';
						}

					}

					do_action( 'simple-calendar_settings_tabs' );

					echo '</h2>';

					settings_errors();

					foreach ( $settings_pages as $tab_id => $contents ) {

						if ( $tab_id === $current_tab ) {

							echo isset( $contents['description'] ) ? '<p>' . $contents['description'] . '</p>' : '';

							settings_fields( 'simple-calendar_' . $this->page . '_' . $tab_id );
							do_settings_sections( 'simple-calendar_' . $this->page . '_' . $tab_id );
						}

					}

					do_action( 'simcal_settings_page', $this->page, $current_tab );
				}

				$submit = apply_filters( 'simcal_settings_page_submit', true );
				if ( true === $submit ) {
					submit_button();
				}

				?>
			</form>
		</div>
		<?php

		do_action( 'simcal_settings_page_end' );
	}

}
