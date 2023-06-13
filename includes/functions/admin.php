<?php
/**
 * Admin functions
 *
 * Functions for the admin back end components only.
 *
 * @package SimpleCalendar/Admin/Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SimpleCalendar\Admin\Notice;

/**
 * Get settings pages and tabs.
 *
 * @since  3.0.0
 *
 * @return array
 */
function simcal_get_admin_pages() {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_admin_pages() : array();
}

/**
 * Get a settings page tab.
 *
 * @since  3.0.0
 *
 * @param  string $page
 *
 * @return null|\SimpleCalendar\Abstracts\Admin_Page
 */
function simcal_get_admin_page( $page ) {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_admin_page( $page ) : null;
}

/**
 * Get a field.
 *
 * @since  3.0.0
 *
 * @param  array  $args
 * @param  string $name
 *
 * @return null|\SimpleCalendar\Abstracts\Field
 */
function simcal_get_field( $args, $name = '' ) {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_field( $args, $name ) : null;
}

/**
 * Print a field.
 *
 * @since  3.0.0
 *
 * @param  array  $args
 * @param  string $name
 *
 * @return void
 */
function simcal_print_field( $args, $name = '' ) {

	$field = simcal_get_field( $args, $name );

	if ( $field instanceof \SimpleCalendar\Abstracts\Field ) {
		$field->html();
	}
}

/**
 * Sanitize a variable of unknown type.
 *
 * Recursive helper function to sanitize a variable from input,
 * which could also be a multidimensional array of variable depth.
 *
 * @since  3.0.0
 *
 * @param  mixed  $var  Variable to sanitize.
 * @param  string $func Function to use for sanitizing text strings (default 'sanitize_text_field')
 *
 * @return array|string Sanitized variable
 */
function simcal_sanitize_input( $var, $func = 'sanitize_text_field'  ) {

	if ( is_null( $var ) ) {
		return '';
	}

	if ( is_bool( $var ) ) {
		if ( $var === true ) {
			return 'yes';
		} else {
			return 'no';
		}
	}

	if ( is_string( $var ) || is_numeric( $var ) ) {
		$func = is_string( $func ) && function_exists( $func ) ? $func : 'sanitize_text_field';
		return call_user_func( $func, trim( strval( $var ) ) );
	}

	if ( is_object( $var ) ) {
		$var = (array) $var;
	}

	if ( is_array( $var ) ) {
		$array = array();
		foreach ( $var as $k => $v ) {
			$array[ $k ] = simcal_sanitize_input( $v );
		}
		return $array;
	}

	return '';
}

/**
 * Check if a screen is a plugin admin view.
 * Returns the screen id if true, false (bool) if not.
 *
 * @since  3.0.0
 *
 * @return string|bool
 */
function simcal_is_admin_screen() {

	$view = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

	if ( $view instanceof WP_Screen ) {

		// Screens used by this plugin.
		$screens = array(
			'customize',
			'calendar',
			'calendar_page_simple-calendar_add_ons',
			'calendar_page_simple-calendar_settings',
			'calendar_page_simple-calendar_tools',
			'edit-calendar',
			'edit-calendar_category',
			'dashboard_page_simple-calendar_about',
			'dashboard_page_simple-calendar_credits',
			'dashboard_page_simple-calendar_translators',
		);
		if ( in_array( $view->id, $screens ) ) {
			return $view->id;
		}
	}

	return false;
}

/**
 * Update add-on.
 *
 * @since 3.0.0
 *
 * @param string $_api_url     The URL pointing to the custom API end
 * @param string $_plugin_file Path to the plugin file.
 * @param array  $_api_data    Optional data to send with API calls.
 *
 * @return \SimpleCalendar\Admin\Updater
 */
function simcal_addon_updater( $_api_url, $_plugin_file, $_api_data = null ) {
	return new \SimpleCalendar\Admin\Updater( $_api_url, $_plugin_file, $_api_data );
}

/**
 * Get add-on license key.
 *
 * @since  3.0.0
 *
 * @param  string $addon Unique add-on id.
 *
 * @return null|string
 */
function simcal_get_license_key( $addon ) {
	$licenses = get_option( 'simple-calendar_settings_licenses', array() );
	if ( isset( $licenses['keys'][ $addon ] ) ) {
		return empty( $licenses['keys'][ $addon ] ) ? null : $licenses['keys'][ $addon ];
	}
	return null;
}

