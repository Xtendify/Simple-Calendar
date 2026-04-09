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

$sc_google_api_key_mode = 'api_key_step';
include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/partials/google-calendar-api-key-connect.php';
?>

<section class="sc_section sc_section_last sc_connect_helpful_links">
	<div class="sc_setup_card">
		<h3 class="sc_h3 sc_section_title">
			<?php esc_html_e('Helpful Link', 'google-calendar-events'); ?>
		</h3>
		<div class="sc_helpful_links_cards_wrapper">
			<a href="<?php echo simcal_ga_campaign_url('https://docs.simplecalendar.io/', 'core-plugin', 'connect-api-key-documentation'); ?>" target="_blank" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'document.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('Documentation', 'google-calendar-events'); ?></span>
			</a>
			<a href="<?php echo simcal_ga_campaign_url('https://simplecalendar.io/go/setup-video', 'core-plugin', 'connect-api-key-setup-video'); ?>" target="_blank" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'clapperboard.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('Setup Video', 'google-calendar-events'); ?></span>
			</a>
			<a href="<?php echo simcal_ga_campaign_url('https://simplecalendar.io/go/faq', 'core-plugin', 'connect-api-key-faq'); ?>" target="_blank" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'question-white.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('FAQ', 'google-calendar-events'); ?></span>
			</a>
			<a href="<?php echo simcal_ga_campaign_url('https://simplecalendar.io/go/support', 'core-plugin', 'connect-api-key-support'); ?>" target="_blank" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'headphone.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('Support Form', 'google-calendar-events'); ?></span>
			</a>
		</div>
	</div>
</section>
