(function (window, undefined) {
	'use strict';

	jQuery(function ($) {
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
					url: simcal_admin.ajax_url,
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
	});
})(this);