/**
 * Get add-on license status.
 *
 * Without passing arguments returns all the statuses array.
 *
 * @since  3.0.0
 *
 * @param  null|string $addon Unique add-on slug.
 *
 * @return array|string
 */
function simcal_get_license_status( $addon = null ) {
	$licenses = get_option( 'simple-calendar_licenses_status', array() );
	return isset( $licenses[ $addon ] ) ? $licenses[ $addon ] : $licenses;
}

/**
 * Get admin notices.
 *
 * @since  3.0.0
 *
 * @return array
 */
function simcal_get_admin_notices() {
	$notices = new \SimpleCalendar\Admin\Notices();
	return $notices->get_notices();
}

/**
 * Delete admin notices.
 *
 * @since 3.0.0
 */
function simcal_delete_admin_notices() {
	delete_option( 'simple-calendar_admin_notices' );
}

/**
 * Print a shortcode tip.
 *
 * @since  3.0.0
 *
 * @param  int $post_id
 *
 * @return void
 */
function simcal_print_shortcode_tip( $post_id ) {

	$browser = new \SimpleCalendar\Browser();
	if ( $browser::PLATFORM_APPLE == $browser->getPlatform() ) {
		$cmd = '&#8984;&#43;C';
	} else {
		$cmd = 'Ctrl&#43;C';
	}

	$shortcut  = sprintf( __( 'Press %s to copy.', 'google-calendar-events' ), $cmd );
	$shortcode = sprintf( '[calendar id="%s"]', $post_id );

	echo "<input readonly='readonly' " .
				"class='simcal-shortcode simcal-calendar-shortcode simcal-shortcode-tip' " .
				"title='" . $shortcut . "' " .
				"onclick='this.select();' value='" . $shortcode . "' />";
}

/**
 * Google Analytics campaign URL.
 *
 * @since   3.0.0
 *
 * @param   string  $base_url   Plain URL to navigate to
 * @param   string  $campaign   GA "campaign" tracking value
 * @param   string  $content    GA "content" tracking value
 * @param   bool    $raw        Use esc_url_raw instead (default = false)
 *
 * @return  string  $url        Full Google Analytics campaign URL
 */
function simcal_ga_campaign_url( $base_url, $campaign, $content, $raw = false ) {

	$url = add_query_arg( array(
		'utm_source'   => 'inside-plugin',
		'utm_medium'   => 'link',
		'utm_campaign' => $campaign, // i.e. 'core-plugin', 'gcal-pro'
		'utm_content'  => $content // i.e. 'sidebar-link', 'settings-link'
	), $base_url );

	if ( $raw ) {
		return esc_url_raw( $url );
	}

	return esc_url( $url );
}

/**
 * Newsletter signup form.
 *
 * @since  3.0.0
 *
 * @return void
 */
function simcal_newsletter_signup() {

	if ( $screen = simcal_is_admin_screen() ) {

		global $current_user;
		wp_get_current_user();

		$name = $current_user->user_firstname ? $current_user->user_firstname : '';

		?>
		<div id="simcal-drip" class="<?php echo $screen; ?>">
			<div class="signup">
				<p>
					<?php _e( "Enter your name and email and we'll send you a coupon code for <strong>20% off</strong> all Pro Add-on purchases.", 'google-calendar-events' ); ?>
				</p>

				<p>
					<label for="simcal-drip-field-email"><?php _e( 'Your Email', 'google-calendar-events' ); ?></label><br />
					<input type="email"
					       id="simcal-drip-field-email"
					       name="fields[email]"
					       value="<?php echo $current_user->user_email; ?>" />
				</p>

				<p>
					<label for="simcal-drip-field-first_name"><?php _e( 'First Name', 'google-calendar-events' ); ?></label><br />
					<input type="text"
					       id="simcal-drip-field-first_name"
					       name="fields[first_name]"
					       value="<?php echo $name; ?>" />
				</p>
				<p class="textright">
					<a href="#"
					   id="simcal-drip-signup"
					   class="button button-primary"><?php _e( 'Send me the coupon', 'google-calendar-events' ); ?></a>
				</p>
				<div class="textright">
					<em><?php _e( 'No spam. Unsubscribe anytime.', 'google-calendar-events' ); ?></em>
					<br/>
					<a href="<?php echo simcal_ga_campaign_url( simcal_get_url( 'addons' ), 'core-plugin', 'sidebar-link' ); ?>"
					   target="_blank"><?php _e( 'Just take me the add-ons', 'google-calendar-events' ); ?></a>
				</div>
			</div>
			<div class="thank-you" style="display: none;">
				<?php _e( 'Thank you!', 'google-calendar-events' ); ?>
			</div>
			<div class="clear">
			</div>
		</div>
		<?php

	}
}

