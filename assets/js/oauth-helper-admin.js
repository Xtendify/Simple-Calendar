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
			var spinner = $(this).find('i'),
				dialog = $(this).data('dialog'),
				reply = confirm(dialog);

			if (true !== reply) {
				return;
			}

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
