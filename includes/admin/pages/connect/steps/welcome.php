<?php
/**
 * Step: Welcome.
 *
 * Variables expected:
 * - string $video_url
 */
if (!defined('ABSPATH')) {
	exit();
} ?>
<div class="sc_connect_welcome_outer">
	<div class="sc_connect_welcome_inner">
		<h1 class="sc_h3">
			<?php esc_html_e('Welcome to your new simple calendar', 'google-calendar-events'); ?>
		</h1>
		<p class="sc_connect_welcome_subtitle">
			<?php esc_html_e(
   	'Keep planning in Google Calendar, and display events on your site with 1-click. Simple Calendar keeps everything in sync for you.',
   	'google-calendar-events'
   ); ?>
		</p>

		<div class="sc_connect_welcome_video">
			<img
				src="<?php echo esc_url(SIMPLE_CALENDAR_ASSETS . 'images/pages/connect/welcome-video-placeholder.png'); ?>"
				alt="<?php esc_attr_e('Getting Started with Simple Calendar', 'google-calendar-events'); ?>"
			/>
		</div>

		<div class="sc_connect_welcome_button_wrap">
			<form method="post">
				<?php wp_nonce_field('simcal_connect_welcome_next_action', 'simcal_connect_welcome_nonce'); ?>
				<button type="submit" name="simcal_connect_welcome_next" class="sc_btn sc_btn--blue">
					<?php esc_html_e('Next', 'google-calendar-events'); ?>
				</button>
			</form>
		</div>
	</div>
</div>

