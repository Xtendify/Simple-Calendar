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
		<div class="simcal-font-poppins simcal-font-medium">
			<a href="<?php echo admin_url( 'edit.php?post_type=calendar' ); ?>"
			   class="button button-primary "
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
		<h2 class="simcal-nav-tab-wrapper simcal-font-poppins">
			<a class="nav-tab simcal-border-0 simcal-bg-transparent simcal-font-medium simcal-text-sc_grey-100 hover:simcal-bg-transparent focus:simcal-bg-transparent <?php if ( $_GET['page'] == 'simple-calendar_about' ) {
				echo 'nav-tab-active simcal-border-b-[3px] simcal-border-sc_green-200 focus:simcal-border-b-[3px] hover:simcal-border-b-[3px] active:simcal-border-b-[3px] focus:simcal-border-sc_green-200 hover:simcal-border-sc_green-200 active:simcal-border-sc_green-200 focus:simcal-shadow-none';
			} ?>"
			   href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_about' ), 'index.php' ) ) ); ?>"
				><?php _e( "What's New", 'google-calendar-events' ); ?></a>
			<a class="nav-tab simcal-border-0 simcal-bg-transparent simcal-font-medium simcal-text-sc_grey-100 hover:simcal-bg-transparent focus:simcal-bg-transparent <?php if ( $_GET['page'] == 'simple-calendar_credits' ) {
				echo 'nav-tab-active simcal-border-b-[3px] simcal-border-sc_green-200 focus:simcal-border-b-[3px] hover:simcal-border-b-[3px] active:simcal-border-b-[3px] focus:simcal-border-sc_green-200 hover:simcal-border-sc_green-200 active:simcal-border-sc_green-200 focus:simcal-shadow-none';
			} ?>"
			   href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_credits' ), 'index.php' ) ) ); ?>"
				><?php _e( 'Credits', 'google-calendar-events' ); ?></a>
			<a class="nav-tab simcal-border-0 simcal-bg-transparent simcal-font-medium simcal-text-sc_grey-100 hover:simcal-bg-transparent focus:simcal-bg-transparent <?php if ( $_GET['page'] == 'simple-calendar_translators' ) {
				echo 'nav-tab-active simcal-border-b-[3px] simcal-border-sc_green-200 focus:simcal-border-b-[3px] hover:simcal-border-b-[3px] active:simcal-border-b-[3px] focus:simcal-border-sc_green-200 hover:simcal-border-sc_green-200 active:simcal-border-sc_green-200 focus:simcal-shadow-none';
			} ?>"
			   href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_translators' ), 'index.php' ) ) ); ?>"
				><?php _e( 'Translators', 'google-calendar-events' ); ?></a>
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
		<div class="simcal-max-w-[100%]">		
			<div class="simcal-mt-[100px] simcal-h-[408px] simcal-border-2 simcal-relative simcal-bg-sc_banner-bg simcal-rounded-[20px]">
				<div class="simcal-pl-[4%] simcal-pt-[61px] simcal-w-[51%] simcal-text-white ">
					<?php $this->intro(); ?>
				</div>
				<div class="simcal-max-w-[100%]">
					<div class="simcal-absolute simcal-right-[-45px] simcal-top-[-30px] max-3xl:simcal-w-[66%] max-3xl:simcal-right-[-40px] max-3xl:simcal-top-[-20]">
						<img src="<?php echo $welcome_image_about_path . '/banner-right.png'; ?>" />	
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
		<div class="simcal-font-poppins simcal-font-bold simcal-text-4xl">
			<?php
			/* translators: %s prints the current version of the plugin. */
			printf( __( 'Welcome to Simple Calendar %s', 'google-calendar-events' ), SIMPLE_CALENDAR_VERSION );
			?>
		</div>

		<div class="about-text simcal-font-poppins simcal-font-normal simcal-text-lg">
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
		<div class="simcal-pt-[54px] max-3xl:simcal-pt-[25px]">
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
		$image_about_path = SIMPLE_CALENDAR_ASSETS . '/images';
		?>
		<div id="simcal-welcome">
			<div class="wrap about-wrap whats-new-wrap simcal-max-w-[100%] simcal-font-poppins simcal-mr-[4%] simcal-ml-[4%] simcal-text-sm">
				<?php $this->nav_links(); ?>				
				<?php $this->sc_intro_section(); ?>						
				<div class="simcal-grid simcal-gap-x-10 simcal-grid-cols-3 simcal-mt-[170px] simcal-font-poppins">
					<div class="simcal-h-[436px] simcal-bg-sc_cream-100 simcal-text-white simcal-pt-[52px] simcal-rounded-[15px] ">
						<div class="simcal-pl-[5%] simcal-pr-[5%]">
							<span class="simcal-text-[24px] simcal-font-bold simcal-leading-[31px]"><?php _e( 'Configure event colors, number of events to display, grid or list style', 'google-calendar-events' ); ?></span>
							<span class="simcal-text-[24px] "> <?php _e( ' and more.', 'google-calendar-events' ); ?></span>
						</div>
						<div class="simcal-ml-[20px] simcal-mr-[20px] simcal-mt-[57px] max-2xl:simcal-mt-[88px] max-3xl:simcal-mt-[87px]">
							<img src="<?php echo $welcome_image_about_path . '/cal-meeting.png'; ?>" />
						</div>
					</div>
					<div class="simcal-h-[436px] simcal-bg-sc_green-200 simcal-text-white simcal-pt-[52px] simcal-rounded-[15px] simcal-relative">
						<div class="simcal-pl-[5%]">
							<span class="simcal-text-[24px] simcal-font-bold simcal-leading-[31px] "><?php _e( 'Mobile responsive and ', 'google-calendar-events' ); ?></span><br>						
							<span class="simcal-text-[24px] simcal-font-normal"> <?php _e( 'widget ready.', 'google-calendar-events' ); ?></span>	
						</div>
						<img class="simcal-absolute simcal-bottom-0" src="<?php echo $welcome_image_about_path . '/jan-cal.png'; ?>" />												
						<img class="simcal-absolute simcal-inset-y-20 simcal-right-0" src="<?php echo $welcome_image_about_path . '/cof-house.png'; ?>" />												
					</div>
					<div class="simcal-h-[436px] simcal-bg-sc_blue-300 simcal-text-white simcal-pt-[52px] simcal-rounded-[15px]">
						<div class="simcal-pl-[5%] simcal-pr-[5%]">
							<span class="simcal-text-[24px] simcal-font-bold simcal-leading-[31px] "><?php _e( 'Add even more display options with add-ons like', 'google-calendar-events' ); ?> </span>
							<span class="simcal-text-[24px] "> <?php _e( 'Full Calendar and Google Calendar Pro.', 'google-calendar-events' ); ?></span>
						</div>
						<div class="simcal-ml-[20px] simcal-mr-[20px] simcal-mt-[58px] max-2xl:simcal-mt-[84px] max-3xl:simcal-mt-[94px]" >
							<img src="<?php echo $welcome_image_about_path . '/mar-cal.png'; ?>" />	
						</div>
					</div>
				</div>
				<div class="simcal-mt-[50px] simcal-max-w-[100%] simcal-h-[330px] simcal-pt-[45px] simcal-flex simcal-bg-sc_green-100 simcal-font-poppins simcal-rounded">
					<div class="simcal-pl-[4%] simcal-w-[46%]">
						<div class="simcal-flex simcal-pt-[50px] ">
							<div class="">
								<img src="<?php echo $welcome_image_about_path . '/bl-crown.png'; ?>" />
							</div>
							<div class="simcal-pl-[17px] simcal-font-semibold simcal-text-[18px] simcal-text-sc_black-200">
								<?php _e( 'View Pricing and Try a Demo of our', 'google-calendar-events' ); ?>
								<span class="simcal-text-sc_green-200"><?php _e( 'Simple Calendar Pro Add-ons.', 'google-calendar-events' ); ?></span>
							</div>
						</div>
						<div class="simcal-flex simcal-items-center simcal-mt-[34px] ">
							<div>
								<img src="<?php echo $image_about_path . '/green-tick.png'; ?>" />
							</div>
							<div class="simcal-ml-[9px] simcal-text-base simcal-text-gray-400 simcal-font-normal">
								<span><?php _e('Display events from both private and public Google Calendars.','google-calendar-events')?></span>
							</div>
						</div>
						<div class="simcal-flex simcal-items-center simcal-mt-[14px] ">
							<div >
								<img src="<?php echo $image_about_path . '/green-tick.png'; ?>" />
							</div>
							<div class="simcal-ml-[9px] simcal-text-base simcal-text-gray-400 simcal-font-normal">
								<span><?php _e('Display a list of attachments with links to their original source.','google-calendar-events')?></span>
							</div>
						</div>
						<div class="simcal-flex simcal-items-center simcal-mt-[14px] ">
							<div>
								<img src="<?php echo $image_about_path . '/green-tick.png'; ?>" />
							</div>
							<div class="simcal-ml-[9px] simcal-text-base simcal-text-gray-400 simcal-font-normal">
								<span><?php _e('Many More...','google-calendar-events')?></span>
							</div>
						</div>
					</div>
					<div class="simcal-m-auto simcal-w-[20%]">
						<img src="<?php echo $image_about_path . '/arrow.png'; ?>" />
					</div>
					<div class="simcal-m-auto simcal-ml-[20px] simcal-w-[30%]">
						<a href="https://simplecalendar.io/downloads/google-calendar-pro/">
							<button type="button" class="simcal-flex simcal-justify-center simcal-items-center simcal-w-[80%] simcal-h-[40px] simcal-bg-sc_green-200 simcal-text-white simcal-text-base simcal-font-medium simcal-rounded-md simcal-font-poppins">
								<img class="simcal-p-[8px]" src="<?php echo $image_about_path . '/crown.png'; ?>" />
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
		$image_about_path = SIMPLE_CALENDAR_ASSETS . '/images';
		?>	
		<div id="simcal-welcome">
			<div class="wrap about-wrap credits-wrap simcal-max-w-[100%] simcal-font-poppins simcal-mr-[4%] simcal-ml-[4%] simcal-text-sm">
				<?php $this->nav_links(); ?>				
				<?php $this->sc_intro_section(); ?>	
				<div class="simcal-mt-[121px] simcal-w-[100%] simcal-h-[auto] simcal-bg-sc_green-100 simcal-rounded-[20px] simcal-pl-[5%]">
					<div class="simcal-pt-[62px] simcal-font-bold simcal-text-xl simcal-text-sc_black-200">
						<?php _e("Simple Calendar is created by a worldwide team of developers. If you'd ",'google-calendar-events')?>	
					</div>
					<div class="simcal-text-xl simcal-text-sc_black-200">
						<?php _e("like to contribute please visit our <a href='%s' class='simcal-text-sc_green-200' target='_blank'>GitHub repo. </a>",'google-calendar-events')?>	
					</div>
					<div class="simcal-flex simcal-pt-[54px]">
						<div class="simcal-w-[60%]">
							<?php
							  echo $this->contributors();
							?>
						</div>
						<div class="simcal-w-[40%]">							
							<div class="simcal-mt-[54px] simcal-mr-[5%] simcal-w-[83%] simcal-h-[366px] simcal-rounded-[20px] simcal-bg-white ">
								<div class="simcal-pl-[42%] " >					
									<img class="simcal-pt-[19px]" src="<?php echo $image_about_path . '/rating.png'; ?>" />							
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
									<button type="button" class="simcal-mt-[20px] simcal-m-auto simcal-flex simcal-justify-center simcal-items-center simcal-w-[80%] simcal-h-[40px] simcal-bg-sc_green-200 simcal-text-white simcal-text-xl simcal-font-medium simcal-rounded-md">
										<?php _e('Rate Now','google-calendar-events')?>							
									</button>
								</a>
								<div class="simcal-mt-[25px] simcal-text-center simcal-text-base simcal-text-sc_blue-200">
									<a class="hover:simcal-text-sc_green-200" href="https://wordpress.org/support/plugin/google-calendar-events/reviews/?filter=5"><?php _e('See All Customers Reviews','google-calendar-events')?></a>
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
			<div class="wrap about-wrap translators-wrap simcal-max-w-[100%] simcal-font-poppins simcal-mr-[4%] simcal-ml-[4%] simcal-text-sm">
				<?php $this->nav_links(); ?>
				<?php $this->sc_intro_section(); ?>
				<div class="simcal-font-bold simcal-text-xl simcal-mt-[100px] simcal-text-sc_black-200">
					<span><?php _e( 'Simple Calendar has been kindly translated into several other ', 'google-calendar-events' ); ?></span>
				</div>
				<div class="simcal-text-xl simcal-text-sc_black-200">
					<span><?php _e( 'languages by contributors from all over the world.', 'google-calendar-events' ); ?></span>
				</div>
				<div class="simcal-mt-[42px]">
						<a href="https://translate.wordpress.org/projects/wp-plugins/google-calendar-events">
							<button type="button" class="simcal-items-center simcal-w-[257px] simcal-h-[40px] simcal-bg-sc_green-200 simcal-text-white simcal-text-base simcal-rounded-[7px] simcal-font-poppins">
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

		$contributor_list = '<div class="simcal-flex simcal-flex-wrap simcal-gap-[1%]">';

		foreach ( $contributors as $contributor ) {

			// Skip contributor bots
			$contributor_bots = array( 'gitter-badger' );
			if ( in_array( $contributor->login, $contributor_bots ) ) {
				continue;
			}

			$contributor_list .= '<div class="wp-person simcal-mt-[43px] simcal-rounded-[15px] simcal-pt-[10px] simcal-pl-[8px] simcal-font-medium hover:simcal-bg-sc_green-200">';
			$contributor_list .= sprintf(
				'<a href="%s" class="hover:simcal-text-white" title="%s" target="_blank">%s</a>',
				esc_url( 'https://github.com/' . $contributor->login ),
				esc_html( sprintf( __( 'View %s', 'google-calendar-events' ), $contributor->login ) ),
				sprintf( '<img src="%s" width="50" height="50" class="gravatar" alt="%s" />', esc_url( $contributor->avatar_url ), esc_html( $contributor->login ) )
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
