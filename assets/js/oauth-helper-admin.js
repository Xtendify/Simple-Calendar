(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 */
	$(function (e) {
		/* ========================= *
		 * De auth from xtendify*
		 * ========================= */
		$('#oauth_deauthentication').on('click', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var dialog = $btn.data('dialog');
			var $modal = $('#sc_oauth_deauth_modal');

			function setBtnLoading() {
				$btn.removeClass('sc_is_finished sc_btn--red').addClass('sc_is_active');
				$btn.attr('aria-disabled', 'true');
			}

			function clearBtnLoading() {
				$btn.removeClass('sc_is_active');
				$btn.removeAttr('aria-disabled');
			}

			function setBtnSuccess() {
				$btn.removeClass('sc_is_active sc_btn--red').addClass('sc_is_finished');
				$btn.attr('aria-disabled', 'true');
			}

			function setBtnError() {
				$btn.removeClass('sc_is_active sc_is_finished').addClass('sc_btn--red');
				$btn.removeAttr('aria-disabled');
			}

			function runDeauthenticationAjax() {
				$.ajax({
					url: oauth_admin.ajax_url,
					method: 'POST',
					data: {
						action: 'oauth_deauthenticate_site',
						nonce: $('#oauth_action_deauthentication').val(),
					},
					beforeSend: function () {
						setBtnLoading();
					},
					success: function (response) {
						if (response && response.data) {
							// Show success state briefly, then reload to reflect disconnected status.
							setBtnSuccess();
							setTimeout(function () {
								var curUrl = window.location.href;
								var newURL = curUrl.replace('status=1', 'status=0');
								newURL = newURL.replace('auth_token=', '');
								window.location.href = newURL;
							}, 700);
							return;
						}

						clearBtnLoading();
						setBtnError();
					},
					error: function (response) {
						clearBtnLoading();
						setBtnError();
					},
				});
			}

			if ($modal.length) {
				$modal.find('.sc_connect_modal__message').text(typeof dialog === 'string' ? dialog : '');
				$modal.removeClass('is_hidden').attr('aria-hidden', 'false');

				$modal.off('.scDeauth');
				$(document).off('keydown.scDeauth');

				function dismissDeauthModal() {
					$modal.addClass('is_hidden').attr('aria-hidden', 'true');
					$modal.off('.scDeauth');
					$(document).off('keydown.scDeauth');
				}

				$modal.on('click.scDeauth', '[data-sc-deauth-modal-dismiss]', function (ev) {
					ev.preventDefault();
					dismissDeauthModal();
				});

				$modal.on('click.scDeauth', '#sc_oauth_deauth_confirm', function (ev) {
					ev.preventDefault();
					dismissDeauthModal();
					runDeauthenticationAjax();
				});

				$(document).on('keydown.scDeauth', function (ev) {
					if (ev.key === 'Escape' || ev.keyCode === 27) {
						dismissDeauthModal();
					}
				});
			} else {
				if (true !== confirm(dialog)) {
					return;
				}
				runDeauthenticationAjax();
			}
		});
	});

	$(window).load(function () {
		/* ========================= *
		 * Auth Via xtendify Tab*
		 * ========================= */

		$('#simcal-auth-tabs-nav li:first-child').addClass('active');
		$('.simcal-auth-tab-content').hide();
		$('.simcal-auth-tab-content:first').show();

		// Click function
		$('#simcal-auth-tabs-nav li').click(function () {
			$('#simcal-auth-tabs-nav li').removeClass('active');
			$(this).addClass('active');
			$('.simcal-auth-tab-content').hide();

			var activeTab = $(this).find('a').attr('href');
			$(activeTab).fadeIn();
			return false;
		});
	});
})(jQuery);
