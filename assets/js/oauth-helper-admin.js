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
			var spinner = $btn.find('i');
			var dialog = $btn.data('dialog');
			var $modal = $('#sc_oauth_deauth_modal');

			function runDeauthenticationAjax() {
				$.ajax({
					url: oauth_admin.ajax_url,
					method: 'POST',
					data: {
						action: 'oauth_deauthenticate_site',
						nonce: $('#oauth_action_deauthentication').val(),
					},
					beforeSend: function () {
						spinner.toggle();
					},
					success: function (response) {
						if (response.data) {
							var curUrl = window.location.href;
							var newURL = curUrl.replace('status=1', 'status=0');
							newURL = newURL.replace('auth_token=', '');
							window.location.href = newURL;
						} else {
							console.log(response);
							spinner.fadeToggle();
						}
					},
					error: function (response) {
						console.log(response);
						spinner.fadeToggle();
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
