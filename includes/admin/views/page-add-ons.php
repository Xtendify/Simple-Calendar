<?php
/**
 * Add-ons admin page shell.
 *
 * @package SimpleCalendar/Admin
 */

if (!defined('ABSPATH')) {
	exit();
}

$connect_sidebar_scope = function_exists('simcal_prepare_connect_sidebar_scope')
	? simcal_prepare_connect_sidebar_scope()
	: [];
$assets_base = isset($connect_sidebar_scope['assets_base'])
	? (string) $connect_sidebar_scope['assets_base']
	: (string) (SIMPLE_CALENDAR_ASSETS . 'images/admin/');
?>
<div class="wrap sc_root sc_addons" id="simcal-add-ons-page">
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
			<?php
   do_action('simcal_admin_page_' . $page_slug . '_' . $current_tab . '_start');
   do_action('simcal_admin_page_' . $page_slug . '_' . $current_tab . '_end');
   ?>
		</div>
	</div>
</div>
