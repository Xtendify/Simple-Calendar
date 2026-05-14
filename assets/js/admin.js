(function (window, undefined) {
	'use strict';

	/**
	 * Password / text toggle for square icon buttons (no document-level listeners).
	 * Binds click on DOMContentLoaded; do not add inline onclick (double handler toggles twice).
	 * Binds click on DOMContentLoaded; do not add inline onclick (double handler toggles twice).
	 * Markup: type="button" data-sc-password-toggle aria-controls="input_id"
	 * Optional: data-sc-label-show / data-sc-label-hide (else simcal_connect.strings when present).
	 */
	(function scRegisterPasswordToggleApi() {
		if (window.__scPasswordToggleApi) {
			return;
		}
		window.__scPasswordToggleApi = true;

		function getToggleLabels(btn) {
			var localized = window.simcal_connect && window.simcal_connect.strings ? window.simcal_connect.strings : {};
			return {
				show: btn.getAttribute('data-sc-label-show') || localized.show_api_key || '',
				hide: btn.getAttribute('data-sc-label-hide') || localized.hide_api_key || '',
			};
		}

		function scPasswordToggleUpdateIcons(btn, input) {
			var imgShow = btn.querySelector('.sc_input_square_show');
			var imgHide = btn.querySelector('.sc_input_square_hide');
			if (!imgShow || !imgHide) {
				return;
			}
			var labels = getToggleLabels(btn);
			if (input.type === 'password') {
				imgShow.setAttribute('hidden', '');
				imgHide.removeAttribute('hidden');
				if (labels.show) {
					btn.setAttribute('aria-label', labels.show);
					btn.setAttribute('title', labels.show);
				}
			} else {
				imgShow.removeAttribute('hidden');
				imgHide.setAttribute('hidden', '');
				if (labels.hide) {
					btn.setAttribute('aria-label', labels.hide);
					btn.setAttribute('title', labels.hide);
				}
			}
		}

		function handleButtonClick(e) {
			if (!e || !e.currentTarget) {
				return;
			}
			var btn = e.currentTarget;
			if (btn.disabled || !btn.hasAttribute('data-sc-password-toggle')) {
				return;
			}
			var inputId = btn.getAttribute('aria-controls');
			if (!inputId) {
				return;
			}
			var input = document.getElementById(inputId);
			if (!input || (input.tagName !== 'INPUT' && input.tagName !== 'TEXTAREA')) {
				return;
			}
			if (input.type !== 'password' && input.type !== 'text') {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			input.type = input.type === 'password' ? 'text' : 'password';
			scPasswordToggleUpdateIcons(btn, input);
		}

		function init(root) {
			var scRoot = root || document;
			var scButtons = scRoot.querySelectorAll('button[data-sc-password-toggle]');
			for (var i = 0; i < scButtons.length; i++) {
				var scBtn = scButtons[i];
				if (scBtn.__scPasswordToggleBound) {
					continue;
				}
				scBtn.__scPasswordToggleBound = true;
				scBtn.addEventListener('click', handleButtonClick);

				var scInputId = scBtn.getAttribute('aria-controls');
				if (scInputId) {
					var scInput = document.getElementById(scInputId);
					if (scInput) {
						scPasswordToggleUpdateIcons(scBtn, scInput);
					}
				}
			}
		}

		window.scPasswordToggle = {
			handleButtonClick: handleButtonClick,
			updateIcons: scPasswordToggleUpdateIcons,
			init: init,
		};

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', function () {
				init(document);
			});
		} else {
			init(document);
		}
	})();

	jQuery(function ($) {
		/* =========================
		 * Relocate notices (SC pages)
		 * ========================= */
		(function relocateScNotices() {
			var $scRoot = $('.sc_root.sc_misc_settings, .sc_root.sc_addons').first();
			if (!$scRoot.length) return;

			var $target = $scRoot.find('.sc_connect_notices').first();
			if (!$target.length) return;

			// Move any notices that ended up inside the main cards back under the header.
			var $cardNotices = $scRoot.find('.sc_container .sc_setup_card .notice');
			if ($cardNotices.length) {
				$cardNotices.each(function () {
					$(this).detach().appendTo($target);
				});
			}
		})();

		/* ======== *
		 * Tooltips *
		 * ======== */

		// Initialize Tooltips (tiptip.js).
		$('.simcal-help-tip').tipTip({
			attribute: 'data-tip',
			delay: 200,
			fadeIn: 50,
			fadeOut: 50,
		});

		// Tooltips to ease shortcode copying.
		$('.simcal-shortcode-tip').tipTip({
			activation: 'click',
			defaultPosition: 'top',
			delay: 200,
			fadeIn: 50,
			fadeOut: 50,
		});

		/* ========== *
		 * Meta Boxes *
		 * ========== */

		var // Calendar Settings Meta Box
			calendarSettings = $('#simcal-calendar-settings'),
			simCalBoxHandle = calendarSettings.find('.simcal-box-handle'),
			boxHandle = calendarSettings.find('.hndle');

		// Move the into the Meta Box header handle.
		$(simCalBoxHandle).appendTo(boxHandle);
		$(function () {
			// Prevent inputs in meta box headings opening/closing contents.
			$(boxHandle).unbind('click.postboxes');
			calendarSettings.on('click', 'h3.hndle', function (event) {
				// If the user clicks on some form input inside the h3 the box should not be toggled.
				if ($(event.target).filter('input, option, label, select').length) {
					return;
				}
				calendarSettings.toggleClass('closed');
			});
		});

		// Tabbed Panels in Settings Meta Box.
		$(document.body)
			.on('simcal-init-tabbed-panels', function () {
				$('.simcal-tabs').show();
				$('.simcal-tabs a').click(function () {
					var panel_wrap = $(this).closest('div.simcal-panels-wrap');
					$('ul.simcal-tabs li', panel_wrap).removeClass('active');
					$(this).parent().addClass('active');
					$('div.simcal-panel', panel_wrap).hide();
					$($(this).attr('href')).show();
					return false;
				});
				$('div.simcal-panels-wrap').each(function () {
					$(this).find('ul.simcal-tabs > li').eq(0).find('a').click();
				});
			})
			.trigger('simcal-init-tabbed-panels');

		// Swap feed type tabs and panels according to selection.
		$('#_feed_type')
			.on('change', function () {
				var selected = $(this).find('option:selected'),
					feed = selected.val(),
					ul = $('.simcal-tabs'),
					tabs = ul.find('> .simcal-feed-type'),
					tab = ul.find('> .simcal-feed-type-' + feed),
					a = ul.find('> li:first-child > a');

				tabs.each(function () {
					$(this).hide();
				});
				tab.show();

				// Toggle the settings lock mask:
				// - core feeds: require Google API key
				// - google-pro feed: requires OAuth (via Simple Calendar or own credentials)
				var settingsContentWrap = calendarSettings.find('.simcal-settings-content-wrap'),
					settingsMask = settingsContentWrap.find('.simcal-settings-mask'),
					hasGoogleApiKey = String(settingsContentWrap.data('sc-has-google-api-key')) === '1',
					hasProAuth = String(settingsContentWrap.data('sc-has-pro-auth')) === '1',
					requiredFeedsRaw = settingsContentWrap.attr('data-sc-api-key-required-feeds'),
					requiredFeeds = ['google', 'grouped-calendars'];

				if (requiredFeedsRaw) {
					try {
						requiredFeeds = JSON.parse(requiredFeedsRaw);
					} catch (e) {
						requiredFeeds = ['google', 'grouped-calendars'];
					}
				}

				var requiresGoogleApiKey = $.inArray(feed, requiredFeeds) !== -1,
					requiresProAuth = String(feed) === 'google-pro',
					shouldShowMask = (requiresGoogleApiKey && !hasGoogleApiKey) || (requiresProAuth && !hasProAuth);

				// Prevent keyboard/screen reader access to the masked settings fields.
				var settingsFields = settingsContentWrap.find('.simcal-settings-fields');
				if (settingsFields.length) {
					settingsFields.attr('aria-hidden', shouldShowMask ? 'true' : 'false');
					if (shouldShowMask) {
						settingsFields.attr('inert', '');
					} else {
						settingsFields.removeAttr('inert');
					}
				}

				settingsContentWrap.toggleClass('simcal-settings-content-wrap--masked', shouldShowMask);
				settingsMask.attr('aria-hidden', shouldShowMask ? 'false' : 'true');
				settingsMask.toggle(shouldShowMask);
				a.trigger('click');
			})
			.trigger('change');

		// Brings back the meta box after all the manipulations above.
		calendarSettings.show();

		// Toggle default calendar settings.
		var defCalViews = $('#_calendar_view_default-calendar'),
			defCalSettings = $('#default-calendar-settings'),
			gridSettings = defCalSettings.find('.simcal-default-calendar-grid'),
			listSettings = defCalSettings.find('.simcal-default-calendar-list'),
			groupedListSettings = defCalSettings.find('.simcal-default-calendar-list-grouped');

		defCalViews
			.on('change', function () {
				var selView = $(this).val();

				if ('grid' == selView) {
					listSettings.hide();
					groupedListSettings.hide();
					gridSettings.show();
				} else if ('list' == selView) {
					gridSettings.hide();
					groupedListSettings.hide();
					listSettings.show();
				} else if ('list-grouped' == selView) {
					gridSettings.hide();
					listSettings.hide();
					groupedListSettings.show();
				}
			})
			.trigger('change');

		var calendar_type = $('#_calendar_type');

		calendar_type
			.on('change', function () {
				$('label[for*="_calendar_view_"]').hide();
				$('#calendar-settings-panel table[id*="-settings"]').hide();

				$('label[for="_calendar_view_' + $(this).val() + '"]').show();
				$('#calendar-settings-panel table[id="' + $(this).val() + '-settings"]').show();
			})
			.trigger('change');

		/* ============ *
		 * Input Fields *
		 * ============ */

		// WordPress color picker.
		$('.simcal-field-color-picker').wpColorPicker();

		// Select2 enhanced select.
		$('.simcal-field-select-enhanced').each(function (e, i) {
			var field = $(i),
				noResults = field.data('noresults'),
				allowClear = field.data('allowclear');

			field.select2({
				allowClear: allowClear != 'undefined' ? allowClear : false,
				placeholder: {
					id: '',
					placeholder: '',
				},
				dir: simcal_admin.text_dir != 'undefined' ? simcal_admin.text_dir : 'ltr',
				tokenSeparators: [','],
				width: '100%',
				language: {
					noResults: function () {
						return noResults != 'undefined' ? noResults : '';
					},
				},
			});
		});

		// jQuery Date Picker.
		var fieldDatePicker = $('.simcal-field-date-picker');
		fieldDatePicker.each(function (e, i) {
			var input = $(i).find('input'),
				args = {
					autoSize: true,
					changeMonth: true,
					changeYear: true,
					dateFormat: 'yy-mm-dd',
					firstDay: 1,
					prevText: '<i class="simcal-icon-left"></i>',
					nextText: '<i class="simcal-icon-right"></i>',
					yearRange: '1900:2050',
					beforeShow: function (input, instance) {
						$('#ui-datepicker-div').addClass('simcal-date-picker');
					},
				};

			$(input).datepicker(args);
			$(input).datepicker('option', $.datepicker.regional[simcal_admin.locale]);
		});

		// Datetime formatter field.
		var fieldDateTime = $('.simcal-field-datetime-format');
		fieldDateTime.sortable({
			items: '> div',
			stop: function () {
				formatDateTime($(this));
			},
		});
		fieldDateTime.each(function (e, i) {
			var select = $(i).find('> div select');

			select.each(function (e, i) {
				$(i).on('change', function () {
					formatDateTime($(this).closest('div.simcal-field-datetime-format'));
				});
			});

			formatDateTime(i);
		});
		// Helper function for datetime formatter field.
		function formatDateTime(field) {
			var input = $(field).find('input'),
				select = $(field).find('> div select'),
				code = $(field).find('code'),
				format = '',
				preview = '';

			select.each(function (i, e) {
				var value = $(e).val(),
					selected = $(e).find('> option:selected');

				if (value.length) {
					if (selected.data('trim')) {
						format = format.trim() + $(e).val();
						preview = preview.trim() + selected.data('preview');
					} else {
						format += $(e).val() + ' ';
						preview += selected.data('preview') + ' ';
					}
				}
			});

			input.val(format);
			code.text(preview);
		}

		// If PHP datetime formatter is used, this will live preview the user input.
		$('.simcal-field-datetime-format-php').each(function (e, i) {
			var input = $(i).find('input'),
				preview = $(i).find('code');

			$(input).on('keyup', function () {
				var data = {
					action: 'simcal_date_i18n_input_preview',
					value: input.val(),
				};
				$.post(simcal_admin.ajax_url, data, function (response) {
					$(preview).text(response.data);
				});
			});
		});

		/* =============== *
		 * Input Fields UI *
		 * =============== */

		// Enforce min or max value on number inputs
		$('input[type="number"].simcal-field').each(function (e, i) {
			var field = $(i),
				min = field.attr('min'),
				max = field.attr('max');

			field.on('change', function () {
				var value = parseInt($(this).val());

				if (min && value < min) {
					$(this).val(min);
				}

				if (max && value > max) {
					$(this).val(max);
				}
			});
		});

		// Show or hide a field when an option is selected.
		$('.simcal-field-switch-other')
			.on('change', function () {
				var options = $(this).find('option');

				options.each(function (e, option) {
					var show = $(option).data('show-field'),
						showMany = $(option).data('show-fields'),
						hide = $(option).data('hide-field'),
						hideMany = $(option).data('hide-fields');

					var fieldShow = show ? $('#' + show) : '',
						fieldHide = hide ? $('#' + hide) : '';

					if ($(option).is(':selected')) {
						if (fieldShow) {
							fieldShow.show();
						}
						if (fieldHide) {
							fieldHide.hide();
						}
						if (showMany) {
							var s = hideMany.split(',');
							$(s).each(function (e, field) {
								$('#' + field).hide();
							});
						}
						if (hideMany) {
							var h = hideMany.split(',');
							$(h).each(function (e, field) {
								$('#' + field).hide();
							});
						}
					}
				});
			})
			.trigger('change');

		// Show another field based on the selection of a field.
		$('.simcal-field-show-other')
			.on('change', function () {
				var options = $(this).find('option');

				options.each(function (e, option) {
					var id = $(option).data('show-field'),
						field = typeof id !== 'undefined' && id.length ? $('#' + id) : '',
						next = typeof id !== 'undefined' && id.length ? field.next() : '';

					if (field.length) {
						if ($(option).is(':selected')) {
							field.show();
							if (next.hasClass('select2')) {
								next.show();
							}
						} else {
							field.hide();
							if (next.hasClass('select2')) {
								next.hide();
							}
						}
					}
				});
			})
			.trigger('change');

		// Show the next field when a particular option is chosen in a field.
		$('.simcal-field-show-next')
			.on('change', function () {
				var value, trigger, el, next;

				if ($(this).is(':checkbox')) {
					el = $(this).parent().next();

					if ($(this).is(':checked')) {
						el.show();
					} else {
						el.hide();
					}
				} else {
					value = $(this).val();
					trigger = $(this).data('show-next-if-value');
					el = $(this).nextUntil().not('i');
					next = el.length ? el.next() : '';

					if (value == trigger) {
						el.show();
						if (next.hasClass('select2')) {
							next.show();
						}
					} else {
						el.hide();
						if (next.hasClass('select2')) {
							next.hide();
						}
					}
				}
			})
			.trigger('change');

		/* ==== *
		 * Misc *
		 * ==== */

		// Clear cache buttons.
		$('#simcal-clear-cache').on('click', function (e) {
			e.preventDefault();

			var spinner = $(this).find('i');

			$.ajax({
				url: simcal_admin.ajax_url,
				method: 'POST',
				data: {
					action: 'simcal_clear_cache',
					id: $(this).data('id'),
					nonce: simcal_admin.nonce,
				},
				beforeSend: function () {
					spinner.fadeToggle();
				},
				success: function () {
					spinner.fadeToggle();
				},
				error: function (response) {
					console.log(response);
				},
			});
		});

		// Newsletter signup
		$('#simcal-drip-signup').on('click', function (e) {
			e.preventDefault();

			var nlMetaBox = $('#simcal-drip'),
				signupDiv = nlMetaBox.find('.signup'),
				thankYou = nlMetaBox.find('.thank-you'),
				nlForm = $('#simcal-drip-form'),
				name = nlMetaBox.find('#simcal-drip-field-first_name'),
				nameReal = nlForm.find('#simcal-drip-real-field-first_name'),
				email = nlMetaBox.find('#simcal-drip-field-email'),
				emailReal = nlForm.find('#simcal-drip-real-field-email');

			nameReal.val(name.val());
			emailReal.val(email.val());

			signupDiv.hide();
			thankYou.show();

			nlForm.submit();
		});

		// Remove the timezone option for "event source" when a grouped calendar is selected.
		$('#_feed_type').on('change', function (e) {
			if ($(this).val() === 'grouped-calendars') {
				$('#use_calendar').remove();
			} else {
				// Don't append "event source default" back if already exists.
				// TODO i18n
				if (!$('#use_calendar').length) {
					var html =
						'<option id="use_calendar" value="use_calendar" data-show-field="_use_calendar_warning">Event source default</option>';
					$('#_feed_timezone_setting').append(html);
				}
			}
		});

		/* ========================= *
		 * Add-on License Management *
		 * ========================= */

		$('.simcal-addon-manage-license').on('click', function (e) {
			e.preventDefault();

			var manage_license_action = '',
				button = $(this),
				buttons = button.closest('.simcal-addon-manage-license-buttons'),
				field = button.closest('.simcal-addon-manage-license-field').find('> input'),
				error = buttons.find('.error');

			function setBtnLoading($btn) {
				if (!$btn || !$btn.length) return;
				$btn.removeClass('sc_is_finished sc_btn--red').addClass('sc_is_active');
				$btn.attr('aria-disabled', 'true');
				$btn.prop('disabled', true);
				$btn.data('inFlight', true);
			}

			function clearBtnLoading($btn) {
				if (!$btn || !$btn.length) return;
				$btn.removeClass('sc_is_active');
				$btn.removeAttr('aria-disabled');
				$btn.prop('disabled', false);
				$btn.data('inFlight', false);
			}

			function setBtnSuccess($btn) {
				if (!$btn || !$btn.length) return;
				$btn.removeClass('sc_is_active sc_btn--red').addClass('sc_is_finished');
				$btn.attr('aria-disabled', 'true');
				$btn.prop('disabled', true);
				$btn.data('inFlight', false);
			}

			function setBtnError($btn) {
				if (!$btn || !$btn.length) return;
				$btn.removeClass('sc_is_active sc_is_finished').addClass('sc_btn--red');
				$btn.removeAttr('aria-disabled');
				$btn.prop('disabled', false);
				$btn.data('inFlight', false);
			}

			if (button.data('inFlight') === true || button.prop('disabled')) {
				return;
			}

			if ($(this).hasClass('activate')) {
				manage_license_action = 'activate_license';
			} else if ($(this).hasClass('deactivate')) {
				manage_license_action = 'deactivate_license';
			} else {
				return;
			}

			$.ajax({
				url: simcal_admin.ajax_url,
				method: 'POST',
				data: {
					action: 'simcal_manage_add_on_license',
					add_on: $(this).data('add-on'),
					license_key: field.val(),
					license_action: manage_license_action,
					nonce: $('#simcal_license_manager').val(),
				},
				beforeSend: function () {
					error.addClass('is_hidden').text('');
					setBtnLoading(button);
				},
				success: function (response) {
					if ('activate_license' == manage_license_action) {
						if ('valid' == response.data) {
							setBtnSuccess(button);
							setTimeout(function () {
								button
									.addClass('is_hidden')
									.removeClass('sc_is_active sc_is_finished')
									.removeAttr('aria-disabled')
									.prop('disabled', false)
									.data('inFlight', false);
								field.attr('disabled', 'disabled');
								$(buttons).find('.label').removeClass('is_hidden');
								$(buttons)
									.find('.deactivate')
									.removeClass('is_hidden')
									.removeClass('sc_is_active sc_is_finished')
									.removeAttr('aria-disabled')
									.prop('disabled', false)
									.data('inFlight', false);
								error.addClass('is_hidden').text('');
							}, 650);
						} else {
							clearBtnLoading(button);
							setBtnError(button);
							error.removeClass('is_hidden').text(response.data);
						}
					} else {
						if ('deactivated' == response.data) {
							setBtnSuccess(button);
							setTimeout(function () {
								button
									.addClass('is_hidden')
									.removeClass('sc_is_active sc_is_finished')
									.removeAttr('aria-disabled')
									.prop('disabled', false)
									.data('inFlight', false);
								field.removeAttr('disabled');
								$(buttons).find('.label').addClass('is_hidden');
								$(buttons)
									.find('.activate')
									.removeClass('is_hidden')
									.removeClass('sc_is_active sc_is_finished')
									.removeAttr('aria-disabled')
									.prop('disabled', false)
									.data('inFlight', false);
								error.addClass('is_hidden').text('');
							}, 650);
						} else {
							clearBtnLoading(button);
							setBtnError(button);
							error.removeClass('is_hidden').text(response.data);
						}
					}
				},
				error: function (response) {
					console.log(response);
					clearBtnLoading(button);
					setBtnError(button);
				},
			});
		});

		$('#simcal-reset-licenses').on('click', function (e) {
			e.preventDefault();

			var spinner = $(this).find('i'),
				dialog = $(this).data('dialog'),
				$modal = $('#sc_reset_licenses_modal');

			function runResetAjax() {
				$.ajax({
					url: simcal_admin.ajax_url,
					method: 'POST',
					data: {
						action: 'simcal_reset_add_ons_licenses',
						nonce: $('#simcal_license_manager').val(),
					},
					beforeSend: function () {
						spinner.removeClass('is_hidden');
					},
					success: function (response) {
						if ('success' == response.data) {
							location.reload();
						} else {
							console.log(response);
						}
					},
					error: function (response) {
						console.log(response);
					},
					complete: function () {
						spinner.addClass('is_hidden');
					},
				});
			}

			if ($modal.length) {
				$modal.find('.sc_connect_modal__message').text(typeof dialog === 'string' ? dialog : '');
				$modal.removeClass('is_hidden').attr('aria-hidden', 'false');

				$modal.off('.scResetLic');
				$(document).off('keydown.scResetLic');

				function dismissResetModal() {
					$modal.addClass('is_hidden').attr('aria-hidden', 'true');
					$modal.off('.scResetLic');
					$(document).off('keydown.scResetLic');
				}

				$modal.on('click.scResetLic', '[data-sc-reset-licenses-modal-dismiss]', function (ev) {
					ev.preventDefault();
					dismissResetModal();
				});

				$modal.on('click.scResetLic', '#sc_reset_licenses_confirm', function (ev) {
					ev.preventDefault();
					dismissResetModal();
					runResetAjax();
				});

				$(document).on('keydown.scResetLic', function (ev) {
					if (ev.key === 'Escape' || ev.keyCode === 27) {
						dismissResetModal();
					}
				});
				return;
			}

			if (true !== confirm(dialog)) {
				return;
			}

			runResetAjax();
		});

		/* =========================
		 * Connect page: copy helper
		 * ========================= */
		function simcalBindCopyTargets(rootEl) {
			var ctxEl = rootEl && rootEl.length ? rootEl : $(document);
			ctxEl
				.find('[data-sc-copy-target-field]')
				.off('click.simcalCopy')
				.on('click.simcalCopy', function (e) {
					e.preventDefault();
					var targetId = $(this).attr('data-sc-copy-target-field');
					if (!targetId) return;
					var el = document.getElementById(targetId);
					if (!el) return;
					var text = String(el.value || '');
					if (!text) return;

					function markCopiedUi() {
						// Only requested UX: Pro Redirect URL field border turns green after copy.
						if (targetId !== 'sc_google_pro_redirect_url') return;
						$(el).addClass('sc_input--copied');
						window.clearTimeout(el._scCopyTimer);
						el._scCopyTimer = window.setTimeout(function () {
							$(el).removeClass('sc_input--copied');
						}, 1600);
					}

					function fallbackCopy() {
						try {
							el.focus();
							el.select();
							document.execCommand('copy');
							markCopiedUi();
						} catch (err) {}
					}

					if (navigator.clipboard && navigator.clipboard.writeText) {
						navigator.clipboard
							.writeText(text)
							.then(function () {
								markCopiedUi();
							})
							.catch(function () {
								fallbackCopy();
							});
					} else {
						fallbackCopy();
					}
				});
		}

		// Bind once for initial DOM (avoids document-level delegated click).
		simcalBindCopyTargets($('#simcal-connect-page').length ? $('#simcal-connect-page') : $(document));
	});

	/* =========================================
	 * Connect page: eye toggle + API key validation (DOM ready)
	 * ========================================= */
	jQuery(function ($) {
		var _scConnectPageEl = $('#simcal-connect-page');
		if (!_scConnectPageEl.length) {
			return;
		}

		// Use localized config only (strings come from wp_localize_script).
		var connectCfg = window.simcal_connect || {
			ajax_url: (window.simcal_admin && window.simcal_admin.ajax_url) || '',
			nonce: '',
			check_icon_url: '',
			warning_icon_url: '',
			oauth_check_nonce: '',
			strings: {},
		};

		function simcalAdminAjaxUrl() {
			var href =
				(typeof globalThis !== 'undefined' && globalThis.location && typeof globalThis.location.href === 'string'
					? globalThis.location.href
					: document && document.location && typeof document.location.href === 'string'
						? document.location.href
						: '') || '';
			var wpAdminPos = href.indexOf('/wp-admin/');
			var derived = '';
			if (wpAdminPos > -1) {
				derived = href.substring(0, wpAdminPos) + '/wp-admin/admin-ajax.php';
			} else {
				derived = '/wp-admin/admin-ajax.php';
			}
			var candidates = [
				(connectCfg && connectCfg.ajax_url) || '',
				(window.simcal_admin && window.simcal_admin.ajax_url) || '',
				typeof window.ajaxurl === 'string' ? window.ajaxurl : '',
				derived,
			];
			var urls = [];
			for (var i = 0; i < candidates.length; i++) {
				var c = String(candidates[i] || '').trim();
				if (!c || $.inArray(c, urls) !== -1) {
					continue;
				}
				urls.push(c);
			}
			return urls;
		}

		function simcalXhrGetContentType(xhr) {
			if (!xhr || typeof xhr.getResponseHeader !== 'function') {
				return '';
			}
			try {
				return String(xhr.getResponseHeader('content-type') || '').toLowerCase();
			} catch (e) {
				return '';
			}
		}

		function simcalTryParseJson(text) {
			if (typeof text !== 'string') {
				return null;
			}
			var trimmed = text.trim();
			if (!trimmed) {
				return null;
			}
			var startsLikeJson = trimmed.charAt(0) === '{' || trimmed.charAt(0) === '[';
			if (!startsLikeJson) {
				return null;
			}
			try {
				return JSON.parse(trimmed);
			} catch (e) {
				return null;
			}
		}

		function simcalIsNonJsonResponse(xhr) {
			if (!xhr) return true;
			if (xhr.responseJSON && typeof xhr.responseJSON === 'object') {
				return false;
			}
			var ct = simcalXhrGetContentType(xhr);
			if (ct && ct.indexOf('application/json') !== -1) {
				return false;
			}
			var parsed = simcalTryParseJson(xhr.responseText);
			return !parsed;
		}

		function simcalNormalizeAjaxJson(resOrXhr) {
			// Accept either already-parsed JSON, or an XHR with responseJSON/responseText.
			if (!resOrXhr) return null;
			if (typeof resOrXhr === 'object' && !resOrXhr.getResponseHeader) {
				return resOrXhr;
			}
			var xhr = resOrXhr;
			if (xhr && xhr.responseJSON && typeof xhr.responseJSON === 'object') {
				return xhr.responseJSON;
			}
			return simcalTryParseJson(xhr && xhr.responseText);
		}

		function scApiKeyBadgeNodes() {
			return $('[data-sc-google-api-key-health="1"]');
		}

		function scApiKeyBadgeHolders() {
			return $('[data-sc-google-api-key-badge-holder="1"]');
		}

		function scApiKeyBadgeDetails() {
			return $('[data-sc-google-api-key-health-detail="1"]');
		}

		function scApiKeyBadgeSetChecking() {
			scApiKeyBadgeHolders().removeClass('is_hidden');
			var badges = scApiKeyBadgeNodes();
			badges
				.removeClass('sc_connect_oauth_status_header_badge--ok sc_connect_oauth_status_header_badge--error')
				.addClass('sc_connect_oauth_status_header_badge--pending');
			badges
				.find('.sc_connect_oauth_status_header_label')
				.first()
				.text((connectCfg.strings && connectCfg.strings.oauth_checking) || 'Checking…');
			scApiKeyBadgeDetails().addClass('is_hidden').empty();
		}

		function scApiKeyBadgeSetOk() {
			scApiKeyBadgeHolders().removeClass('is_hidden');
			var badges = scApiKeyBadgeNodes();
			badges
				.removeClass('sc_connect_oauth_status_header_badge--pending sc_connect_oauth_status_header_badge--error')
				.addClass('sc_connect_oauth_status_header_badge--ok');
			badges
				.find('.sc_connect_oauth_status_header_label')
				.first()
				.text((connectCfg.strings && connectCfg.strings.oauth_connected) || 'Connected');
			scApiKeyBadgeDetails().addClass('is_hidden').empty();
		}

		function scApiKeyBadgeSetInvalid() {
			scApiKeyBadgeHolders().removeClass('is_hidden');
			var badges = scApiKeyBadgeNodes();
			badges
				.removeClass('sc_connect_oauth_status_header_badge--pending sc_connect_oauth_status_header_badge--ok')
				.addClass('sc_connect_oauth_status_header_badge--error');
			badges.find('.sc_connect_oauth_status_header_label').first().text('API key is not valid.');
			scApiKeyBadgeDetails().addClass('is_hidden').empty();
		}

		function scApiKeyBadgeSetError(message) {
			scApiKeyBadgeHolders().removeClass('is_hidden');
			var badges = scApiKeyBadgeNodes();
			badges
				.removeClass('sc_connect_oauth_status_header_badge--pending sc_connect_oauth_status_header_badge--ok')
				.addClass('sc_connect_oauth_status_header_badge--error');
			badges
				.find('.sc_connect_oauth_status_header_label')
				.first()
				.text((connectCfg.strings && connectCfg.strings.oauth_error) || 'Error');
			var intro = (connectCfg.strings && connectCfg.strings.google_api_key_public_calendar_failed) || '';
			var msg = message ? String(message) : '';
			var detail = scApiKeyBadgeDetails();
			detail.removeClass('is_hidden');
			detail.text(intro && msg ? intro + ' ' + msg : intro || msg || '');
		}

		/* =========================================
		 * Saved API key health check (public calendar fetch)
		 * ========================================= */
		(function googleApiKeySavedHealthCheck() {
			var holders = scApiKeyBadgeHolders();
			if (!holders.length || holders.first().hasClass('is_hidden')) {
				return;
			}

			scApiKeyBadgeSetChecking();

			var ajaxUrls = simcalAdminAjaxUrl();
			if (!ajaxUrls.length) {
				scApiKeyBadgeSetError('');
				return;
			}

			var healthNonce = (connectCfg && connectCfg.google_api_key_health_nonce) || '';

			function healthAtIndex(idx) {
				$.ajax({
					url: ajaxUrls[idx],
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'simcal_connect_google_api_key_health_check',
						nonce: healthNonce,
					},
				})
					.done(function (res) {
						if (typeof res === 'string') {
							var parsedRes = simcalTryParseJson(res);
							if (parsedRes) {
								res = parsedRes;
							} else if (idx + 1 < ajaxUrls.length) {
								healthAtIndex(idx + 1);
								return;
							}
						}
						var connected = !!(res && res.success && res.data && res.data.connected);
						if (connected) {
							scApiKeyBadgeSetOk();
							return;
						}
						if (res && res.data && res.data.reason === 'api_keys_not_supported') {
							// Treat this as connected for onboarding UI, but don't show the error text.
							scApiKeyBadgeSetOk();
							return;
						}
						if (res && res.data && res.data.reason === 'api_key_invalid') {
							scApiKeyBadgeSetInvalid();
							return;
						}
						var msg = res && res.data && res.data.message ? String(res.data.message) : '';
						scApiKeyBadgeSetError(msg);
					})
					.fail(function (xhr) {
						var json = simcalNormalizeAjaxJson(xhr);
						if (json) {
							var connected = !!(json && json.success && json.data && json.data.connected);
							if (connected) {
								scApiKeyBadgeSetOk();
								return;
							}
							if (json && json.data && json.data.reason === 'api_keys_not_supported') {
								scApiKeyBadgeSetOk();
								return;
							}
							if (json && json.data && json.data.reason === 'api_key_invalid') {
								scApiKeyBadgeSetInvalid();
								return;
							}
							var msg = json && json.data && json.data.message ? String(json.data.message) : '';
							scApiKeyBadgeSetError(msg);
							return;
						}
						if (simcalIsNonJsonResponse(xhr) && idx + 1 < ajaxUrls.length) {
							healthAtIndex(idx + 1);
							return;
						}
						scApiKeyBadgeSetError('');
					});
			}

			healthAtIndex(0);
		})();

		/* =========================================
		 * Pro OAuth health check (calendar list)
		 * ========================================= */
		(function oauthConnectionHealthCheck() {
			var statusWrap = $('.sc_connect_auth_status_center[data-sc-oauth-check="1"]').first();
			var headerBadge = $('#sc_connect_oauth_status_header_badge[data-sc-oauth-check="1"]').first();
			if (!statusWrap.length && !headerBadge.length) {
				return;
			}

			var statusText = statusWrap.length ? statusWrap.find('.sc_connect_oauth_status').first() : $();
			var linkIcon = statusWrap.length ? statusWrap.find('.sc_connect_auth_status_link_icon').first() : $();

			var iconLinkUrl = statusWrap.length ? String(statusWrap.attr('data-sc-oauth-icon-link') || '') : '';
			var iconUnlinkUrl = statusWrap.length ? String(statusWrap.attr('data-sc-oauth-icon-unlink') || '') : '';

			function setHeaderChecking() {
				if (!headerBadge.length) return;
				headerBadge
					.removeClass('sc_connect_oauth_status_header_badge--ok sc_connect_oauth_status_header_badge--error')
					.addClass('sc_connect_oauth_status_header_badge--pending');
				headerBadge
					.find('.sc_connect_oauth_status_header_label')
					.first()
					.text((connectCfg.strings && connectCfg.strings.oauth_checking) || 'Checking…');
			}

			function setHeaderConnected() {
				if (!headerBadge.length) return;
				headerBadge
					.removeClass('sc_connect_oauth_status_header_badge--pending sc_connect_oauth_status_header_badge--error')
					.addClass('sc_connect_oauth_status_header_badge--ok');
				headerBadge
					.find('.sc_connect_oauth_status_header_label')
					.first()
					.text((connectCfg.strings && connectCfg.strings.oauth_connected) || 'Connected');
			}

			function setHeaderError(message) {
				if (!headerBadge.length) return;
				headerBadge
					.removeClass('sc_connect_oauth_status_header_badge--pending sc_connect_oauth_status_header_badge--ok')
					.addClass('sc_connect_oauth_status_header_badge--error');
				headerBadge
					.find('.sc_connect_oauth_status_header_label')
					.first()
					.text(message || (connectCfg.strings && connectCfg.strings.oauth_error) || 'Error');
			}

			function markProSidebarStepComplete($li) {
				if (!$li.length || $li.hasClass('is_completed')) {
					return;
				}
				$li.addClass('is_completed');
				var $cb = $li.find('.sc_checklist_checkbox');
				if ($cb.length && !$cb.find('img').length) {
					$cb.html('<img src="' + connectCfg.check_icon_url + '" alt="" class="sc_checklist_icon" />');
				}
			}

			function applyConnectedUi() {
				setHeaderConnected();
				if (linkIcon.length && iconLinkUrl) {
					linkIcon.attr('src', iconLinkUrl);
				}
				if (statusText.length) {
					statusText
						.removeClass('sc_connect_oauth_status--error sc_connect_oauth_status--disconnected')
						.addClass('sc_connect_oauth_status--ok');
					statusText.text((connectCfg.strings && connectCfg.strings.oauth_connected) || 'Connected');
				}

				var subtitle = $('.sc_connect_credentials_subtitle').first();
				if (subtitle.length) {
					var connectedSubtitle = String(subtitle.attr('data-sc-subtitle-connected') || '');
					if (connectedSubtitle) {
						subtitle.text(connectedSubtitle);
					}
				}

				// Pro sidebar: OAuth health success means authentication is complete,
				// but onboarding should only be 100% when a Pro calendar is created.
				markProSidebarStepComplete($('#sc_connect_step_connection_type'));
				markProSidebarStepComplete($('#sc_connect_step_credentials'));

				var scCircle = $('#sc_connect_progress_circle');
				var scProgressText = $('#sc_connect_progress_text');
				if (scCircle.length) {
					// If the server already marked the final step complete (i.e. Pro calendar exists),
					// keep the UI at 100% instead of forcing 75%.
					var $privateStep = $('#sc_connect_step_private');
					var hasProCalendar = $privateStep.length && $privateStep.hasClass('is_completed');
					var isAlready100 = scCircle.hasClass('sc_progress_circle--100');

					scCircle.addClass('sc_connect_progress_anim');
					if (hasProCalendar || isAlready100) {
						markProSidebarStepComplete($privateStep);
						scCircle.removeClass('sc_progress_circle--33 sc_progress_circle--67').addClass('sc_progress_circle--100');
						scCircle[0].style.setProperty('--sc-progress', '100');
						if (scProgressText.length) {
							scProgressText.text((connectCfg.strings && connectCfg.strings['100_ready']) || '100% Ready');
						}
					} else {
						// 75% uses the 67% circle styles but updates the conic-gradient via CSS var.
						scCircle.removeClass('sc_progress_circle--33 sc_progress_circle--100').addClass('sc_progress_circle--67');
						scCircle[0].style.setProperty('--sc-progress', '75');
						if (scProgressText.length) {
							scProgressText.text((connectCfg.strings && connectCfg.strings['75_ready']) || '75% Ready');
						}
					}
					var $progressRow = scCircle.closest('.sc_row.sc_row_align_start');
					if ($progressRow.length) {
						$progressRow.toggleClass('sc_connect_progress_is_complete', hasProCalendar || isAlready100);
					}
					setTimeout(function () {
						scCircle.removeClass('sc_connect_progress_anim');
					}, 1200);
				}

				// Pro own-credentials flow: show "Add New Calendar" CTA after successful auth
				// (button is hidden server-side when a Pro calendar already exists).
				var scAddProBtn = $('#sc_connect_add_pro_calendar_btn');
				if (scAddProBtn.length && String(scAddProBtn.attr('data-sc-can-unhide') || '') === '1') {
					scAddProBtn.removeClass('is_hidden');
				}
			}

			function applyDisconnectedUi(message) {
				setHeaderError(message);
				if (linkIcon.length && iconUnlinkUrl) {
					linkIcon.attr('src', iconUnlinkUrl);
				}
				if (statusText.length) {
					statusText
						.removeClass('sc_connect_oauth_status--ok')
						.removeClass('sc_connect_oauth_status--error')
						.addClass('sc_connect_oauth_status--disconnected');
					statusText.text(message || (connectCfg.strings && connectCfg.strings.oauth_not_connected) || 'Not Connected');
				}

				var subtitle = $('.sc_connect_credentials_subtitle').first();
				if (subtitle.length) {
					var disconnectedSubtitle = String(subtitle.attr('data-sc-subtitle-disconnected') || '');
					if (disconnectedSubtitle) {
						subtitle.text(disconnectedSubtitle);
					}
				}
			}

			setHeaderChecking();
			if (statusText.length) {
				statusText
					.removeClass(
						'sc_connect_oauth_status--ok sc_connect_oauth_status--disconnected sc_connect_oauth_status--error'
					)
					.text((connectCfg.strings && connectCfg.strings.oauth_checking) || 'Checking…');
			}
			if (linkIcon.length && iconUnlinkUrl) {
				linkIcon.attr('src', iconUnlinkUrl);
			}

			var ajaxUrls = simcalAdminAjaxUrl();
			if (!ajaxUrls.length) {
				applyDisconnectedUi(
					(connectCfg.strings && connectCfg.strings.oauth_ajax_url_not_found) || 'Ajax URL not found.'
				);
				console.log('ajaxUrls.length', ajaxUrls.length);
				return;
			}

			var oauthCheckAction = statusWrap.length
				? String(statusWrap.attr('data-sc-oauth-check-action') || 'simcal_connect_oauth_via_sc_check')
				: String(headerBadge.attr('data-sc-oauth-check-action') || 'simcal_connect_oauth_via_sc_check');

			function oauthCheckAtIndex(idx) {
				$.ajax({
					url: ajaxUrls[idx],
					type: 'POST',
					dataType: 'json',
					data: {
						action: oauthCheckAction,
						nonce: connectCfg.oauth_check_nonce || '',
					},
				})
					.done(function (res) {
						if (typeof res === 'string') {
							var parsedRes = simcalTryParseJson(res);
							if (parsedRes) {
								res = parsedRes;
							} else if (idx + 1 < ajaxUrls.length) {
								oauthCheckAtIndex(idx + 1);
								return;
							}
						}
						var isOk = !!(res && res.success);
						var isConnected = !!(res && res.data && res.data.connected);
						if (isOk && isConnected) {
							applyConnectedUi();
							return;
						}
						var msg = res && res.data && res.data.message ? String(res.data.message) : '';
						applyDisconnectedUi(msg || '');
						console.log('done oauthCheckAtIndex', res);
					})
					.fail(function (xhr) {
						var json = simcalNormalizeAjaxJson(xhr);
						if (json) {
							var isOk = !!(json && json.success);
							var isConnected = !!(json && json.data && json.data.connected);
							if (isOk && isConnected) {
								applyConnectedUi();
								return;
							}
							var msg = json && json.data && json.data.message ? String(json.data.message) : '';
							applyDisconnectedUi(msg || '');
							return;
						}
						if (simcalIsNonJsonResponse(xhr) && idx + 1 < ajaxUrls.length) {
							oauthCheckAtIndex(idx + 1);
							return;
						}
						applyDisconnectedUi(
							(connectCfg.strings && connectCfg.strings.oauth_not_comunicate) || 'Not able to communicate with Google.'
						);
						console.log('fail oauthCheckAtIndex');
					});
			}

			oauthCheckAtIndex(0);
		})();

		/* Pro Connect: persist "OAuth via Simple Calendar" choice before redirect (progress sidebar). */
		$(document).on('click', 'a[data-sc-pro-mark-via-sc="1"]', function (e) {
			var $a = $(this);
			var targetUrl = String($a.attr('data-sc-pro-oauth-url') || $a.attr('href') || '').trim();
			if (!targetUrl || targetUrl === '#') {
				return;
			}
			e.preventDefault();
			var nonce = (connectCfg && connectCfg.mark_pro_connection_nonce) || '';
			var ajaxUrls = simcalAdminAjaxUrl();
			if (!ajaxUrls.length) {
				window.location.assign(targetUrl);
				return;
			}
			function postMark(i) {
				$.ajax({
					url: ajaxUrls[i],
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'simcal_mark_pro_connection_via_sc',
						nonce: nonce,
					},
				}).always(function () {
					window.location.assign(targetUrl);
				});
			}
			postMark(0);
		});

		/* API key eye toggle: handled globally via [data-sc-password-toggle] + aria-controls (see top of file). */

		var scConnectForm = $('#simcal-connect-page-form');
		if (scConnectForm.length && scConnectForm.find('[data-sc-connect-validate-btn]').length) {
			var scInput = $('#sc_google_api_key');
			var scConnectFieldWrap = $('#sc_connect_api_key_wrap');
			var scConnectMsgWrap = $('#sc_connect_api_key_msg_wrap');
			var scmsgError = $('#sc_connect_api_key_msg_error');
			var scmsgSuccess = $('#sc_connect_api_key_msg_success');
			var scValidateBtn = $('[data-sc-connect-validate-btn]');
			var scValidateBtnEl = scValidateBtn.get(0) || null;
			var validating = false;
			var originalBtnHtml = scValidateBtnEl ? scValidateBtn.html() : '';
			var originalBtnClass = scValidateBtnEl ? scValidateBtn.attr('class') : '';
			var resetTimer = null;
			var submitTimer = null;

			function showInlineFlex($el) {
				if (!$el || !$el.length) {
					return;
				}
				$el.css('display', 'inline-flex');
			}

			function resetVisualState() {
				if (resetTimer) {
					clearTimeout(resetTimer);
					resetTimer = null;
				}
				if (submitTimer) {
					clearTimeout(submitTimer);
					submitTimer = null;
				}
				scConnectFieldWrap.removeClass('sc_input--error sc_input--success');
				scConnectMsgWrap.hide();
				scmsgError.hide();
				scmsgSuccess.hide();
				if (scValidateBtnEl) {
					scValidateBtn.attr('class', originalBtnClass);
					scValidateBtn.html(originalBtnHtml);
					scValidateBtn.prop('disabled', false);
				}
			}

			function animateProgressToApiKeyCompleted() {
				var sccircle = $('#sc_connect_progress_circle');
				var scProgressText = $('#sc_connect_progress_text');
				var scOnBoardingStep = $('#sc_connect_step_api_key');
				if (!sccircle.length) return;

				if (scOnBoardingStep.length && !scOnBoardingStep.hasClass('is_completed')) {
					scOnBoardingStep.addClass('is_completed');
					var scCheckBox = scOnBoardingStep.find('.sc_checklist_checkbox');
					if (scCheckBox.length && !scCheckBox.find('img').length) {
						scCheckBox.html('<img src="' + connectCfg.check_icon_url + '" alt="" class="sc_checklist_icon" />');
					}
				}

				sccircle.addClass('sc_connect_progress_anim');
				sccircle[0].style.setProperty('--sc-progress', '67');
				if (scProgressText.length) {
					scProgressText.text((connectCfg.strings && connectCfg.strings['67_ready']) || '');
				}
				setTimeout(function () {
					sccircle.removeClass('sc_connect_progress_anim');
				}, 1200);
			}

			scInput.on('input', function () {
				scConnectForm.removeData('scValidated');
				resetVisualState();
				if (scApiKeyBadgeHolders().not('.is_hidden').length) {
					scApiKeyBadgeSetChecking();
				}
			});

			scConnectForm.on('submit', function (e) {
				if (scConnectForm.data('scValidated') === true) {
					return;
				}
				e.preventDefault();
				if (validating) {
					return;
				}
				resetVisualState();
				var inputEl = (scInput && scInput.length ? scInput[0] : null) || document.getElementById('sc_google_api_key');
				var rawKey = ((inputEl && typeof inputEl.value === 'string' ? inputEl.value : '') || '').trim();
				if (scValidateBtnEl) {
					// Show loading state immediately on submit attempt.
					scValidateBtn.removeClass('sc_is_finished sc_btn--red').addClass('sc_is_active').prop('disabled', true);
				}
				if (!rawKey) {
					scConnectFieldWrap.removeClass('sc_input--success').addClass('sc_input--error');
					scConnectMsgWrap.show();
					scmsgSuccess.hide();
					scmsgError.show();
					showInlineFlex(scmsgError);
					scmsgError
						.find('.sc_icon_warning_label')
						.text((connectCfg.strings && connectCfg.strings.please_enter_api_key) || '');
					if (scValidateBtnEl) {
						scValidateBtn.removeClass('sc_is_active sc_is_finished').addClass('sc_btn--red').prop('disabled', false);
					}
					resetTimer = setTimeout(resetVisualState, 10000);
					return;
				}

				validating = true;
				scConnectFieldWrap.removeClass('sc_input--error sc_input--success');
				scConnectMsgWrap.hide();
				scmsgError.hide();
				scmsgSuccess.hide();

				var ajaxNonce = (connectCfg && connectCfg.nonce) || scConnectForm.attr('data-sc-connect-validate-nonce') || '';
				var fallbackAjaxUrl = (function () {
					var href =
						(typeof globalThis !== 'undefined' && globalThis.location && typeof globalThis.location.href === 'string'
							? globalThis.location.href
							: document && document.location && typeof document.location.href === 'string'
								? document.location.href
								: '') || '';
					var wpAdminPos = href.indexOf('/wp-admin/');
					if (wpAdminPos > -1) {
						return href.substring(0, wpAdminPos) + '/wp-admin/admin-ajax.php';
					}
					return '/wp-admin/admin-ajax.php';
				})();
				var ajaxCandidates = [
					(connectCfg && connectCfg.ajax_url) || '',
					window.ajaxurl || '',
					(window.simcal_admin && window.simcal_admin.ajax_url) || '',
					fallbackAjaxUrl,
				];
				var ajaxUrls = [];
				for (var i = 0; i < ajaxCandidates.length; i++) {
					var candidate = String(ajaxCandidates[i] || '').trim();
					if (!candidate || $.inArray(candidate, ajaxUrls) !== -1) {
						continue;
					}
					ajaxUrls.push(candidate);
				}

				function validateRequestAtIndex(index) {
					$.ajax({
						url: ajaxUrls[index],
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'simcal_validate_google_api_key',
							nonce: ajaxNonce,
							api_key: rawKey,
						},
					})
						.done(function (res) {
							validating = false;
							if (res && res.success) {
								scConnectFieldWrap.addClass('sc_input--success');
								scConnectMsgWrap.show();
								scmsgSuccess.show();
								animateProgressToApiKeyCompleted();
								$('#sc_connect_add_calendar_btn').show();
								scApiKeyBadgeSetOk();
								if (scValidateBtnEl) {
									scValidateBtn
										.removeClass('sc_is_active sc_btn--red')
										.addClass('sc_is_finished')
										.prop('disabled', true);
								}
								scConnectForm.data('scValidated', true);
								submitTimer = setTimeout(function () {
									if (scValidateBtnEl) scValidateBtn.prop('disabled', false);
									scConnectForm.trigger('submit');
								}, 1000);
							} else if (res && res.data && res.data.reason === 'api_keys_not_supported') {
								scConnectFieldWrap.addClass('sc_input--success');
								scConnectMsgWrap.show();
								scmsgSuccess.show();
								animateProgressToApiKeyCompleted();
								$('#sc_connect_add_calendar_btn').show();
								// Treat this as connected for onboarding UI, but don't show the error text.
								scApiKeyBadgeSetOk();
								if (scValidateBtnEl) {
									scValidateBtn
										.removeClass('sc_is_active sc_btn--red')
										.addClass('sc_is_finished')
										.prop('disabled', true);
								}
								scConnectForm.data('scValidated', true);
								submitTimer = setTimeout(function () {
									if (scValidateBtnEl) scValidateBtn.prop('disabled', false);
									scConnectForm.trigger('submit');
								}, 1000);
							} else if (res && res.data && res.data.reason === 'api_key_invalid') {
								scConnectFieldWrap.addClass('sc_input--error');
								scConnectMsgWrap.show();
								scmsgError.show();
								showInlineFlex(scmsgError);
								scmsgError.find('.sc_icon_warning_label').text('API key is not valid.');
								scApiKeyBadgeSetInvalid();
								if (scValidateBtnEl) {
									scValidateBtn
										.removeClass('sc_is_active sc_is_finished')
										.addClass('sc_btn--red')
										.prop('disabled', false);
								}
								resetTimer = setTimeout(resetVisualState, 10000);
							} else {
								scConnectFieldWrap.addClass('sc_input--error');
								scConnectMsgWrap.show();
								scmsgError.show();
								showInlineFlex(scmsgError);
								var failMsg = '';
								if (res && res.data && res.data.message) {
									failMsg = String(res.data.message);
									scmsgError.find('.sc_icon_warning_label').text(failMsg);
								}
								scApiKeyBadgeSetError(failMsg);
								if (scValidateBtnEl) {
									scValidateBtn
										.removeClass('sc_is_active sc_is_finished')
										.addClass('sc_btn--red')
										.prop('disabled', false);
								}
								resetTimer = setTimeout(resetVisualState, 10000);
							}
						})
						.fail(function (xhr) {
							var json = simcalNormalizeAjaxJson(xhr);
							var isNonJsonResponse = simcalIsNonJsonResponse(xhr);
							if (isNonJsonResponse && index + 1 < ajaxUrls.length) {
								validateRequestAtIndex(index + 1);
								return;
							}

							validating = false;
							scConnectFieldWrap.addClass('sc_input--error');
							scConnectMsgWrap.show();
							scmsgError.show();
							showInlineFlex(scmsgError);
							var errorText = 'Unable to validate the API key right now. Please try again.';
							if (json && json.data && json.data.message) {
								errorText = String(json.data.message);
							} else if (isNonJsonResponse) {
								errorText =
									'Validation endpoint returned a non-JSON response. Please check WP debug notices and admin-ajax routing.';
							}
							scmsgError.find('.sc_icon_warning_label').text(errorText);
							scApiKeyBadgeSetError(errorText);
							if (scValidateBtnEl) {
								scValidateBtn
									.removeClass('sc_is_active sc_is_finished')
									.addClass('sc_btn--red')
									.prop('disabled', false);
							}
							resetTimer = setTimeout(resetVisualState, 10000);
						});
				}

				validateRequestAtIndex(0);
			});
		}
	});
})(window);
