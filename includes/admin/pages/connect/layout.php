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
					<?php echo __('Connect', 'google-calendar-events'); ?>
				</span>
			</div>
		</div>

		<?php $hide_sidebar = !empty($context['hide_sidebar']); ?>
		<div class="sc_connect_page_inner<?php echo $hide_sidebar ? ' sc_connect_page_inner--no-sidebar' : ''; ?>">
			<div>
				<?php
    // Step content. Use EXTR_OVERWRITE so values computed in $context win over variables
    // already set in parent scope (e.g. $welcome_context from connect-menu / connect-controller),
    // which would otherwise be skipped by EXTR_SKIP and break Pro vs core sidebar progress.
    if (is_array($context)) {
    	extract($context, EXTR_OVERWRITE);
    }
    include $step_template_path;
    ?>
			</div>

			<?php if (!$hide_sidebar) { ?>
				<div>
					<?php // Sidebar content.
     if (!empty($context['sidebar_template_path']) && is_string($context['sidebar_template_path'])) {
     	include $context['sidebar_template_path'];
     } ?>
				</div>
			<?php } ?>
		</div>
	</div>
</div>

