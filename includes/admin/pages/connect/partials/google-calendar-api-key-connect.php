<?php
/**
 * Connect: Google Calendar (core) API key form.
 *
 * Used on the API Key step and embedded on the Credentials step (Pro own-credentials).
 *
 * Variables expected:
 * - string $assets_base
 * - string $api_key       Current API key value for the field.
 * - string $sc_google_api_key_mode  'api_key_step' (default) | 'credentials_core' (layout only; both use Validate & Save).
 * - bool   $has_api_key            Whether to show "Add New Calendar" (api_key_step + credentials_core).
 */
if (!defined('ABSPATH')) {
	exit();
}

$assets_base = isset($assets_base) ? (string) $assets_base : '';
$api_key = isset($api_key) ? (string) $api_key : '';
$mode = isset($sc_google_api_key_mode) ? (string) $sc_google_api_key_mode : 'api_key_step';
$is_credentials_core = 'credentials_core' === $mode;

$card_class = $is_credentials_core ? 'sc_setup_card sc_connect_credentials_core_card' : 'sc_setup_card';
$heading_class = $is_credentials_core ? 'sc_h4 sc_connect_credentials_heading' : 'sc_h4';
$subtitle_class = 'sc_text--body_b2 sc_text--dark sc_connect_credentials_subtitle';

$form_id = 'simcal-connect-page-form';
$form_action = 'options.php';
$settings_group = 'simple-calendar_settings_feeds';
$form_attrs = [
	'data-sc-connect-validate-nonce' => wp_create_nonce('simcal_connect_validate_google_api_key'),
];
if ($is_credentials_core) {
	$form_attrs['class'] = 'sc_connect_credentials_core_form';
}

