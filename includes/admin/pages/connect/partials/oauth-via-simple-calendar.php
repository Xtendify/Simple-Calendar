<?php
/**
 * Connect page: OAuth via Simple Calendar buttons.
 *
 * Variables expected:
 * - string $authredirect
 * - bool   $has_oauth_connection
 */
if (!defined('ABSPATH')) {
	exit();
}

$authredirect = isset($authredirect) ? (string) $authredirect : '';
$has_oauth_connection = !empty($has_oauth_connection);

// Needed by `assets/js/oauth-helper-admin.js` for deauthentication.
wp_nonce_field('oauth_action_deauthentication', 'oauth_action_deauthentication');
?>

<div class="sc_connect_form_actions sc_connect_credentials_actions_center">
	<?php if (!$has_oauth_connection) { ?>
		<?php if ($authredirect) { ?>
			<a
				href="<?php echo esc_url($authredirect); ?>"
				class="sc_btn sc_btn--blue"
				data-sc-pro-mark-via-sc="1"
				data-sc-pro-oauth-url="<?php echo esc_url($authredirect); ?>"
			>
				<?php esc_html_e('OAuth via Simple Calendar', 'google-calendar-events'); ?>
			</a>
		<?php } else { ?>
			<button
				type="button"
				class="sc_btn sc_btn--blue sc_btn--disabled"
				disabled
				aria-disabled="true"
			>
				<?php esc_html_e('OAuth via Simple Calendar', 'google-calendar-events'); ?>
			</button>
		<?php } ?>
	<?php } else { ?>
		<a
			href="javascript:void(0);"
			id="oauth_deauthentication"
			class="sc_btn sc_btn--blue-loading"
			data-dialog="<?php echo esc_attr(__('Confirm disconnect?', 'google-calendar-events')); ?>"
		>
			<span class="sc_btn_submit"><?php esc_html_e('Disconnect', 'google-calendar-events'); ?></span>
			<span class="sc_btn_loading" aria-hidden="true">
				<span class="sc_btn--icons sc_btn--loading-icon">autorenew</span>
			</span>
			<span class="sc_btn_check" aria-hidden="true">
				<span class="sc_btn--icons sc_btn--check-icon">check</span>
			</span>
		</a>
		<div id="sc_oauth_deauth_modal" class="sc_connect_modal is_hidden" aria-hidden="true">
			<div class="sc_connect_modal__backdrop" data-sc-deauth-modal-dismiss tabindex="-1"></div>
			<div
				class="sc_connect_modal__panel sc_setup_card"
				role="dialog"
				aria-modal="true"
				aria-labelledby="sc_oauth_deauth_modal_title"
			>
				<p id="sc_oauth_deauth_modal_title" class="sc_connect_modal__message sc_text--body_b1"></p>
				<div class="sc_connect_form_actions sc_connect_modal__actions">
					<button type="button" class="sc_btn sc_btn--blue" id="sc_oauth_deauth_confirm">
						<?php esc_html_e('OK', 'google-calendar-events'); ?>
					</button>
					<button type="button" class="sc_btn sc_btn--white" data-sc-deauth-modal-dismiss>
						<?php esc_html_e('Cancel', 'google-calendar-events'); ?>
					</button>
				</div>
			</div>
		</div>
	<?php } ?>
</div>

