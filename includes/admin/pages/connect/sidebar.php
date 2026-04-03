<?php
/**
 * Connect sidebar (progress OR rating/pro cards).
 *
 * Variables expected:
 * - bool   $should_hide_progress
 * - bool   $has_published_calendar
 * - bool   $has_api_key
 * - bool   $has_oauth_connection
 * - bool   $has_client_credentials
 * - string $welcome_context
 * - bool   $has_published_pro_calendar
 * - string $current_step
 * - string $assets_base
 */
if (!defined('ABSPATH')) {
	exit();
}

if (!$should_hide_progress) {
	$is_pro = isset($welcome_context) && 'pro' === (string) $welcome_context;

	if ($is_pro) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$show_own_credentials = isset($_GET['sc_pro_own']) && '1' === (string) $_GET['sc_pro_own'];
		$is_connection_type_step = isset($current_step) && 'credentials' === (string) $current_step;

		$pro_connection_choice = (string) get_option('simple_calendar_connect_pro_connection_type', '');
		$oauth_health_ok = (string) get_option('simple_calendar_connect_pro_oauth_health_ok', '');
		$has_via_sc_authenticated = '1' === $oauth_health_ok;
		$own_oauth_health_ok = (string) get_option('simple_calendar_connect_pro_own_oauth_health_ok', '');
		$has_own_authenticated = '1' === $own_oauth_health_ok;

		$connection_type_chosen =
			$has_via_sc_authenticated ||
			$has_client_credentials ||
			'via_sc' === $pro_connection_choice ||
			'own' === $pro_connection_choice;

		$has_published_pro_calendar = isset($has_published_pro_calendar) ? (bool) $has_published_pro_calendar : false;

		$percent = 25;
		// Treat successful authentication as "ready" for Pro.
		// Publishing a Pro calendar is still tracked separately, but shouldn't block the progress ring.
		if ($has_published_pro_calendar || $has_via_sc_authenticated || $has_own_authenticated) {
			$percent = 100;
		} elseif ('own' === $pro_connection_choice && $has_client_credentials) {
			$percent = 75;
		} elseif ('via_sc' === $pro_connection_choice || 'own' === $pro_connection_choice) {
			$percent = 50;
		}
		if ($is_connection_type_step && !$show_own_credentials && !$connection_type_chosen && !$has_via_sc_authenticated) {
			$percent = 25;
		}

		$labels = [
			25 => __('25% Ready', 'google-calendar-events'),
			50 => __('50% Ready', 'google-calendar-events'),
			75 => __('75% Ready', 'google-calendar-events'),
			100 => __('100% Ready', 'google-calendar-events'),
		];
		$label = isset($labels[$percent]) ? $labels[$percent] : __('25% Ready', 'google-calendar-events');

		$progress = [
			'percent' => $percent,
			'label' => $label,
			'items' => [
				[
					'text' => __('Watch Tutorial', 'google-calendar-events'),
					'completed' => true,
					'icon_src' => $assets_base . 'check.svg',
				],
				[
					'id' => 'sc_connect_step_connection_type',
					'text' => __('Connection type', 'google-calendar-events'),
					'completed' => $connection_type_chosen || $has_published_pro_calendar,
					'icon_src' => $assets_base . 'check.svg',
				],
				[
					'id' => 'sc_connect_step_credentials',
					'text' => __('Enter Client ID & Secret Key', 'google-calendar-events'),
					'completed' =>
						$has_via_sc_authenticated ||
						$has_own_authenticated ||
						(('own' === $pro_connection_choice || $show_own_credentials) && $has_client_credentials) ||
						$has_published_pro_calendar,
					'icon_src' => $assets_base . 'check.svg',
				],
				[
					'id' => 'sc_connect_step_private',
					'text' => __('Display private calendar', 'google-calendar-events'),
					'completed' => $has_published_pro_calendar || $has_via_sc_authenticated || $has_own_authenticated,
					'icon_src' => $assets_base . 'check.svg',
				],
			],
		];
	} else {
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
	}

	// This class forces all connectors/checkbox rings to green.
	// It should only reflect CORE setup completion, not Pro onboarding progress.
	$force_complete_styles = !$is_pro && $has_published_calendar;
	include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/components/progress.php';
	return;
}
?>
<div class="sc_connect_sidebar_stack">
	<div class="sc_setup_card sc_connect_rating_card">
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

	<div class="sc_setup_card sc_connect_pro_card">
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
		<a href="<?php echo simcal_ga_campaign_url('https://simplecalendar.io/addons/google-calendar-pro/', 'core-plugin', 'connect-sidebar-pro-addon'); ?>" target="_blank" class="sc_btn sc_btn--blue sc_connect_pro_btn">
			<?php esc_html_e('Upgrade to Pro', 'google-calendar-events'); ?>
		</a>
	</div>
</div>

