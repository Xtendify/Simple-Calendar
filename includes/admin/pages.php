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
		<div class="wrap simcal-font-poppins semical-<?php echo $current_tab; ?>-tab" id="simcal-settings-page">
			<form id="simcal-settings-page-form"
			      method="post"
			      action="options.php">
				<?php

				// Include settings pages
				$settings_pages = self::get_settings();
				if ( ! empty( $settings_pages ) && is_array( $settings_pages ) ) {

					echo '<h2 class="nav-tab-wrapper simcal-nav-tab-wrapper simcal-flex simcal-space-x-[102px] simcal-bg-sc_blue-100 simcal-text-sc_grey-100 ">';

					// Get tabs for the settings page
					if ( ! empty( $settings_pages ) && is_array( $settings_pages ) ) {

						foreach ( $settings_pages as $id => $settings ) {

							$tab_id    = isset( $id ) ? $id : '';
							$tab_label = isset( $settings['label'] ) ? $settings['label'] : '';
							$tab_link  = admin_url( 'edit.php?post_type=calendar&page=simple-calendar_' . $this->page . '&tab=' . $tab_id );

							echo '<a href="' . $tab_link . '" class=" sc-link simcal-ml-[5%] simcal-text-base simcal-font-normal' . ( $current_tab == $tab_id ? ' nav-tab-active hover:simcal-bg-inherit simcal-bg-sc_blue-100 simcal-border-b-[3px] simcal-pb-[7px] simcal-pr-[7px] simcal-pl-[7px] simcal-border-b-sc_green-200 ' : '' ) . '">' . $tab_label . '</a>';
						}

					}

					do_action( 'simcal_admin_page_' . $this->page . '_tabs' );

					echo '</h2>';
					echo '<div class="simcal-max-w-[100%]">';
					echo '<div class="simcal-bg-sc_blue-100 ">';

					echo '<div class="simcal-flex simcal-pt-[80px] simcal-ml-[5%] ">';

					settings_errors();

					foreach ( $settings_pages as $tab_id => $contents ) {

						if ( $tab_id === $current_tab ) {
							echo '<div class="simcal-bg-white simcal-p-[2%] simcal-w-[54%] simcal-rounded-[5px] ">';

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
								<div class="simcal-w-[38%] simcal-h-[452px] simcal-ml-[2%] simcal-rounded-[5px] simcal-bg-white">
									<div class="simcal-mt-[15%]">					
										<img class="simcal-m-auto" src="<?php echo $admin_image_about_path . '/rating.png'; ?>" />							
									</div>
									<div class="simcal-mt-[15px] simcal-text-center simcal-font-semibold simcal-text-lg ">
										<Span><?php _e('Please Rate Us !','google-calendar-events')?></Span>
									</div>
									<div class="simcal-mt-[5px] simcal-text-center simcal-font-normal simcal-text-base simcal-text-gray-500">
										<Span><?php _e('If you like Simple Calendar please Rate Us','google-calendar-events')?> </Span>
									</div>
									<div class="simcal-mt-[44px]">
										<?php
										// Rating function is used here 
											sc_rating();
										?>				
									</div> 
									<a href="https://wordpress.org/support/plugin/google-calendar-events/reviews/?filter=5#new-post">
										<button type="button" class="simcal-mt-[20px] simcal-m-auto simcal-flex simcal-justify-center simcal-items-center simcal-w-[85%] simcal-h-[40px] simcal-bg-sc_green-200 simcal-text-white simcal-text-xl simcal-font-medium simcal-rounded-md">
										<?php _e('Rate Now','google-calendar-events')?>							
										</button>
									</a>
									<div class="simcal-mt-[25px] simcal-text-center simcal-text-base simcal-underline ">
										<a class="hover:simcal-text-sc_green-200" href="https://wordpress.org/support/plugin/google-calendar-events/reviews/?filter=5"><?php _e('See All Customers Reviews','google-calendar-events')?></a>
									</div>
								</div>
								<?php
								echo '</div>'; 
								}
							}
							// It will display when the Pro is activated.				
							if (! is_plugin_active( 'Simple-Calendar-Google-Calendar-Pro-main/simple-calendar-google-calendar-pro.php' )  )  {		
							?>
								<div class="simcal-pt-[41px] simcal-max-w-[100%] simcal-ml-[5%]">
									<div class="simcal-flex simcal-h-[430px] xl:simcal-h-[450px] ">
										<div class="simcal-w-[48%] simcal-flex simcal-relative simcal-bg-sc_green-100 simcal-rounded-l-[5px] ">
											<div class="simcal-pl-[37px] simcal-pt-[20px]">
												<img src="<?php echo $admin_image_about_path . '/pro-banner.png'; ?>" />
											</div>
											<div class="simcal-absolute simcal-right-[-15px] simcal-top-[120px]">
												<img src="<?php echo $admin_image_about_path . '/arrow.png'; ?>" />
											</div>	
										</div>
										<div class="simcal-w-[46%] simcal-pl-[5%] simcal-bg-sc_green-100 simcal-rounded-r-[5px] ">
											<div class="simcal-pr-[9%] simcal-mt-[66px]">
												<div class="simcal-flex">
													<div>
														<img src="<?php echo $admin_image_about_path . '/black-tick.png'; ?>" />
													</div>
													<div class="simcal-ml-[9px] simcal-text-xl simcal-font-semibold simcal-text-sc_green-200">
														<span><?php _e('Pro Version','google-calendar-events')?></span>
													</div>
												</div>
												<div class="simcal-mt-3 simcal-text-base simcal-text-gray-400 ">
													<span><?php _e('Calendars configured to use the','google-calendar-events')?><b class="simcal-text-sc_black-100 hover:simcal-text-sc_green-200"><?php _e(' Google Calendar Pro add-on','google-calendar-events')?></b> <?php _e('use a different method of authorization.','google-calendar-events')?></span>
												</div>
												<div class="simcal-flex simcal-items-center simcal-text-gray-400 simcal-mt-[21px]">
													<div>
														<img src="<?php echo $admin_image_about_path . '/green-tick.png'; ?>" />
													</div>
													<div class="simcal-ml-[9px] simcal-text-base simcal-text-gray-400 simcal-font-normal" >
														<span><?php _e('Display events from both private and public Google Calendars.','google-calendar-events')?></span>
													</div>
												</div>
												<div class="simcal-flex simcal-items-center simcal-mt-[14px] ">
													<div>
														<img src="<?php echo $admin_image_about_path . '/green-tick.png'; ?>" />
													</div>
													<div class="simcal-ml-[9px] simcal-text-base simcal-text-gray-400 simcal-font-normal " >
														<span> <?php _e('Display a list of attachments with links to their original source.','google-calendar-events')?></span>
													</div>
												</div>
												<div class="simcal-flex simcal-items-center simcal-mt-[14px] ">
													<div>
														<img src="<?php echo $admin_image_about_path . '/green-tick.png'; ?>" />
													</div>
													<div class="simcal-ml-[9px] simcal-text-base simcal-text-gray-400 simcal-font-normal">
														<span><?php _e('Many More...','google-calendar-events')?></span>
													</div>
												</div>
												<a href="https://simplecalendar.io/addons/?utm_source=inside-plugin&utm_medium=link&utm_campaign=core-plugin&utm_content=settings-link">
													<button type="button" class="simcal-mt-[45px] simcal-flex simcal-justify-center simcal-items-center simcal-w-[97%] simcal-h-[40px] simcal-bg-sc_green-200 simcal-text-white simcal-text-xl simcal-font-medium simcal-rounded-md">
														<img class="simcal-p-[8px]" src="<?php echo $admin_image_about_path . '/crown.png'; ?>" />
														<?php _e('Get Pro Version','google-calendar-events')?>						
													</button>
												</a>
											</div>
										</div>
									</div>
								</div>
							<?php						
						}	
					echo '</div>'; 						
					echo '</div>'; 						
				}
				?>
			</form>
		</div>
		<?php
	}
	
}
