<?php
/**
 * Tools admin page.
 *
 * @package SimpleCalendar/Admin
 */

if (!defined('ABSPATH')) {
	exit();
} ?>
<div class="wrap" id="simcal-tools-page">
	<h1><?php esc_html_e('Tools', 'google-calendar-events'); ?></h1>
	<?php settings_errors(); ?>
	<?php foreach ($settings_pages as $tab_id => $contents) {
 	if ($tab_id !== $current_tab) {
 		continue;
 	}

 	if (!empty($contents['description'])) {
 		echo '<p>' . wp_kses_post($contents['description']) . '</p>';
 	}

 	do_action('simcal_admin_page_' . $page_slug . '_' . $current_tab . '_start');
 	settings_fields('simple-calendar_' . $page_slug . '_' . $tab_id);
 	do_settings_sections('simple-calendar_' . $page_slug . '_' . $tab_id);
 	do_action('simcal_admin_page_' . $page_slug . '_' . $current_tab . '_end');

 	$submit = apply_filters('simcal_admin_page_' . $page_slug . '_' . $current_tab . '_submit', true);
 	if (true === $submit) {
 		submit_button();
 	}
 } ?>
</div>
