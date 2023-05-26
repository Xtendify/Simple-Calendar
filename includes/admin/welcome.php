<?php
/**
 * Welcome Page Class
 *
 * Adapted from analogue code found in WoCommerce, EDD and WordPress itself.
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Welcome page.
 *
 * Shows a feature overview for the new version (major) and credits.
 *
 * @since 3.0.0
 */
class Welcome {

	/**
	 * Install type.
	 *
	 * @access public
	 * @var array
	 */
	public $install = '';

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->install = isset( $_GET['simcal_install'] ) ? esc_attr( $_GET['simcal_install'] ) : '';

		add_action( 'admin_menu', array( $this, 'welcome_page_tabs' ) );
		add_action( 'admin_head', array( $this, 'remove_submenu_pages' ) );
	}

	/**
	 * Add page screens.
	 *
	 * @since 3.0.0
	 */
	public function welcome_page_tabs() {

		$welcome_page_name  = __( 'About Simple Calendar', 'google-calendar-events' );
		$welcome_page_title = __( 'Welcome to Simple Calendar', 'google-calendar-events' );

		$page = isset( $_GET['page'] ) ? $_GET['page'] : 'simple-calendar_about';

		switch ( $page ) {

			case 'simple-calendar_about' :
				$page = add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'simple-calendar_about', array(
					$this,
					'about_screen',
				) );
				break;

			case 'simple-calendar_credits' :
				$page = add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'simple-calendar_credits', array(
					$this,
					'credits_screen',
				) );
				break;

			case 'simple-calendar_translators' :
				$page = add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'simple-calendar_translators', array(
					$this,
					'translators_screen',
				) );
				break;
		}
	}

	/**
	 * Remove dashboard page links.
	 *
	 * @since 3.0.0
	 */
	public function remove_submenu_pages() {
		remove_submenu_page( 'index.php', 'simple-calendar_about' );
		remove_submenu_page( 'index.php', 'simple-calendar_credits' );
		remove_submenu_page( 'index.php', 'simple-calendar_translators' );
	}

	/**
	 * Main nav links at top & bottom.
	 *
	 * @since 3.0.0
	 */
	public function main_nav_links() {

		?>
		<div class="font-poppins font-medium">
			<a href="<?php echo admin_url( 'edit.php?post_type=calendar' ); ?>"
			   class="button button-primary"
				><?php _e( 'Calendars', 'google-calendar-events' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( 'page', 'simple-calendar_settings', admin_url( 'admin.php' ) ) ); ?>"
			   class="button button-primary "
				><?php _e( 'Settings', 'google-calendar-events' ); ?></a>
			<a href="<?php echo simcal_ga_campaign_url( simcal_get_url( 'addons' ), 'core-plugin', 'welcome-page' ); ?>"
			   class="docs button button-primary" target="_blank"
				><?php _e( 'Add-ons', 'google-calendar-events' ); ?></a>
			<a href="<?php echo simcal_ga_campaign_url( simcal_get_url( 'docs' ), 'core-plugin', 'welcome-page' ); ?>"
			   class="docs button button-primary" target="_blank"
				><?php _e( 'Documentation', 'google-calendar-events' ); ?></a>
	</div>
		<?php

	}
	/**
	 * nav links at top .
	 *
	 * @since 3.0.0
	 */
	public function nav_links() {
		?>
		<h2 class="nav-tab-wrapper text-sc_grey-100 font-poppins">
			<a class="nav-tab <?php if ( $_GET['page'] == 'simple-calendar_about' ) {
				echo 'nav-tab-active ';
			} ?>"
			   href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_about' ), 'index.php' ) ) ); ?>"
				><?php _e( "What's New", 'google-calendar-events' ); ?></a>
			<a class="nav-tab <?php if ( $_GET['page'] == 'simple-calendar_credits' ) {
				echo 'nav-tab-active ';
			} ?>"
			   href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_credits' ), 'index.php' ) ) ); ?>"
				><?php _e( 'Credits', 'google-calendar-events' ); ?></a>
			<a class="nav-tab <?php if ( $_GET['page'] == 'simple-calendar_translators' ) {
				echo 'nav-tab-active';
			} ?>"
			   href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_translators' ), 'index.php' ) ) ); ?>"
				><?php _e( 'Translators', 'google-calendar-events border-b-[3px]' ); ?></a>
		</h2>	
		<?php

	}
	/**
	 * intro section .
	 *
	 * @since 3.0.0
	 */
	public function sc_intro_section() {
		$welcome_image_about_path = SIMPLE_CALENDAR_ASSETS . '/images/welcome';
		?>		
		<div class="mt-[100px] h-[408px] border-2 relative bg-sc_banner-bg rounded-[20px]">
					<div class="pl-[62px] pt-[61px] max-w-[745px] text-white ">
						<?php $this->intro(); ?>
					</div>
					<div class="">
						<div class=" w-[409px] h-[237px] absolute right-0 top-[-35px]">
						<img src="<?php echo $welcome_image_about_path . '/cal-meeting.png'; ?>" />	
						</div>
						<div Class="absolute h-[78px] right-[35%] rounded-full bg-white ">
							<img class="max-w-[61px] max-h-[47px]" src="<?php echo $welcome_image_about_path . '/cal-icon.png'; ?>" />	
						</div>
						<div Class=" absolute bg-white w-[359px] h-[156px] right-[37%] bottom-[-20%] flex rounded-[15px]">
							<div class="pl-[30px] pt-[30px]">
								<img class="max-w-[75px] max-h-[75px] " src="<?php echo $welcome_image_about_path . '/review-img.png'; ?>" />
							</div>
							<div class="pl-[30px] pt-[30px] font-poppins">
								<div class="text-lg font-semibold">
									<?php _e( 'Suke Tran', 'google-calendar-events' ); ?>
								</div>
								<div>
									<?php _e( 'excellent plugin with additional features', 'google-calendar-events' ); ?>
								</div>
								<div class="pr-[60px]">
									<?php sc_rating(); ?>
								</div>
							</div>
						</div>
						
					</div>
		</div>
		<?php

	}

	/**
	 * Intro shown on every about page screen.
	 *
	 * @since 3.0.0
	 */
	private function intro() {

		?>		
		<div class="font-poppins font-bold text-4xl">
			<?php
			/* translators: %s prints the current version of the plugin. */
			printf( __( 'Welcome to Simple Calendar %s', 'google-calendar-events' ), SIMPLE_CALENDAR_VERSION );
			?>
		</div>

		<div class="about-text font-poppins ">
			<?php

			// Difference message if updating vs fresh install.
			if ( 'update' == $this->install ) {
				$message = __( 'Thanks for updating to the latest version!', 'google-calendar-events' );
			} else {
				$message = __( 'Thanks for installing!', 'google-calendar-events' );
			}

			echo $message;

			/* translators: %s prints the current version of the plugin. */
			printf( ' ' . __( "Simple Calendar %s has a few display options to configure. ", 'google-calendar-events' ), SIMPLE_CALENDAR_VERSION );
			?>
			<a href="<?php echo simcal_ga_campaign_url( simcal_get_url( 'docs' ), 'core-plugin', 'welcome-page' ); ?>"
			   target="_blank"
			><br><?php _e( 'Check out our documentation', 'google-calendar-events' ); ?></a>
			<?php _e( 'to get started now.', 'google-calendar-events' ); ?>
		</div>

		<!-- <div class="simcal-badge">&nbsp;</div> -->
		<div class="pt-[54px]">
		<?php $this->main_nav_links(); ?>
		</div>
			
		<?php

	}

	/**
	 * Output the about screen.
	 *
	 * @since 3.0.0
	 */
	public function about_screen() {
		$welcome_image_about_path = SIMPLE_CALENDAR_ASSETS . '/images/welcome';
		$welcome_addons_link = simcal_ga_campaign_url( simcal_get_url( 'addons' ), 'core-plugin', 'welcome-page' );
		?>
		<div id="simcal-welcome">
			<div class="wrap about-wrap whats-new-wrap max-w-[1606px]">
				<?php $this->nav_links(); ?>				
				<?php $this->sc_intro_section(); ?>				
				
				<div class="grid gap-x-10 grid-cols-3 mt-[170px] font-poppins">
					<div class="h-[436px] bg-sc_cream-100 text-white pt-[52px] pl-[33px] pr-[15px] rounded-[15px] ">
						<span class="text-[24px] font-bold leading-[31px]"><?php _e( 'Configure event colors, number of events to display, grid or list style', 'google-calendar-events' ); ?></span>
						<span class="text-[24px]"> <?php _e( ' and more.', 'google-calendar-events' ); ?></span>
						<img src="<?php echo $welcome_image_about_path . '/cal-meeting.png'; ?>" />
						
					</div>
					<div class="h-[436px] bg-sc_green-200 text-white pt-[52px] pl-[33px] rounded-[15px]">
						<span class="text-[24px] font-bold leading-[31px] "><?php _e( 'Mobile responsive and ', 'google-calendar-events' ); ?></span>						
						<span class="text-[24px]"> <?php _e( 'widget ready.', 'google-calendar-events' ); ?></span>	
						<img src="<?php echo $welcome_image_about_path . '/jan-cal.png'; ?>" />												
					</div>
					<div class="h-[436px] bg-sc_blue-300 text-white pt-[52px] pl-[33px] rounded-[15px]">
						<span class="text-[24px] font-bold leading-[31px] "><?php _e( 'Add even more display options with add-ons like', 'google-calendar-events' ); ?> </span>
						<span class="text-[24px] "> <?php _e( 'Full Calendar and Google Calendar Pro.', 'google-calendar-events' ); ?></span>
						<img src="<?php echo $welcome_image_about_path . '/mar-cal.png'; ?>" />	
					</div>
				</div>
				<div class="mt-[50px] max-w-[1606px] h-[266px] pl-[62px] pt-[45px] flex bg-sc_green-100 font-poppins rounded">
					<div>
						<div class="flex">
							<div class="">
								<img src="<?php echo $welcome_image_about_path . '/bl-crown.png'; ?>" />
							</div>
							<div class="pl-[17px] font-semibold text-[16px]">
								<?php _e( 'View Pricing and Try a Demo of our', 'google-calendar-events' ); ?>
								<span class="text-sc_green-200"><?php _e( 'Simple Calendar Pro Add-ons.', 'google-calendar-events' ); ?></span>
							</div>
						</div>
						<div class="flex items-center mt-[34px] ">
							<div>
								<img src="<?php echo $welcome_image_about_path . '/green-tick.png'; ?>" />
							</div>
							<div class="ml-[9px] text-base text-gray-400 font-normal">
								<span><?php _e('Display events from both private and public Google Calendars.','google-calendar-events')?></span>
							</div>
						</div>
						<div class="flex items-center mt-[14px] ">
							<div >
								<img src="<?php echo $welcome_image_about_path . '/green-tick.png'; ?>" />
							</div>
							<div class="ml-[9px] text-base text-gray-400 font-normal">
								<span><?php _e('Lorem ipsum dolor sit amet, consectetur adipiscing elit','google-calendar-events')?></span>
							</div>
						</div>
						<div class="flex items-center mt-[14px] ">
							<div>
								<img src="<?php echo $welcome_image_about_path . '/green-tick.png'; ?>" />
							</div>
							<div class="ml-[9px] text-base text-gray-400 font-normal">
								<span><?php _e('Many More...','google-calendar-events')?></span>
							</div>
						</div>
					</div>
					<div class="m-auto">
						<a href="https://simplecalendar.io/downloads/google-calendar-pro/">
							<button type="button" class="flex justify-center items-center w-[377px] h-[40px] bg-sc_green-200 text-white text-base font-medium rounded-md font-poppins">
								<img class="p-[8px]" src="<?php echo $welcome_image_about_path . '/crown.png'; ?>" />
								<?php _e('Get Pro Version','google-calendar-events')?>						
							</button>
						</a>
					</div>
				</div>
			</div>			
		</div>
		<?php

	}

	/**
	 * Output the credits screen.
	 *
	 * @since 3.0.0
	 */
	public function credits_screen() {
		$welcome_image_about_path = SIMPLE_CALENDAR_ASSETS . '/images/welcome';

		?>	
		<div id="simcal-welcome">
			<div class="wrap about-wrap credits-wrap max-w-[1606px] font-poppins">
			<?php $this->nav_links(); ?>				
				<?php $this->sc_intro_section(); ?>	
				<div class="mt-[54px] max-w-[1606px] h-[auto] bg-sc_green-100 rounded-[20px] pl-[74px]">
					<div class="pt-[62px] font-bold text-xl">
						<?php _e("Simple Calendar is created by a worldwide team of developers. If you'd ",'google-calendar-events')?>	
					</div>
					<div class="text-xl">
						<?php _e("like to contribute please visit our <a href='%s' class='text-sc_green-200' target='_blank'>GitHub repo </a>",'google-calendar-events')?>	
					</div>
					<div class="flex flex-row pt-[54px]">
						<div class="basis-3/5">
							<?php
							  echo $this->contributors();
							?>
						</div>
						<div class="basis-2/5">
							
							<div class="mt-[54px] mr-[118px] w-[481px] h-[366px] rounded-[20px] bg-white ">
								<div class="pl-[42%] " >					
									<img class="pt-[19px]" src="<?php echo $welcome_image_about_path . '/wel-rating.png'; ?>" />							
								</div>
								<div class=" mt-[15px] text-center font-semibold text-lg ">
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
						
						</div>
					</div>
				</div>
				<p class="about-description">
					<?php

					printf(
						__( "", 'google-calendar-events' ),
						simcal_get_url( 'github' )
					);

					?>
				</p>
				
			</div>
		</div>
		<?php

	}

	/**
	 * Output the translators screen.
	 *
	 * @since 3.0.0
	 */
	public function translators_screen() {

		?>
		<div id="simcal-welcome">
			<div class="wrap about-wrap translators-wrap max-w-[1606px] fonlat-poppins">
				<?php $this->nav_links(); ?>
				<?php $this->sc_intro_section(); ?>
				<div class="font-bold text-xl mt-[31px]">
					<span><?php _e( 'Simple Calendar has been kindly translated into several other ', 'google-calendar-events' ); ?></span>
				</div>
				<div class=" text-xl">
					<span><?php _e( 'languages by contributors from all over the world.', 'google-calendar-events' ); ?></span>
				</div>
				<div class="mt-[42px]">
						<a href="https://translate.wordpress.org/projects/wp-plugins/google-calendar-events">
							<button type="button" class=" items-center w-[257px] h-[40px] bg-sc_green-200 text-white text-base rounded-[7px] font-poppins">
								<?php _e('Click here to help translate','google-calendar-events')?>						
							</button>
						</a>
					</div>
				<?php

				// Transifex API is not open and requires authentication,
				// Otherwise something like this would be possible:
				// `json_decode( 'https://www.transifex.com/api/2/project/simple-calendar/languages/', true );`
				// Since this is not possible, this has to be done manually.

				// @TODO switch to WordPress language packs and try to pull list of translators from there

				?>
			</div>
		</div>
		<?php

	}

	/**
	 * Render Contributors List.
	 *
	 * @since  3.0.0
	 *
	 * @return string $contributor_list HTML formatted list of contributors.
	 */
	public function contributors() {

		$contributors = $this->get_contributors();

		if ( empty( $contributors ) ) {
			return '';
		}

		$contributor_list = '<div class="wp-people-group">';

		foreach ( $contributors as $contributor ) {

			// Skip contributor bots
			$contributor_bots = array( 'gitter-badger' );
			if ( in_array( $contributor->login, $contributor_bots ) ) {
				continue;
			}

			$contributor_list .= '<div class="wp-person mt-[43px]">';
			$contributor_list .= sprintf(
				'<a href="%s" title="%s" target="_blank">%s</a>',
				esc_url( 'https://github.com/' . $contributor->login ),
				esc_html( sprintf( __( 'View %s', 'google-calendar-events' ), $contributor->login ) ),
				sprintf( '<img src="%s" width="50px" height="50px" class="gravatar" alt="%s" />', esc_url( $contributor->avatar_url ), esc_html( $contributor->login ) )
			);
			$contributor_list .= sprintf(
				'<a class="web" href="%s" target="_blank">%s</a>',
				esc_url( 'https://github.com/' . $contributor->login ),
				esc_html( $contributor->login )
			);
			$contributor_list .= '</div>';

		}

		$contributor_list .= '</div>';

		return $contributor_list;
	}

	/**
	 * Retrieve list of contributors from GitHub.
	 *
	 * @since  3.0.0
	 *
	 * @return mixed
	 */
	public function get_contributors() {

		$contributors = get_transient( '_simple-calendar_contributors' );
		if ( false !== $contributors ) {
			return $contributors;
		}

		$response = wp_safe_remote_get(
			'https://api.github.com/repos/Xtendify/Simple-Calendar/contributors'
		);
		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$contributors = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! is_array( $contributors ) ) {
			return array();
		}

		set_transient( '_simple-calendar_contributors', $contributors, HOUR_IN_SECONDS );

		return $contributors;
	}

}