$has_api_key_for_step = isset($has_api_key) && (bool) $has_api_key;
$api_key_field_item_class = $is_credentials_core ? 'sc_item sc_item_spaced' : 'sc_item_spaced';
?>
<div class="<?php echo esc_attr($card_class); ?>">
	<div class="sc_connect_credentials_header">
		<h2 class="<?php echo esc_attr($heading_class); ?>">
			<?php esc_html_e('Google Calendar', 'google-calendar-events'); ?>
		</h2>
		<div
			data-sc-google-api-key-badge-holder="1"
			class="<?php echo $has_api_key_for_step ? '' : 'is_hidden'; ?>"
		>
			<span
				class="sc_connect_oauth_status_header_badge sc_connect_oauth_status_header_badge--pending sc_text--body_b3"
				data-sc-google-api-key-health="1"
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
		</div>
	</div>
	<p class="<?php echo esc_attr($subtitle_class); ?>">
		<?php esc_html_e(
  	'Simple for a reason. Zero technical setup. Easy integration steps with advanced options when you need them.',
  	'google-calendar-events',
  ); ?>
	</p>
	<p
		data-sc-google-api-key-health-detail="1"
		class="sc_text--body_b3 sc_connect_google_api_key_health_detail is_hidden"
		role="status"
	></p>
	<?php include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/settings-form.php'; ?>
		<input type="hidden" name="simple-calendar_settings_feeds[__sc_partial_update]" value="1" />
		<input type="hidden" name="simple-calendar_settings_feeds[__sc_partial_sections][google]" value="1" />
			<div class="<?php echo esc_attr($api_key_field_item_class); ?>">
				<label for="sc_google_api_key" class="sc_h6">
					<?php esc_html_e('Google API Key', 'google-calendar-events'); ?>
				</label>

				<div id="sc_connect_api_key_wrap" class="sc_input_wrapper sc_input_wrapper--icons-outside sc_input_wrapper--square sc_input_full">
					<input
						id="sc_google_api_key"
						type="password"
						name="simple-calendar_settings_feeds[google][api_key]"
						class="sc_input"
						value="<?php echo esc_attr($api_key); ?>"
						autocomplete="off"
					/>
					<button
						type="button"
						id="sc_connect_api_key_eye_btn"
						class="sc_icon--square"
						data-sc-password-toggle
						data-sc-label-show="<?php echo esc_attr(__('Show API key', 'google-calendar-events')); ?>"
						data-sc-label-hide="<?php echo esc_attr(__('Hide API key', 'google-calendar-events')); ?>"
						aria-label="<?php esc_attr_e('Show API key', 'google-calendar-events'); ?>"
						aria-controls="sc_google_api_key"
						title="<?php esc_attr_e('Show API key', 'google-calendar-events'); ?>"
					>
						<img src="<?php echo esc_url($assets_base . 'eye.svg'); ?>" alt="" class="sc_input_square_show" />
						<img src="<?php echo esc_url($assets_base . 'eye-white.svg'); ?>" alt="" class="sc_input_square_show_white" />
						<img src="<?php echo esc_url($assets_base . 'eye-hide.svg'); ?>" alt="" class="sc_input_square_hide" />
						<img src="<?php echo esc_url($assets_base . 'eye-hide-white.svg'); ?>" alt="" class="sc_input_square_hide_white" />
					</button>
				</div>

				<div id="sc_connect_api_key_msg_wrap" class="sc_connect_api_key_msg_wrap is_hidden">
					<span id="sc_connect_api_key_msg_error" class="sc_icon_warning_wrap is_hidden">
						<span class="sc_icon--circle-small">
							<img src="<?php echo esc_url($assets_base . 'warning.svg'); ?>" alt="" />
						</span>
						<span class="sc_icon_warning_label">
							<?php esc_html_e('API key is not valid', 'google-calendar-events'); ?>
						</span>
					</span>
					<span id="sc_connect_api_key_msg_success" class="sc_connect_success_wrap is_hidden">
						<span class="sc_icon--circle-small">
							<img src="<?php echo esc_url($assets_base . 'check.svg'); ?>" alt="" />
						</span>
						<span class="sc_connect_success_label">
							<?php esc_html_e('API key is valid', 'google-calendar-events'); ?>
						</span>
					</span>
				</div>
			</div>

		<div class="sc_connect_helper_row">
			<p class="sc_connect_helper_text">
				<?php
    /* translators: %1$s: Opening anchor tag, %2$s: Closing anchor tag */
    $text = __('Never share your API key. Get one from %1$sCreating a Google API Key%2$s.', 'google-calendar-events');
    echo wp_kses(
    	sprintf(
    		$text,
    		'<a href="' .
    			esc_url(
    				simcal_ga_campaign_url(
    					'https://docs.simplecalendar.io/google-api-key',
    					'core-plugin',
    					'connect-api-key-docs',
    				),
    			) .
    			'" target="_blank" class="sc_connect_helper_link">',
    		'</a>',
    	),
    	[
    		'a' => [
    			'href' => true,
    			'target' => true,
    			'rel' => true,
    			'class' => true,
    		],
    	],
    );
    ?>
			</p>

			<?php if (!$is_credentials_core) { ?>
				<a href="<?php echo simcal_ga_campaign_url(
    	'https://simplecalendar.io/downloads/google-calendar-pro/',
    	'core-plugin',
    	'connect-api-key-pro-addon',
    ); ?>" target="_blank"  class="sc_connect_pro_link">
					<img src="<?php echo esc_url($assets_base . 'crown.svg'); ?>" alt="" />
					<span class="sc_link"><?php esc_html_e('Pro Version Available Here', 'google-calendar-events'); ?></span>
				</a>
			<?php } ?>
		</div>

		<div class="sc_connect_form_actions">
			<button type="submit" class="sc_btn sc_btn--blue-loading" data-sc-connect-validate-btn>
				<span class="sc_btn_submit"><?php esc_html_e('Validate & Save', 'google-calendar-events'); ?></span>
				<span class="sc_btn_loading" aria-hidden="true">
					<span class="sc_btn--icons sc_btn--loading-icon">autorenew</span>
				</span>
				<span class="sc_btn_check" aria-hidden="true">
					<span class="sc_btn--icons sc_btn--check-icon">check</span>
				</span>
			</button>
			<a
				href="<?php echo esc_url(admin_url('post-new.php?post_type=calendar')); ?>"
				class="sc_btn sc_btn--white<?php echo $has_api_key_for_step ? '' : ' is_hidden'; ?>"
				id="sc_connect_add_calendar_btn"
			>
				<?php esc_html_e('Add New Calendar', 'google-calendar-events'); ?>
			</a>
		</div>
	</form>
</div>
