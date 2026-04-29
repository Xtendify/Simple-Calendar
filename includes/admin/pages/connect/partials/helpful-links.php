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
} ?>

<section class="sc_section sc_section_last sc_connect_helpful_links">
	<div class="sc_setup_card">
		<h3 class="sc_h3 sc_section_title">
			<?php esc_html_e('Helpful Links', 'google-calendar-events'); ?>
		</h3>
		<div class="sc_helpful_links_cards_wrapper">
			<a
				href="<?php echo esc_url(
    	simcal_ga_campaign_url('https://docs.simplecalendar.io/', 'core-plugin', 'connect-documentation'),
    ); ?>"
				target="_blank"
				rel="noopener noreferrer"
				class="sc_helpful_link_card"
			>
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
			<a
				href="<?php echo esc_url(
    	simcal_ga_campaign_url('https://simplecalendar.io/contact', 'core-plugin', 'connect-support'),
    ); ?>"
				target="_blank"
				rel="noopener noreferrer"
				class="sc_helpful_link_card"
			>
				<span class="sc_icon--circle">
					<img src="<?php echo esc_url($assets_base . 'headphone.svg'); ?>" alt="" />
				</span>
				<span class="sc_helpful_link_card_label"><?php esc_html_e('Support Form', 'google-calendar-events'); ?></span>
			</a>
		</div>
	</div>
</section>
