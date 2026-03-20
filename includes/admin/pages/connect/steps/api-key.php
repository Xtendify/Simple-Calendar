<?php
/**
 * Step: API Key.
 *
 * Variables expected:
 * - string $api_key
 * - bool   $has_api_key
 * - string $assets_base
 */
if (!defined('ABSPATH')) {
	exit();
}

$sc_connect_validate_nonce = wp_create_nonce('simcal_connect_validate_google_api_key');
?>
<div class="sc_connect_card">
	<h2 class="sc_connect_section_title">
		<?php esc_html_e('Google Calendar', 'google-calendar-events'); ?>
	</h2>
	<p class="sc_connect_section_subtitle">
		<?php esc_html_e(
  	'Simple for a reason. Zero technical setup. Easy integration steps with advanced options when you need them.',
  	'google-calendar-events'
  ); ?>
	</p>

	<form
		id="simcal-settings-page-form"
		method="post"
		action="options.php"
		data-sc-connect-validate-nonce="<?php echo esc_attr($sc_connect_validate_nonce); ?>"
	>
		<?php settings_fields('simple-calendar_settings_feeds'); ?>
		<label for="sc_google_api_key" class="sc_connect_label">
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
				aria-label="<?php esc_attr_e('Show API key', 'google-calendar-events'); ?>"
				aria-controls="sc_google_api_key"
				title="<?php esc_attr_e('Show API key', 'google-calendar-events'); ?>"
			>
				<img src="<?php echo esc_url($assets_base . 'eye.svg'); ?>" alt="" class="sc_input_square_show" hidden />
				<img src="<?php echo esc_url($assets_base . 'eye-white.svg'); ?>" alt="" class="sc_input_square_show_white" />
				<img src="<?php echo esc_url($assets_base . 'eye-hide.svg'); ?>" alt="" class="sc_input_square_hide" />
				<img src="<?php echo esc_url($assets_base . 'eye-hide-white.svg'); ?>" alt="" class="sc_input_square_hide_white" />
			</button>
		</div>

		<div id="sc_connect_api_key_msg_wrap" style="display:none; margin-top: 8px;">
			<span id="sc_connect_api_key_msg_error" class="sc_icon_warning_wrap" style="display:none;">
				<span class="sc_icon--circle-small">
					<img src="<?php echo esc_url($assets_base . 'warning.svg'); ?>" alt="" />
				</span>
				<span class="sc_icon_warning_label">
					<?php esc_html_e('API key is not valid', 'google-calendar-events'); ?>
				</span>
			</span>
			<span id="sc_connect_api_key_msg_success" class="sc_connect_success_wrap" style="display:none;">
				<span class="sc_icon--circle-small">
					<img src="<?php echo esc_url($assets_base . 'check.svg'); ?>" alt="" />
				</span>
				<span class="sc_connect_success_label">
					<?php esc_html_e('API key is valid', 'google-calendar-events'); ?>
				</span>
			</span>
		</div>

		<div class="sc_connect_helper_row">
			<p class="sc_connect_helper_text">
				<?php printf(
    	/* translators: %s: Google Cloud Console link */
    	esc_html__('Never share your API key. Get one from %sGoogle Cloud Console%s.', 'google-calendar-events'),
    	'<a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="sc_connect_helper_link">',
    	'</a>'
    ); ?>
			</p>

			<a href="https://simplecalendar.io/addons/google-calendar-pro/" target="_blank" class="sc_connect_pro_link">
				<img src="<?php echo esc_url($assets_base . 'crown.svg'); ?>" alt="" />
				<span class="sc_link"><?php esc_html_e('Pro Version Available Here', 'google-calendar-events'); ?></span>
			</a>
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
				class="sc_btn sc_btn--white"
				id="sc_connect_add_calendar_btn"
				<?php echo $has_api_key ? '' : 'style="display:none;"'; ?>
			>
				<?php esc_html_e('Add New Calendar', 'google-calendar-events'); ?>
			</a>
		</div>
	</form>
</div>

<section class="sc_section sc_section_last sc_connect_helpful_links">
	<div class="sc_setup_card">
		<h3 class="sc_h3 sc_section_title">
			<?php esc_html_e('Helpful Link', 'google-calendar-events'); ?>
		</h3>
		<div class="sc_helpful_links_cards_wrapper">
			<a href="https://docs.simplecalendar.io/" target="_blank" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'document.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('Documentation', 'google-calendar-events'); ?></span>
			</a>
			<a href="https://simplecalendar.io/go/setup-video" target="_blank" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'clapperboard.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('Setup Video', 'google-calendar-events'); ?></span>
			</a>
			<a href="https://simplecalendar.io/go/faq" target="_blank" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'question-white.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('FAQ', 'google-calendar-events'); ?></span>
			</a>
			<a href="https://simplecalendar.io/go/support" target="_blank" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'headphone.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('Support Form', 'google-calendar-events'); ?></span>
			</a>
		</div>
	</div>
</section>

