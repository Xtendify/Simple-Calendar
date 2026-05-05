<?php
/**
 * Step: Credentials (Pro).
 *
 * Variables expected:
 * - string $assets_base
 * - bool   $has_oauth_connection
 * - string $api_key
 */
if (!defined('ABSPATH')) {
	exit();
}

$is_pro_active = false;
if (defined('SIMPLE_CALENDAR_GOOGLE_PRO_VERSION')) {
	$is_pro_active = true;
} elseif (class_exists('Google_Pro')) {
	$is_pro_active = true;
} elseif (isset($welcome_context) && 'pro' === (string) $welcome_context) {
	$is_pro_active = true;
}

$setup_video_url = $is_pro_active
	? 'https://youtu.be/lmN774Fk3rw?si=zBMoOck0BjI7Q39j'
	: 'https://youtu.be/3QveIbm5Oc0?si=fMEUU0Za7KFzlJk5';

$feeds_options = get_option('simple-calendar_settings_feeds', []);
$google_pro =
	isset($feeds_options['google-pro']) && is_array($feeds_options['google-pro']) ? $feeds_options['google-pro'] : [];
$redirect_url = isset($google_pro['redirect_url']) ? trim((string) $google_pro['redirect_url']) : '';
$client_id = isset($google_pro['client_id']) ? trim((string) $google_pro['client_id']) : '';
$client_secret = isset($google_pro['client_secret']) ? trim((string) $google_pro['client_secret']) : '';
$client_auth = isset($google_pro['client_auth']) ? trim((string) $google_pro['client_auth']) : '';
$has_client_credentials = !empty($client_id) && !empty($client_secret);

// Allow switching UI via query args, but only persist the choice when a nonce is present.
$show_own_credentials = isset($_GET['sc_pro_own']) && '1' === (string) $_GET['sc_pro_own'];
$force_via_sc = isset($_GET['sc_pro_via']) && '1' === (string) $_GET['sc_pro_via'];

$switch_nonce = isset($_GET['_wpnonce']) ? sanitize_text_field((string) wp_unslash($_GET['_wpnonce'])) : '';
$can_persist_switch = $switch_nonce && wp_verify_nonce($switch_nonce, 'simcal_pro_connection_switch');

// Pro Connect: persist and remember connection type choice (via SC vs own credentials).
// If user explicitly chose own credentials via query param, store the choice.
if ($show_own_credentials && current_user_can('manage_options') && $can_persist_switch) {
	update_option('simple_calendar_connect_pro_connection_type', 'own', false);
} elseif ($force_via_sc && current_user_can('manage_options') && $can_persist_switch) {
	// Explicitly switching back to OAuth via Simple Calendar.
	$show_own_credentials = false;
	update_option('simple_calendar_connect_pro_connection_type', 'via_sc', false);
} elseif (!$show_own_credentials) {
	// If no explicit choice in URL, default to previously saved choice.
	$pro_choice = (string) get_option('simple_calendar_connect_pro_connection_type', '');
	if ('own' === $pro_choice) {
		$show_own_credentials = true;
	}
}

$site_url = get_site_url();
$authredirect = '';
if (defined('SIMPLE_CALENDAR_OAUTH_HELPER_AUTH_DOMAIN') && SIMPLE_CALENDAR_OAUTH_HELPER_AUTH_DOMAIN) {
	$oauth_state = wp_create_nonce('simcal_oauth_via_sc_state');
	$authredirect = add_query_arg(
		[
			'request_from' => $site_url,
			'state' => $oauth_state,
		],
		SIMPLE_CALENDAR_OAUTH_HELPER_AUTH_DOMAIN . 'helper/',
	);
}
$authredirect = apply_filters('simcal_connect_oauth_via_simple_calendar_url', $authredirect);

$connect_field_groups = function_exists('simcal_connect_settings_fields') ? simcal_connect_settings_fields() : [];
$google_pro_defs =
	isset($connect_field_groups['google-pro']['fields']) && is_array($connect_field_groups['google-pro']['fields'])
		? $connect_field_groups['google-pro']['fields']
		: [];
?>

