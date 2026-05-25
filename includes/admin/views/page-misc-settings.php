<?php
/**
 * Misc Settings admin page.
 *
 * @package SimpleCalendar/Admin
 */

if (!defined('ABSPATH')) {
	exit();
}

$calendars_tab_id = 'calendars';
$advanced_tab_id = 'advanced';

$calendars_contents = isset($settings_pages[$calendars_tab_id]) ? $settings_pages[$calendars_tab_id] : null;
$advanced_contents = isset($settings_pages[$advanced_tab_id]) ? $settings_pages[$advanced_tab_id] : null;

$calendars_card_title = __('General', 'google-calendar-events');
if (
	is_array($calendars_contents) &&
	!empty($calendars_contents['sections']) &&
	is_array($calendars_contents['sections'])
) {
	$first_section = reset($calendars_contents['sections']);
	if (is_array($first_section) && !empty($first_section['title'])) {
		$calendars_card_title = $first_section['title'];
	}
}
$advanced_card_title = __('Additional Settings', 'google-calendar-events');

$connect_sidebar_scope = simcal_prepare_connect_sidebar_scope();
$assets_base = isset($connect_sidebar_scope['assets_base'])
	? (string) $connect_sidebar_scope['assets_base']
	: (string) (SIMPLE_CALENDAR_ASSETS . 'images/admin/');
?>
<div class="wrap sc_root sc_misc_settings" id="simcal-misc-settings-page">
	<div class="sc_connect_page_outer">
		<div class="sc_connect_page_header">
			<div class="sc_connect_page_header_left">
				<span class="sc_logo">
					<a href="<?php echo esc_url(admin_url('admin.php?page=simple-calendar_settings')); ?>" class="sc_logo_link">
						<img
							src="<?php echo esc_url($assets_base . 'logo.png'); ?>"
							alt="<?php esc_attr_e('Simple Calendar', 'google-calendar-events'); ?>"
						/>
					</a>
				</span>
			</div>
		</div>

		<div class="sc_connect_notices">
			<?php settings_errors(); ?>
		</div>

		<div class="sc_container">
			<div class="sc_misc_settings_layout">
				<div class="sc_misc_settings_main">
					<?php if (is_array($calendars_contents)) { ?>
						<div class="sc_setup_card sc_misc_settings_card">
							<h2 class="sc_h4 sc_misc_settings_card_title">
								<?php echo esc_html($calendars_card_title); ?>
							</h2>
							<?php if (!empty($calendars_contents['description'])) { ?>
								<div class="sc_text--body_b3 sc_text--dark"><?php echo wp_kses_post($calendars_contents['description']); ?></div>
							<?php } ?>

							<form id="simcal-misc-settings-form-calendars" class="simcal-misc-settings-general" method="post" action="options.php">
								<?php
        do_action('simcal_admin_page_' . $page_slug . '_' . $calendars_tab_id . '_start');
        settings_fields('simple-calendar_' . $page_slug . '_' . $calendars_tab_id);
        do_settings_sections('simple-calendar_' . $page_slug . '_' . $calendars_tab_id);
        do_action('simcal_admin_page_' . $page_slug . '_' . $calendars_tab_id . '_end');

        $submit_cal = apply_filters('simcal_admin_page_' . $page_slug . '_' . $calendars_tab_id . '_submit', true);
        if (true === $submit_cal) {
        	echo '<input type="submit" name="simcal-misc-settings-form-calendars-submit" class="sc_btn sc_btn--blue" value="' .
        		esc_attr__('Save Changes', 'google-calendar-events') .
        		'"/>';
        }
        ?>
							</form>
						</div>
					<?php } ?>

					<?php if (is_array($advanced_contents)) { ?>
						<div class="sc_setup_card sc_misc_settings_card">
							<h2 class="sc_h4 sc_misc_settings_card_title">
								<?php echo esc_html($advanced_card_title); ?>
							</h2>
							<?php if (!empty($advanced_contents['description'])) { ?>
								<div class="sc_text--body_b3 sc_text--dark"><?php echo wp_kses_post($advanced_contents['description']); ?></div>
							<?php } ?>

							<form id="simcal-misc-settings-form-advanced" method="post" action="options.php">
								<?php
        do_action('simcal_admin_page_' . $page_slug . '_' . $advanced_tab_id . '_start');
        settings_fields('simple-calendar_' . $page_slug . '_' . $advanced_tab_id);
        do_settings_sections('simple-calendar_' . $page_slug . '_' . $advanced_tab_id);
        do_action('simcal_admin_page_' . $page_slug . '_' . $advanced_tab_id . '_end');

        $submit_adv = apply_filters('simcal_admin_page_' . $page_slug . '_' . $advanced_tab_id . '_submit', true);
        if (true === $submit_adv) {
        	echo '<input type="submit" name="simcal-misc-settings-form-advanced-submit" class="sc_btn sc_btn--blue" value="' .
        		esc_attr__('Save Changes', 'google-calendar-events') .
        		'"/>';
        }
        ?>
							</form>
						</div>
					<?php } ?>
				</div>

				<div class="sc_misc_settings_sidebar">
					<?php
     extract($connect_sidebar_scope, EXTR_OVERWRITE);
     $welcome_context = $is_pro_active ? 'pro' : (string) $welcome_context;
     $current_step = 'misc_settings';
     include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/sidebar.php';
     ?>
				</div>
			</div>
		</div>
	</div>
</div>
