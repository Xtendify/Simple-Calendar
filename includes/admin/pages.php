<?php
/**
 * Settings Pages
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

use SimpleCalendar\Abstracts\Field;
use SimpleCalendar\Abstracts\Admin_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin pages class.
 *
 * Handles settings pages and settings UI in admin dashboard.
 *
 * @since 3.0.0
 */
class Pages {

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
	 * @since 3.0.0
	 *
	 * @param string $page
	 */
	public function __construct( $page = 'settings' ) {

		$this->page = $page;
		$settings_pages = ! is_null( \SimpleCalendar\plugin()->objects ) ? simcal_get_admin_pages() : '';
		$settings_page_tabs = array();
		$tabs = isset( $settings_pages[ $page ] ) ? $settings_pages[ $page ] : false;

		if ( $tabs && is_array( $tabs ) ) {
			foreach ( $tabs as $tab ) {

				$settings_page = simcal_get_admin_page( $tab );

				if ( $settings_page instanceof Admin_Page ) {
					$settings_page_tabs[ $settings_page->id ] = $settings_page;
				}
			}

			$this->settings = $settings_page_tabs;
		}

		// The first tab is the default tab when opening a page.
		$this->tab = isset( $tabs[0] ) ? $tabs[0] : '';

		do_action( 'simcal_admin_pages', $page );
	}