/**
 * Upgrade to Premium Add-ons HTML.
 *
 * @since  3.1.6
 *
 * @return void
 */
function simcal_upgrade_to_premium() {

	if ( $screen = simcal_is_admin_screen() ) {
		?>
		<div class="main">
			<p class="heading centered">
				<?php _e( 'Some of the features included with our premium add-ons', 'google-calendar-events' ); ?>
			</p>

			<ul>
				<li><div class="dashicons dashicons-yes"></div> <?php _e( 'Display color coded events', 'google-calendar-events' ); ?></li>
				<li><div class="dashicons dashicons-yes"></div> <?php _e( 'Show week & day views', 'google-calendar-events' ); ?></li>
				<li><div class="dashicons dashicons-yes"></div> <?php _e( 'Fast view switching', 'google-calendar-events' ); ?></li>
				<li><div class="dashicons dashicons-yes"></div> <?php _e( 'Event titles & start times in grid', 'google-calendar-events' ); ?></li>
				<li><div class="dashicons dashicons-yes"></div> <?php _e( 'Limit event display times', 'google-calendar-events' ); ?></li>
				<li><div class="dashicons dashicons-yes"></div> <?php _e( 'Display private calendar events', 'google-calendar-events' ); ?></li>
				<li><div class="dashicons dashicons-yes"></div> <?php _e( 'Show attendees & RSVP status', 'google-calendar-events' ); ?></li>
				<li><div class="dashicons dashicons-yes"></div> <?php _e( 'Display attachments', 'google-calendar-events' ); ?></li>
				<li><div class="dashicons dashicons-yes"></div> <?php _e( 'Priority email support', 'google-calendar-events' ); ?></li>
			</ul>

			<div class="centered">
				<a href="<?php echo simcal_ga_campaign_url( simcal_get_url( 'addons' ), 'core-plugin', 'sidebar-link' ); ?>"
				   class="button-primary button-large" target="_blank">
					<?php _e( 'Upgrade to Premium Now', 'google-calendar-events' ); ?></a>
			</div>
		</div>
		<?php
	}
}
/**
 * Rating 
 *
 * @since  3.1.42
 *
 * 
 */
function sc_rating() {
	?>
		<div class='simcal-flex simcal-flex-row simcal-justify-center simcal-gap-3'>	
			<?php
			for ($i = 0; $i < 5; $i++){
				svgfillstar();
			}
			?>
		</div>	
	<?php
}


/**
 * Notice to update the Pro addon if version is less then 1.1.2.
 * This function can be remove after June 1 2024.
 * @since  3.1.43
 *
 * @return void
 */
function simcal_notice_to_update_pro_addon(){
	$all_plugins = get_plugins();
	$notices = get_option( 'simple-calendar_admin_notices', array() );

	if ( array_key_exists( 'Simple-Calendar-Google-Calendar-Pro/simple-calendar-google-calendar-pro.php', $all_plugins ) &&  !empty($all_plugins['Simple-Calendar-Google-Calendar-Pro/simple-calendar-google-calendar-pro.php']['Version']) && version_compare($all_plugins['Simple-Calendar-Google-Calendar-Pro/simple-calendar-google-calendar-pro.php']['Version'], '1.1.2', '<') )  {
		$update_pro_notice = new Notice( array(
				'id'          => array( 'check_pro_updated' => 'update_pro_notice' ),
				'type'        => 'error',
				'dismissable' => false,
				'content'     => '<p>' .
				                 '<i class="simcal-icon-calendar-logo"></i> ' .
				                 sprintf(
					                 __( 'Attention! Please take immediate action to update the <strong>Simple Calendar - Google Calendar Pro Add-On</strong> plugin and avoid potential errors. Thank you for your cooperation.  <a href="%s">Click Here</a>.', 'google-calendar-events' ),
					                 admin_url( 'plugins.php' )
				                 ) .
				                 '</p>',
			)
		);

		$update_pro_notice->add();

	}elseif ( is_array( $notices ) ) {
		unset( $notices[ 'check_pro_updated' ] );
		update_option( 'simple-calendar_admin_notices', $notices );	
	}
}
add_action( 'admin_init', 'simcal_notice_to_update_pro_addon');
