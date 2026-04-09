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
		<a
			href="<?php echo esc_url($authredirect ?: '#'); ?>"
			class="sc_btn sc_btn--blue"
			data-sc-pro-mark-via-sc="<?php echo $authredirect ? '1' : '0'; ?>"
			data-sc-pro-oauth-url="<?php echo esc_url($authredirect ?: ''); ?>"
			<?php echo $authredirect ? '' : 'aria-disabled="true"'; ?>
		>
			<?php esc_html_e('OAuth via Simple Calendar', 'google-calendar-events'); ?>
		</a>
	<?php } else { ?>
		<a
			href="javascript:void(0);"
			id="oauth_deauthentication"
			class="sc_btn sc_btn--white"
			data-dialog="<?php echo esc_attr(__('Are you sure you want to DeAuthenticate.', 'google-calendar-events')); ?>"
		>
			<?php esc_html_e('Deauthenticate', 'google-calendar-events'); ?>
			<i class="simcal-icon-spinner simcal-icon-spin is_hidden"></i>
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