	/**
	 * Get settings pages.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = array();

		if ( ! empty( $this->settings ) && is_array( $this->settings ) ) {
			foreach ( $this->settings as $id => $object ) {

				if ( $object instanceof Admin_Page ) {

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
	 * @since 3.0.0
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

							$page = simcal_get_admin_page( $tab_id );

							register_setting(
								'simple-calendar_' . $this->page . '_' . $tab_id,
								'simple-calendar_' . $this->page . '_' . $tab_id,
								$page instanceof Admin_Page ? array( $page, 'validate' ) : ''
							);

						} // loop sections

					} // are sections non empty?

				} // are there sections?

			} // loop settings

		} // are there settings?

	}

	/**
	 * Print Settings Pages.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		global $current_tab;
		$admin_image_about_path = SIMPLE_CALENDAR_ASSETS . '/images';

		// Get current tab/section
		$current_tab = empty( $_GET['tab'] ) ? $this->tab : sanitize_title( $_GET['tab'] );
		$this->tab = $current_tab;

		?>
		<div class="wrap bg-neutral-100 font-sc_popi" id="simcal-settings-page">
			<form id="simcal-settings-page-form"
			      method="post"
			      action="options.php">
				<?php

				// Include settings pages
				$settings_pages = self::get_settings();
				if ( ! empty( $settings_pages ) && is_array( $settings_pages ) ) {

					echo '<h2 class="nav-tab-wrapper simcal-nav-tab-wrapper flex space-x-[102px] bg-sc_blue-100">';

					// Get tabs for the settings page
					if ( ! empty( $settings_pages ) && is_array( $settings_pages ) ) {

						foreach ( $settings_pages as $id => $settings ) {

							$tab_id    = isset( $id ) ? $id : '';
							$tab_label = isset( $settings['label'] ) ? $settings['label'] : '';
							$tab_link  = admin_url( 'edit.php?post_type=calendar&page=simple-calendar_' . $this->page . '&tab=' . $tab_id );

							echo '<a href="' . $tab_link . '" class=" ml-[100px] text-base font-normal text-sc_grey-100' . ( $current_tab == $tab_id ? 'nav-tab-active ml-[100px] text-base font-normal bg-sc_blue-100 border-b-[3px]  pb-[7px] pr-[7px] pl-[7px] border-b-sc_green-200 nav-tab-active' : '' ) . '">' . $tab_label . '</a>';
						}

					}

					do_action( 'simcal_admin_page_' . $this->page . '_tabs' );

					echo '</h2>';
					echo '<div class="bg-sc_blue-100">';

					echo '<div class="flex pt-[114px]">';

					settings_errors();

					foreach ( $settings_pages as $tab_id => $contents ) {

						if ( $tab_id === $current_tab ) {
							echo '<div class="bg-white p-[30px] w-[795px] ml-[102px] ">';

							echo isset( $contents['description'] ) ? '<p>' . $contents['description'] . '</p>' : '';

							do_action( 'simcal_admin_page_' .  $this->page . '_' . $current_tab . '_start' );

							settings_fields( 'simple-calendar_' . $this->page . '_' . $tab_id );
							do_settings_sections( 'simple-calendar_' . $this->page . '_' . $tab_id );

							do_action( 'simcal_admin_page_' .  $this->page . '_' . $current_tab . '_end' );

							$submit = apply_filters( 'simcal_admin_page_' . $this->page . '_' . $current_tab . '_submit', true );
							if ( true === $submit ) {
								submit_button();
							}
							echo '</div>'; 
								?>
									<div class="w-[481px] h-[366px] ml-[25px] bg-white">
									<div>					
										<img class="m-auto mt-[19px]" src="<?php echo $admin_image_about_path . '/rating.png'; ?>" />							
									</div>
									<div class=" mt-[15px] text-center font-semibold text-lg">
										<Span><?php _e('Please Rate Us !','google-calendar-events')?></Span>
									</div>
									<div class=" mt-[5px] text-center font-normal text-base text-gray-500">
										<Span><?php _e('If you like Simple Calendar please Rate Us','google-calendar-events')?> </Span>
									</div>
									<div class="mt-[44px]">
										<?php
										// Rating function is used here 
											sc_rating();
										?>				
									</div> 
									<a href="https://wordpress.org/support/plugin/google-calendar-events/reviews/?filter=5#new-post">
										<button type="button" class="mt-[20px] m-auto flex justify-center items-center w-[405px] h-[40px] bg-sc_green-200 text-white text-xl font-medium rounded-md">
										<?php _e('Rate Now','google-calendar-events')?>							
										</button>
									</a>
									<div class="mt-[25px] text-center text-base text-sc_blue-200 hover:underline">
										<a href="https://wordpress.org/support/plugin/google-calendar-events/reviews/?filter=5"><?php _e('See All Rating 4 Out of 5 Stars','google-calendar-events')?></a>
									</div>
								</div>
								<?php
								echo '</div>'; 
								}
							}
							// It will display when the Pro is deactivated.				
							if (! is_plugin_active( 'Simple-Calendar-Google-Calendar-Pro/simple-calendar-google-calendar-pro.php' )  )  {		
							?>
								<div class="bg-sc_green-100 h-[399px] w-[1302px] ml-[102px] mt-[41px] flex max-xl:ml-[0px]">
									<div class="w-[654px] flex relative ">
										<div class="ml-[37px] mt-[18px]">
											<img src="<?php echo $admin_image_about_path . '/pro-banner.png'; ?>" />
										</div>
										<div class="absolute right-0 top-28">
											<img src="<?php echo $admin_image_about_path . '/arrow.png'; ?>" />
										</div>	
									</div>
									<div class="w-[639px] pl-[47px]">
										<div class="mt-[38px] flex ">
											<div>
												<img src="<?php echo $admin_image_about_path . '/black-tick.png'; ?>" />
											</div>
											<div class="ml-[9px] text-xl font-semibold text-sc_green-200">
												<span><?php _e('Pro Version','google-calendar-events')?></span>
											</div>
										</div>
										<div class=" mt-3 text-base text-gray-400 ">
											<span><?php _e('Calendars configured to use the','google-calendar-events')?><b class="text-black"><?php _e(' Google Calendar Pro add-on','google-calendar-events')?></b> <?php _e('use a different method of authorization.','google-calendar-events')?></span>
										</div>
										<div class="flex items-center text-gray-400 mt-[21px]">
											<div>
												<img src="<?php echo $admin_image_about_path . '/green-tick.png'; ?>" />
											</div>
											<div class="ml-[9px] text-base text-gray-400 font-normal" >
												<span><?php _e('Display events from both private and public Google Calendars.','google-calendar-events')?></span>
											</div>
										</div>
										<div class="flex items-center mt-[14px] ">
											<div>
												<img src="<?php echo $admin_image_about_path . '/green-tick.png'; ?>" />
											</div>
											<div class="ml-[9px] text-base text-gray-400 font-normal " >
												<span> <?php _e('Lorem ipsum dolor sit amet, consectetur adipiscing elit','google-calendar-events')?></span>
											</div>
										</div>
										<div class="flex items-center mt-[14px] ">
											<div>
												<img src="<?php echo $admin_image_about_path . '/green-tick.png'; ?>" />
											</div>
											<div class="ml-[9px] text-base text-gray-400 font-normal">
												<span><?php _e('Many More...','google-calendar-events')?></span>
											</div>
										</div>
										<a href="https://simplecalendar.io/downloads/google-calendar-pro/">
											<button type="button" class="mt-[45px] flex justify-center items-center w-[533px] h-[40px] bg-sc_green-200 text-white text-xl font-medium rounded-md">
												<img class="p-[8px]" src="<?php echo $admin_image_about_path . '/crown.png'; ?>" />
												<?php _e('Get Pro Version','google-calendar-events')?>						
											</button>
										</a>
									</div>
								</div>
							<?php						
						}	
					echo '</div>'; 						
				}
				?>
			</form>
		</div>
		<?php
	}

}
