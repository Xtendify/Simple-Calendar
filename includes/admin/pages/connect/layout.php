<?php
/**
 * Connect onboarding layout.
 *
 * Variables expected:
 * - string $assets_base
 * - string $step_title
 * - string $step_template_path (absolute path)
 * - array  $context
 */
if (!defined('ABSPATH')) {
	exit();
} ?>
<div class="wrap sc_root" id="simcal-connect-page">
	<div class="sc_connect_page_outer">
		<div class="sc_connect_page_header">
			<div class="sc_connect_page_header_left">
				<span class="sc_logo">
					<a href="<?php echo esc_url(admin_url('admin.php?page=simple-calendar_settings')); ?>" class="sc_logo_link">
						<img src="<?php echo esc_url($assets_base . 'logo.png'); ?>" alt="<?php esc_attr_e(
	'Simple Calendar',
	'google-calendar-events'
); ?>" />
					</a>
				</span>
			</div>
			<div class="sc_connect_page_header_right">
				<span class="sc_text--body_b1">
					<?php echo esc_html($step_title); ?>
				</span>
			</div>
		</div>

		<div class="sc_connect_page_inner">
			<div>
				<?php
    // Step content.
    if (is_array($context)) {
    	extract($context, EXTR_SKIP);
    }
    include $step_template_path;
    ?>
			</div>

			<div>
				<?php // Sidebar content.
    if (!empty($context['sidebar_template_path']) && is_string($context['sidebar_template_path'])) {
    	include $context['sidebar_template_path'];
    } ?>
			</div>
		</div>
	</div>
</div>

