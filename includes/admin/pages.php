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

							echo '<a href="' . $tab_link . '" class=" ml-[100px] text-base font-normal text-sc_grey-100' . ( $current_tab == $tab_id ? 'nav-tab-active ml-[100px] text-base font-normal bg-sc_blue-100 border-b-[3px]  pb-[7px] pr-[7px] pl-[7px] border-b-sc_green-200' : '' ) . '">' . $tab_label . '</a>';
						}

					}

					do_action( 'simcal_admin_page_' . $this->page . '_tabs' );

					echo '</h2>';
					echo '<div class="bg-sc_blue-100">';

					echo '<div class=" flex pt-[114px]  " >';
					

					settings_errors();

					foreach ( $settings_pages as $tab_id => $contents ) {

						if ( $tab_id === $current_tab ) {

							echo '<div class="bg-white p-[30] w-[795px] ml-[102px] h-[372px] ">';

							echo isset( $contents['description'] ) ? '<p>' . $contents['description'] . '</p>' : '';

							do_action( 'simcal_admin_page_' .  $this->page . '_' . $current_tab . '_start' );

							settings_fields( 'simple-calendar_' . $this->page . '_' . $tab_id );
							do_settings_sections( 'simple-calendar_' . $this->page . '_' . $tab_id  );

							do_action( 'simcal_admin_page_' .  $this->page . '_' . $current_tab . '_end' );
							
							$submit = apply_filters( 'simcal_admin_page_' . $this->page . '_' . $current_tab . '_submit', true );
							if ( true === $submit ) {
								submit_button();
							}
							echo '</div>'; 
							
						}
					}
					?>
					<div class="w-[481px] h-[360px] ml-[25px] bg-white" >
						<div >					
							<img class="m-auto mt-[19px]" src="<?php echo $admin_image_about_path . '/top-rated 1.png'; ?>" />							
						</div>
						<div class=" mt-[15px] text-center font-semibold text-lg">
							<Span>Please Rate Us !</Span>
						</div>
						<div class=" mt-[5px] text-center font-normal text-base text-gray-500">
							<Span>If you like Simple Calendar please Rate Us </Span>
						</div>
						<div class="mt-[44px]">
							<div class='rating flex flex-row justify-center gap-3'>							
								<svg class="h-6 transition-all duration-100 fill-gray-400  fill-yellow-200  cursor-pointer"
									viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg"
									xmlns:xlink="http://www.w3.org/1999/xlink">
									<path
										d="M575.852903 115.426402L661.092435 288.054362c10.130509 20.465674 29.675227 34.689317 52.289797 37.963825l190.433097 27.62866c56.996902 8.288598 79.7138 78.281203 38.475467 118.496253l-137.836314 134.35715c-16.372539 15.963226-23.84251 38.987109-19.954032 61.49935l32.540421 189.716799c9.721195 56.792245-49.833916 100.077146-100.793444 73.267113L545.870691 841.446188a69.491196 69.491196 0 0 0-64.67153 0l-170.376737 89.537324c-50.959528 26.810033-110.51464-16.474868-100.793444-73.267113L242.569401 667.9996c3.888478-22.512241-3.581493-45.536125-19.954032-61.49935L84.779055 472.245428c-41.238333-40.215049-18.521435-110.207655 38.475467-118.496252l190.433097-27.62866c22.61457-3.274508 42.159288-17.498151 52.289797-37.963826L451.319277 115.426402c25.479764-51.675827 99.053862-51.675827 124.533626 0z"
										></path>
								</svg>
								<svg class="h-6 transition-all duration-100 fill-gray-400  cursor-pointer"
									viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg"
									xmlns:xlink="http://www.w3.org/1999/xlink">
									<path
										d="M575.852903 115.426402L661.092435 288.054362c10.130509 20.465674 29.675227 34.689317 52.289797 37.963825l190.433097 27.62866c56.996902 8.288598 79.7138 78.281203 38.475467 118.496253l-137.836314 134.35715c-16.372539 15.963226-23.84251 38.987109-19.954032 61.49935l32.540421 189.716799c9.721195 56.792245-49.833916 100.077146-100.793444 73.267113L545.870691 841.446188a69.491196 69.491196 0 0 0-64.67153 0l-170.376737 89.537324c-50.959528 26.810033-110.51464-16.474868-100.793444-73.267113L242.569401 667.9996c3.888478-22.512241-3.581493-45.536125-19.954032-61.49935L84.779055 472.245428c-41.238333-40.215049-18.521435-110.207655 38.475467-118.496252l190.433097-27.62866c22.61457-3.274508 42.159288-17.498151 52.289797-37.963826L451.319277 115.426402c25.479764-51.675827 99.053862-51.675827 124.533626 0z"
										></path>
								</svg>
								<svg class="h-6 transition-all duration-100 fill-gray-400  cursor-pointer"
									viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg"
									xmlns:xlink="http://www.w3.org/1999/xlink">
									<path
										d="M575.852903 115.426402L661.092435 288.054362c10.130509 20.465674 29.675227 34.689317 52.289797 37.963825l190.433097 27.62866c56.996902 8.288598 79.7138 78.281203 38.475467 118.496253l-137.836314 134.35715c-16.372539 15.963226-23.84251 38.987109-19.954032 61.49935l32.540421 189.716799c9.721195 56.792245-49.833916 100.077146-100.793444 73.267113L545.870691 841.446188a69.491196 69.491196 0 0 0-64.67153 0l-170.376737 89.537324c-50.959528 26.810033-110.51464-16.474868-100.793444-73.267113L242.569401 667.9996c3.888478-22.512241-3.581493-45.536125-19.954032-61.49935L84.779055 472.245428c-41.238333-40.215049-18.521435-110.207655 38.475467-118.496252l190.433097-27.62866c22.61457-3.274508 42.159288-17.498151 52.289797-37.963826L451.319277 115.426402c25.479764-51.675827 99.053862-51.675827 124.533626 0z"
										></path>
								</svg>
								<svg class="h-6 transition-all duration-100 fill-gray-400  cursor-pointer"
									viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg"
									xmlns:xlink="http://www.w3.org/1999/xlink">
									<path
										d="M575.852903 115.426402L661.092435 288.054362c10.130509 20.465674 29.675227 34.689317 52.289797 37.963825l190.433097 27.62866c56.996902 8.288598 79.7138 78.281203 38.475467 118.496253l-137.836314 134.35715c-16.372539 15.963226-23.84251 38.987109-19.954032 61.49935l32.540421 189.716799c9.721195 56.792245-49.833916 100.077146-100.793444 73.267113L545.870691 841.446188a69.491196 69.491196 0 0 0-64.67153 0l-170.376737 89.537324c-50.959528 26.810033-110.51464-16.474868-100.793444-73.267113L242.569401 667.9996c3.888478-22.512241-3.581493-45.536125-19.954032-61.49935L84.779055 472.245428c-41.238333-40.215049-18.521435-110.207655 38.475467-118.496252l190.433097-27.62866c22.61457-3.274508 42.159288-17.498151 52.289797-37.963826L451.319277 115.426402c25.479764-51.675827 99.053862-51.675827 124.533626 0z"
										></path>
								</svg>
								<svg class="h-6 transition-all duration-100 fill-gray-400  cursor-pointer"
									viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg"
									xmlns:xlink="http://www.w3.org/1999/xlink">
									<path
										d="M575.852903 115.426402L661.092435 288.054362c10.130509 20.465674 29.675227 34.689317 52.289797 37.963825l190.433097 27.62866c56.996902 8.288598 79.7138 78.281203 38.475467 118.496253l-137.836314 134.35715c-16.372539 15.963226-23.84251 38.987109-19.954032 61.49935l32.540421 189.716799c9.721195 56.792245-49.833916 100.077146-100.793444 73.267113L545.870691 841.446188a69.491196 69.491196 0 0 0-64.67153 0l-170.376737 89.537324c-50.959528 26.810033-110.51464-16.474868-100.793444-73.267113L242.569401 667.9996c3.888478-22.512241-3.581493-45.536125-19.954032-61.49935L84.779055 472.245428c-41.238333-40.215049-18.521435-110.207655 38.475467-118.496252l190.433097-27.62866c22.61457-3.274508 42.159288-17.498151 52.289797-37.963826L451.319277 115.426402c25.479764-51.675827 99.053862-51.675827 124.533626 0z"></path>
								</svg>
							</div>
						
						<script>
							const svgs = document.querySelector('.rating').children;
							for(let i = 0;i<svgs.length;i++){ 
								svgs[i].onclick = ()=>{
									for(let j = 0;j<=i;j++){
										svgs[j].classList.add("fill-yellow-200"); // this class should be added to whitelist while in production mode
									}
									for(let k = i + 1;k<svgs.length;k++){
										svgs[k].classList.remove("fill-yellow-200"); // this class should be added to whitelist while in production mode
									}
								}
							}
						</script>
						</div> 
						<button type="button" class="mt-[20px] m-auto flex justify-center items-center w-[405px] h-[40px] bg-sc_green-200 text-white text-xl font-medium rounded-md">
							Rate Now							
						</button>
						<div class=" mt-[25px] text-center text-base text-sc_blue-200 hover:underline">
							<a href="">See All Rating 3.8 Out of 5 Stars</a>
						</div>
					</div>
					<?php
					echo '</div>';
				
				
				?>
				<div class="bg-sc_green-100 h-[399] w-[1302] ml-[102] mt-[41] flex max-xl:ml-[0px]">
					<div class="w-[654] flex relative ">
						<div class="ml-[37px] mt-[18px]">
							<img src="<?php echo $admin_image_about_path . '/image1.png'; ?>" />
						</div>
						<div class="absolute right-0 top-28">
							<img src="<?php echo $admin_image_about_path . '/Group 12.png'; ?>" />
						</div>	
					</div>
					<div class="w-[639] pl-[47px]">
						<div class="mt-[38] flex ">
							<div class="">
								<img src="<?php echo $admin_image_about_path . '/Vector (1).png'; ?>" />
							</div>
							<div class=" ml-[9px] text-xl font-semibold text-sc_green-200" >
								<span>Pro Version</span>
							</div>
						</div>
						<div class=" mt-3 text-base text-gray-400 ">
							<span>Calendars configured to use the <b class="text-black">Google Calendar Pro add-on</b> use a different method of authorization.</span>
						</div>
						<div class="flex items-center text-gray-400 mt-[21px]">
							<div class="">
								<img src="<?php echo $admin_image_about_path . '/Vector (2).png'; ?>" />
							</div>
							<div class="ml-[9px] text-base text-gray-400 font-normal" >
								<span>Display events from both private and public Google Calendars.</span>
							</div>
						</div>
						<div class="flex items-center mt-[14px] ">
							<div class="">
								<img src="<?php echo $admin_image_about_path . '/Vector (2).png'; ?>" />
							</div>
							<div class="ml-[9px] text-base text-gray-400 font-normal " >
								<span>Lorem ipsum dolor sit amet, consectetur adipiscing elit </span>
							</div>
						</div>
						<div class="flex items-center mt-[14px] ">
							<div class="">
								<img src="<?php echo $admin_image_about_path . '/Vector (2).png'; ?>" />
							</div>
							<div class="ml-[9px] text-base text-gray-400 font-normal " >
								<span>Many More...</span>
							</div>
						</div>
						<button type="button" class="mt-[45px] flex justify-center items-center w-[533px] h-[40px] bg-sc_green-200 text-white text-xl font-medium rounded-md">
							<img class="p-[8px]" src="<?php echo $admin_image_about_path . '/Vector.png'; ?>" />
							Get Pro Version							
						</button>
					</div>
				</div>
				<?php
				
				echo '</div>';
				}
				?>
			</form>
		</div>

		<?php

	}

}

?>