<div class="sc_setup_card">
	<div class="sc_connect_credentials_header">
		<h3 class="sc_h4 sc_connect_credentials_heading">
			<?php esc_html_e('Connect Google Calendar Pro', 'google-calendar-events'); ?>
		</h3>
		<?php
  // Only show the status badge when the *current connection type* has an active authentication to check.
  $should_show_oauth_badge = false;
  $oauth_badge_action = '';
  if (!$show_own_credentials) {
  	// OAuth via Simple Calendar connection type.
  	if (!empty($has_oauth_connection)) {
  		$should_show_oauth_badge = true;
  		$oauth_badge_action = 'simcal_connect_oauth_via_sc_check';
  	}
  } else {
  	// Own credentials connection type.
  	if (!empty($has_client_credentials)) {
  		$should_show_oauth_badge = true;
  		$oauth_badge_action = 'simcal_connect_own_oauth_check';
  	}
  }
  ?>
		<?php if ($should_show_oauth_badge) { ?>
			<span
				id="sc_connect_oauth_status_header_badge"
				class="sc_connect_oauth_status_header_badge sc_connect_oauth_status_header_badge--pending sc_text--body_b3"
				data-sc-oauth-check="1"
				data-sc-oauth-check-action="<?php echo esc_attr($oauth_badge_action); ?>"
			>
				<span class="sc_connect_oauth_status_header_badge_inner">
					<span class="sc_connect_credentials_auth_status_dot sc_connect_oauth_status_header_dot" aria-hidden="true"></span>
					<img
						class="sc_connect_oauth_status_header_warn"
						src="<?php echo esc_url($assets_base . 'warning.svg'); ?>"
						alt=""
					/>
					<span class="sc_connect_oauth_status_header_label">
						<?php esc_html_e('Checking…', 'google-calendar-events'); ?>
					</span>
				</span>
			</span>
		<?php } ?>
	</div>
	<p
		class="sc_text--body_b2 sc_text--dark sc_connect_credentials_subtitle"
		data-sc-subtitle-disconnected="<?php echo esc_attr__(
  	$show_own_credentials
  		? 'Enter your credentials to manually configure Google OAuth client. Your credentials remain secure and are never stored on our servers.'
  		: 'You’re one tap away from linking your Google account. Your credentials remain secure and are never stored on our servers.',
  	'google-calendar-events',
  ); ?>"
		data-sc-subtitle-connected="<?php echo esc_attr__(
  	'Google Calendar is connected and ready to sync your events.',
  	'google-calendar-events',
  ); ?>"
	>
		<?php echo esc_html(
  	$show_own_credentials
  		? __(
  			'Enter your credentials to manually configure Google OAuth client. Your credentials remain secure and are never stored on our servers.',
  			'google-calendar-events',
  		)
  		: __(
  			'You’re one tap away from linking your Google account. Your credentials remain secure and are never stored on our servers.',
  			'google-calendar-events',
  		),
  ); ?>
	</p>

	<?php if (!$show_own_credentials) { ?>
		<?php
 	// Always render the status pill (unlink + Not Connected) like the design.
 	// Only enable AJAX checking when a token exists, to avoid showing network errors when not authenticated.
 	?>
  <?php $auth_status_text = !empty($has_oauth_connection)
  	? esc_html__('Checking…', 'google-calendar-events')
  	: esc_html__('Not Connected', 'google-calendar-events'); ?>
		<div class="sc_connect_auth_status_box">
			<div class="sc_connect_auth_status_icon_circle">
				<img src="<?php echo esc_url($assets_base . 'logo-favicon.svg'); ?>" alt="" />
			</div>
			<div
				class="sc_connect_auth_status_center"
				<?php if (!empty($has_oauth_connection)) { ?>
					data-sc-oauth-check="1"
					data-sc-oauth-check-action="simcal_connect_oauth_via_sc_check"
				<?php } ?>
				data-sc-oauth-icon-link="<?php echo esc_url($assets_base . 'link.svg'); ?>"
				data-sc-oauth-icon-unlink="<?php echo esc_url($assets_base . 'unlink.svg'); ?>"
			>
				<div class="sc_connect_auth_status_link_row" aria-hidden="true">
					<span class="sc_connect_auth_status_line"></span>
					<img
						src="<?php echo esc_url(!empty($has_oauth_connection) ? $assets_base . 'unlink.svg' : $assets_base . 'unlink.svg'); ?>"
						class="sc_connect_auth_status_link_icon"
						alt=""
					/>
					<span class="sc_connect_auth_status_line"></span>
				</div>
				<span id="sc_connect_oauth_status" class="sc_text--body_b2 sc_connect_oauth_status">
					<?php echo $auth_status_text; ?>
				</span>
			</div>
			<div class="sc_connect_auth_status_icon_circle">
				<img src="<?php echo esc_url($assets_base . 'google-favicon.svg'); ?>" alt="" />
			</div>
		</div>

		<?php include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/oauth-via-simple-calendar.php'; ?>

		<?php if (empty($has_oauth_connection)) { ?>
			<div class="sc_connect_credentials_link_row_center">
			<span class="sc_text--body_b3 sc_text--medium_gray"><?php esc_html_e('or, ', 'google-calendar-events'); ?></span>
				<a
					href="<?php echo esc_url(
     	wp_nonce_url(
     		admin_url('edit.php?post_type=calendar&page=simple-calendar_settings&sc_pro_own=1'),
     		'simcal_pro_connection_switch',
     	),
     ); ?>"
					class="sc_link sc_link_muted  sc_text--body_b3"
				>
					<?php esc_html_e(' I’ll connect manually with my own credentials', 'google-calendar-events'); ?>
				</a>
			</div>
		<?php } ?>
	<?php } else { ?>
		<div class="sc_connect_helper_row sc_connect_credentials_helper_row">
			<?php
   echo sprintf(
   	__(
   		'<a href="%s" target="_blank" class="sc_connect_helper_link">Step-by-step instructions</a>',
   		'google-calendar-events',
   	),
   	simcal_ga_campaign_url(
   		simcal_get_url('docs') . '/google-calendar-pro-configure-google-oauth/',
   		'gcal-pro',
   		'settings-link',
   	),
   );

   echo sprintf(
   	__(
   		'<a href="%s" target="_blank" class="sc_connect_helper_link">Google Developers Console</a> ',
   		'google-calendar-events',
   	),
   	simcal_get_url('gdev-console'),
   );
   ?>
		</div>

		<?php
  $form_id = 'simcal-pro-connect-page-form';
  $form_action = 'options.php';
  $settings_group = 'simple-calendar_settings_feeds';
  $form_attrs = [
  	'class' => 'sc_connect_credentials_form',
  ];
  include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/settings-form.php';
  ?>
			<input type="hidden" name="simple-calendar_settings_feeds[__sc_partial_update]" value="1" />
			<input type="hidden" name="simple-calendar_settings_feeds[__sc_partial_sections][google-pro]" value="1" />
			<?php
   // Same option key as settings page; only output when non-empty so partial saves do not wipe stored auth.
   if ('' !== $client_auth) { ?>
			<input
				type="hidden"
				id="simple-calendar-settings-feeds-google-pro-client_auth"
				name="simple-calendar_settings_feeds[google-pro][client_auth]"
				value="<?php echo esc_attr($client_auth); ?>"
				autocomplete="off"
			/>
				<?php }
   $field_defs = $google_pro_defs;
   $values = [
   	'redirect_url' => $redirect_url,
   	'client_id' => $client_id,
   	'client_secret' => $client_secret,
   ];
   include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/render-connect-fields.php';
   ?>
			<div class="sc_connect_form_actions sc_connect_credentials_actions_top">
				<button type="submit" name="sc_connect_save_and_authenticate" value="1" class="sc_btn sc_btn--blue">
					<?php esc_html_e('Save & Authenticate', 'google-calendar-events'); ?>
				</button>
				<?php
    $has_published_pro_calendar = isset($has_published_pro_calendar) ? (bool) $has_published_pro_calendar : false;
    $own_oauth_health_ok = (string) get_option('simple_calendar_connect_pro_own_oauth_health_ok', '');
    $show_add_pro_calendar_btn = !$has_published_pro_calendar && '1' === $own_oauth_health_ok;
    $can_unhide_add_pro_calendar_btn = !$has_published_pro_calendar;
    ?>
				<a
					href="<?php echo esc_url(admin_url('post-new.php?post_type=calendar')); ?>"
					id="sc_connect_add_pro_calendar_btn"
					class="sc_btn sc_btn--white<?php echo $show_add_pro_calendar_btn ? '' : ' is_hidden'; ?>"
					data-sc-can-unhide="<?php echo $can_unhide_add_pro_calendar_btn ? '1' : '0'; ?>"
				>
					<?php esc_html_e('Add New Calendar', 'google-calendar-events'); ?>
				</a>
			</div>

			<div class="sc_connect_credentials_link_row">
			<span class="sc_text--body_b3 sc_text--medium_gray"><?php esc_html_e('or, ', 'google-calendar-events'); ?></span>
				<a
					href="<?php echo esc_url(
     	wp_nonce_url(
     		admin_url('edit.php?post_type=calendar&page=simple-calendar_settings&sc_pro_via=1'),
     		'simcal_pro_connection_switch',
     	),
     ); ?>"
					class="sc_link sc_link_muted  sc_text--body_b3"
				>
					<?php esc_html_e('I want to connect securely in 1 click with OAuth via Simple Calendar', 'google-calendar-events'); ?>
				</a>
			</div>
		</form>
	<?php } ?>
</div>

<?php
if ($show_own_credentials) { ?>
	<?php
 $sc_google_api_key_mode = 'credentials_core';
 include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/google-calendar-api-key-connect.php';
 ?>
<?php }
include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/helpful-links.php';
?>

