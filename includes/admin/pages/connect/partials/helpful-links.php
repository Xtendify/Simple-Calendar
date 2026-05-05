<?php
/**
 * Helpful Links (Connect steps).
 *
 * Variables expected:
 * - string $assets_base
 * - string $setup_video_url
 * - string $sc_helpful_links_support_url
 */
if (!defined('ABSPATH')) {
	exit();
}

$is_pro_active = simcal_is_google_calendar_pro_active(isset($welcome_context) ? $welcome_context : '');

$support_url = $is_pro_active
	? simcal_ga_campaign_url('https://simplecalendar.io/contact', 'core-plugin', 'connect-support')
	: 'https://wordpress.org/support/plugin/google-calendar-events/';
?>

<section class="sc_section sc_section_last sc_connect_helpful_links">
	<div class="sc_setup_card">
		<h3 class="sc_h4 sc_section_title">
			<?php esc_html_e('Helpful Link', 'google-calendar-events'); ?>
		</h3>
		<div class="sc_helpful_links_cards_wrapper">
			<a href="<?php echo simcal_ga_campaign_url(
   	'https://docs.simplecalendar.io/',
   	'core-plugin',
   	'connect-documentation',
   ); ?>" target="_blank" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'document.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('Documentation', 'google-calendar-events'); ?></span>
			</a>
			<a href="<?php echo esc_url(
   	$setup_video_url,
   ); ?>" target="_blank" rel="noopener noreferrer" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'clapperboard.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('Setup Video', 'google-calendar-events'); ?></span>
			</a>
			<a href="<?php echo esc_url($support_url); ?>" target="_blank" class="sc_helpful_link_card">
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'headphone.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('Support Form', 'google-calendar-events'); ?></span>
			</a>
		</div>
	</div>
</section>
