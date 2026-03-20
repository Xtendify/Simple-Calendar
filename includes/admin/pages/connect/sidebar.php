<?php
/**
 * Connect sidebar (progress OR rating/pro cards).
 *
 * Variables expected:
 * - bool   $should_hide_progress
 * - bool   $has_published_calendar
 * - bool   $has_api_key
 * - string $assets_base
 */
if (!defined('ABSPATH')) {
	exit();
}

if (!$should_hide_progress) {
	$progress = [
		'percent' => $has_published_calendar ? 100 : ($has_api_key ? 67 : 33),
		'label' => $has_published_calendar
			? __('100% Ready', 'google-calendar-events')
			: ($has_api_key
				? __('67% Ready', 'google-calendar-events')
				: __('33% Ready', 'google-calendar-events')),
		'items' => [
			[
				'text' => __('Watch Tutorial', 'google-calendar-events'),
				'completed' => true,
				'icon_src' => $assets_base . 'check.svg',
			],
			[
				'id' => 'sc_connect_step_api_key',
				'text' => __('Add API Key', 'google-calendar-events'),
				'completed' => $has_api_key || $has_published_calendar,
				'icon_src' => $assets_base . 'check.svg',
			],
			[
				'id' => 'sc_connect_step_calendar',
				'text' => __('Add New Calendar', 'google-calendar-events'),
				'completed' => $has_published_calendar,
				'icon_src' => $assets_base . 'check.svg',
			],
		],
	];

	$force_complete_styles = $has_published_calendar;
	include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/components/progress.php';
	return;
}
?>
<div class="sc_connect_sidebar_stack">
	<div class="sc_connect_card sc_connect_rating_card">
		<h3 class="sc_connect_rating_title">
			<?php esc_html_e('Loving the Plugin', 'google-calendar-events'); ?>
		</h3>
		<p class="sc_connect_rating_subtitle">
			<?php esc_html_e(
   	'Simple for a reason. Zero technical setup. Easy integration steps with advanced options when you need them.',
   	'google-calendar-events'
   ); ?>
		</p>
		<div class="sc_connect_rating_stars">
			<?php for ($i = 0; $i < 5; $i++) { ?>
				<img src="<?php echo esc_url($assets_base . 'star.svg'); ?>" alt="" class="sc_connect_rating_star" />
			<?php } ?>
		</div>
		<a
			href="https://wordpress.org/support/plugin/google-calendar-events/reviews/?rate=5#new-post"
			target="_blank"
			class="sc_btn sc_btn--light-blue sc_connect_rating_btn"
		>
			<?php esc_html_e('Rate on WordPress.org', 'google-calendar-events'); ?>
		</a>
	</div>

	<div class="sc_connect_card sc_connect_pro_card">
		<h3 class="sc_connect_pro_title">
			<?php esc_html_e('Get Pro to Access', 'google-calendar-events'); ?>
		</h3>
		<p class="sc_connect_pro_subtitle">
			<?php esc_html_e(
   	'Simple Calendar is a Calendar WordPress plugin made for powerful, automated event displays.',
   	'google-calendar-events'
   ); ?>
		</p>
		<ul class="sc_connect_pro_list">
			<li>
				<span class="sc_connect_pro_list_icon">
					<img src="<?php echo esc_url($assets_base . 'check.svg'); ?>" alt="" />
				</span>
				<span class="sc_connect_pro_list_text">
					<?php esc_html_e('Priority 24/7 Support', 'google-calendar-events'); ?>
				</span>
			</li>
			<li>
				<span class="sc_connect_pro_list_icon">
					<img src="<?php echo esc_url($assets_base . 'check.svg'); ?>" alt="" />
				</span>
				<span class="sc_connect_pro_list_text">
					<?php esc_html_e('Private Google Calendars', 'google-calendar-events'); ?>
				</span>
			</li>
			<li>
				<span class="sc_connect_pro_list_icon">
					<img src="<?php echo esc_url($assets_base . 'check.svg'); ?>" alt="" />
				</span>
				<span class="sc_connect_pro_list_text">
					<?php esc_html_e('Attachments & Advanced Display', 'google-calendar-events'); ?>
				</span>
			</li>
			<li>
				<span class="sc_connect_pro_list_icon">
					<img src="<?php echo esc_url($assets_base . 'check.svg'); ?>" alt="" />
				</span>
				<span class="sc_connect_pro_list_text">
					<?php esc_html_e('More Add‑ons & Features', 'google-calendar-events'); ?>
				</span>
			</li>
		</ul>
		<a href="https://simplecalendar.io/addons/google-calendar-pro/" target="_blank" class="sc_btn sc_btn--blue sc_connect_pro_btn">
			<?php esc_html_e('Upgrade to Pro', 'google-calendar-events'); ?>
		</a>
	</div>
</div>

