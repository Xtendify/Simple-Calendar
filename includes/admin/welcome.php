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
		<p>
			<a href="<?php echo admin_url( 'edit.php?post_type=calendar' ); ?>"
			   class="button button-primary"
				><?php _e( 'Calendars', 'google-calendar-events' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( 'page', 'simple-calendar_settings', admin_url( 'admin.php' ) ) ); ?>"
			   class="button button-primary"
				><?php _e( 'Settings', 'google-calendar-events' ); ?></a>
			<a href="<?php echo simcal_ga_campaign_url( simcal_get_url( 'addons' ), 'core-plugin', 'welcome-page' ); ?>"
			   class="docs button button-primary" target="_blank"
				><?php _e( 'Add-ons', 'google-calendar-events' ); ?></a>
			<a href="<?php echo simcal_ga_campaign_url( simcal_get_url( 'docs' ), 'core-plugin', 'welcome-page' ); ?>"
			   class="docs button button-primary" target="_blank"
				><?php _e( 'Documentation', 'google-calendar-events' ); ?></a>
		</p>
		<?php

	}

	/**
	 * Intro shown on every about page screen.
	 *
	 * @since 3.0.0
	 */
	private function intro() {

		?>
		<h1>
			<?php
			/* translators: %s prints the current version of the plugin. */
			printf( __( 'Welcome to Simple Calendar %s', 'google-calendar-events' ), SIMPLE_CALENDAR_VERSION );
			?>
		</h1>

		<div class="about-text">
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
			><?php _e( 'Check out our documentation', 'google-calendar-events' ); ?></a>
			<?php _e( 'to get started now.', 'google-calendar-events' ); ?>
		</div>

		<div class="simcal-badge">&nbsp;</div>

		<?php $this->main_nav_links(); ?>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php if ( $_GET['page'] == 'simple-calendar_about' ) {
				echo 'nav-tab-active';
			} ?>"
			   href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_about' ), 'index.php' ) ) ); ?>"
				><?php _e( "What's New", 'google-calendar-events' ); ?></a>
			<a class="nav-tab <?php if ( $_GET['page'] == 'simple-calendar_credits' ) {
				echo 'nav-tab-active';
			} ?>"
			   href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_credits' ), 'index.php' ) ) ); ?>"
				><?php _e( 'Credits', 'google-calendar-events' ); ?></a>
			<a class="nav-tab <?php if ( $_GET['page'] == 'simple-calendar_translators' ) {
				echo 'nav-tab-active';
			} ?>"
			   href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_translators' ), 'index.php' ) ) ); ?>"
				><?php _e( 'Translators', 'google-calendar-events' ); ?></a>
		</h2>
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
			<div class="wrap about-wrap whats-new-wrap">

				<?php $this->intro(); ?>

				<h3><?php _e( 'Configure event colors, number of events to display, grid or list style and more.', 'google-calendar-events' ); ?></h3>
				<img src="<?php echo $welcome_image_about_path . '/grid-view-basic.png'; ?>" />

				<h3><?php _e( 'Mobile responsive and widget ready.', 'google-calendar-events' ); ?></h3>
				<img src="<?php echo $welcome_image_about_path . '/list-view-widget.png'; ?>" />
				<img src="<?php echo $welcome_image_about_path . '/grid-view-widget-dark-theme.png'; ?>" />

				<h3>
					<?php _e( 'Add even more display options with add-ons like', 'google-calendar-events' ); ?>
					<a href="<?php echo $welcome_addons_link; ?>" target="_blank"><?php _e( 'FullCalendar and Google Calendar Pro', 'google-calendar-events' ); ?></a>.
				</h3>
				<a href="<?php echo $welcome_addons_link; ?>" target="_blank"><img src="<?php echo $welcome_image_about_path . '/fullcalendar-google-calendar-pro-grid-view.png'; ?>" /></a>

				<h3><a href="<?php echo $welcome_addons_link; ?>" target="_blank"><?php _e( 'View Pricing and Try a Demo of our Simple Calendar Pro Add-ons.', 'google-calendar-events' ); ?></a></h3>

				<hr/>

				<?php $this->main_nav_links(); ?>

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

		?>
		<div id="simcal-welcome">
			<div class="wrap about-wrap credits-wrap">
				<?php $this->intro(); ?>
				<p class="about-description">
					<?php

					printf(
						__( "Simple Calendar is created by a worldwide team of developers. If you'd like to contribute please visit our <a href='%s' target='_blank'>GitHub repo</a>.", 'google-calendar-events' ),
						simcal_get_url( 'github' )
					);

					?>
				</p>
				<?php echo $this->contributors(); ?>
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
			<div class="wrap about-wrap translators-wrap">
				<?php $this->intro(); ?>
				<p class="about-description">
					<?php _e( 'Simple Calendar has been kindly translated into several other languages by contributors from all over the world.', 'google-calendar-events' ); ?>
				</p>
				<p class="about-description">
					<a href="https://translate.wordpress.org/projects/wp-plugins/google-calendar-events" target="_blank"><?php _e( 'Click here to help translate', 'google-calendar-events' ); ?></a>
				</p>
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

		$contributor_list = '<ul class="wp-people-group">';

		foreach ( $contributors as $contributor ) {

			// Skip contributor bots
			$contributor_bots = array( 'gitter-badger' );
			if ( in_array( $contributor->login, $contributor_bots ) ) {
				continue;
			}

			$contributor_list .= '<li class="wp-person">';
			$contributor_list .= sprintf(
				'<a href="%s" title="%s" target="_blank">%s</a>',
				esc_url( 'https://github.com/' . $contributor->login ),
				esc_html( sprintf( __( 'View %s', 'google-calendar-events' ), $contributor->login ) ),
				sprintf( '<img src="%s" width="64" height="64" class="gravatar" alt="%s" />', esc_url( $contributor->avatar_url ), esc_html( $contributor->login ) )
			);
			$contributor_list .= sprintf(
				'<a class="web" href="%s" target="_blank">%s</a>',
				esc_url( 'https://github.com/' . $contributor->login ),
				esc_html( $contributor->login )
			);
			$contributor_list .= '</li>';

		}

		$contributor_list .= '</ul>';

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
			'https://api.github.com/repos/moonstonemedia/Simple-Calendar/contributors'
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
