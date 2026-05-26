<?php
/**
 * Step: Welcome.
 *
 * Variables expected:
 * - string $assets_base
 * - string $video_url
 * - string $welcome_context
 */
if (!defined('ABSPATH')) {
	exit();
}

$welcome_context = isset($welcome_context) ? (string) $welcome_context : 'core';
if ('appointment' === $welcome_context) {
	$heading = __('Welcome to Appointment booking', 'google-calendar-events');
	$subtitle = __(
		'Explore the Simple Calendar addon that allows Google Calendar appointment bookings directly from your website in real-time, while automatically blocking booked time slots to prevent double bookings.',
		'google-calendar-events',
	);
} elseif ('pro' === $welcome_context) {
	$heading = __('Google Calendar Pro', 'google-calendar-events');
	$subtitle = __(
		'Experience a one-stop solution for 1-click Google Calendar integration, with zero stress of handling complex API keys, Client ID, and Client Secret, powered by Simple Calendar’s pro addons.',
		'google-calendar-events',
	);
} else {
	$heading = __('Get started', 'google-calendar-events');
	$subtitle = __(
		'Experience an easy-breezy integration of Google Calendar with WordPress using the OG Google Calendar WordPress plugin.',
		'google-calendar-events',
	);
}
$video_url = isset($video_url) ? trim((string) $video_url) : '';
$assets_base = isset($assets_base) ? (string) $assets_base : (string) (SIMPLE_CALENDAR_ASSETS . 'images/admin/');
?>
<div class="sc_connect_welcome_outer">
	<div class="sc_connect_welcome_inner">
		<div class="sc_connect_welcome_title_row">
			<span class="sc_logo">
				<a href="<?php echo esc_url(admin_url('admin.php?page=simple-calendar_settings')); ?>" class="sc_logo_link">
					<img
						src="<?php echo esc_url($assets_base . 'logo.png'); ?>"
						alt="<?php esc_attr_e('Simple Calendar', 'google-calendar-events'); ?>"
					/>
				</a>
			</span>
			<h1 class="sc_h3 sc_connect_welcome_heading">
				<?php echo esc_html($heading); ?>
			</h1>
		</div>
		<p class="sc_connect_welcome_subtitle">
			<?php echo esc_html($subtitle); ?>
		</p>

		<div class="sc_connect_welcome_video">
			<?php if ($video_url) { ?>
				<div class="sc_connect_welcome_video_embed">
					<iframe
						class="sc_connect_welcome_video_iframe"
						src="<?php echo esc_url($video_url); ?>"
						title="<?php esc_attr_e('Simple Calendar setup video', 'google-calendar-events'); ?>"
						frameborder="0"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
						referrerpolicy="strict-origin-when-cross-origin"
						allowfullscreen
					></iframe>
				</div>
			<?php } else { ?>
				<img
					src="<?php echo esc_url(SIMPLE_CALENDAR_ASSETS . 'images/pages/connect/welcome-video-placeholder.png'); ?>"
					alt="<?php esc_attr_e('Welcome', 'google-calendar-events'); ?>"
				/>
			<?php } ?>
		</div>

		<div class="sc_connect_welcome_button_wrap">
			<form method="post">
				<?php wp_nonce_field('simcal_connect_welcome_next_action', 'simcal_connect_welcome_nonce'); ?>
				<button type="submit" name="simcal_connect_welcome_next" class="sc_btn sc_btn--blue">
					<?php echo esc_html(__('Continue setup', 'google-calendar-events') . ' →'); ?>
				</button>
			</form>
		</div>
	</div>
</div>

