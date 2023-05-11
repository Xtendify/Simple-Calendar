window.jQuery(function ($) {
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

	const // Calendar Settings Meta Box
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
				const panelWrap = $(this).closest('div.simcal-panels-wrap');
				$('ul.simcal-tabs li', panelWrap).removeClass('active');
				$(this).parent().addClass('active');
				$('div.simcal-panel', panelWrap).hide();
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
			const selected = $(this).find('option:selected'),
				feed = selected.val(),
				ul = $('.simcal-tabs'),
				tabs = ul.find('> .simcal-feed-type'),
				tab = ul.find('> .simcal-feed-type-' + feed),
				a = ul.find('> li:first-child > a');

			tabs.each(function () {
				$(this).hide();
			});
			tab.show();
			a.trigger('click');
		})
		.trigger('change');

	// Brings back the meta box after all the manipulations above.
	calendarSettings.show();

	// Toggle default calendar settings.
	const defCalViews = $('#_calendar_view_default-calendar'),
		defCalSettings = $('#default-calendar-settings'),
		gridSettings = defCalSettings.find('.simcal-default-calendar-grid'),
		listSettings = defCalSettings.find('.simcal-default-calendar-list'),
		groupedListSettings = defCalSettings.find('.simcal-default-calendar-list-grouped');

	defCalViews
		.on('change', function () {
			const selView = $(this).val();

			if ('grid' === selView) {
				listSettings.hide();
				groupedListSettings.hide();
				gridSettings.show();
			} else if ('list' === selView) {
				gridSettings.hide();
				groupedListSettings.hide();
				listSettings.show();
			} else if ('list-grouped' === selView) {
				gridSettings.hide();
				listSettings.hide();
				groupedListSettings.show();
			}
		})
		.trigger('change');

	const calendarType = $('#_calendar_type');

	calendarType
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
		const field = $(i),
			noResults = field.data('noresults'),
			allowClear = field.data('allowclear');

		field.select2({
			allowClear: allowClear !== 'undefined' ? allowClear : false,
			placeholder: {
				id: '',
				placeholder: '',
			},
			dir:
				// eslint-disable-next-line camelcase, no-undef
				simcal_admin.text_dir !== 'undefined'
					? // eslint-disable-next-line camelcase, no-undef
					  simcal_admin.text_dir
					: 'ltr',
			tokenSeparators: [','],
			width: '100%',
			language: {
				// eslint-disable-next-line object-shorthand
				noResults: function () {
					return noResults !== 'undefined' ? noResults : '';
				},
			},
		});
	});

	// jQuery Date Picker.
	const fieldDatePicker = $('.simcal-field-date-picker');
	fieldDatePicker.each(function (e, i) {
		const input = $(i).find('input'),
			args = {
				autoSize: true,
				changeMonth: true,
				changeYear: true,
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				prevText: '<i class="simcal-icon-left"></i>',
				nextText: '<i class="simcal-icon-right"></i>',
				yearRange: '1900:2050',
				// eslint-disable-next-line object-shorthand
				beforeShow: function () {
					$('#ui-datepicker-div').addClass('simcal-date-picker');
				},
			};

		$(input).datepicker(args);
		$(input).datepicker(
			'option',
			// eslint-disable-next-line camelcase, no-undef
			$.datepicker.regional[simcal_admin.locale]
		);
	});

	// Datetime formatter field.
	const fieldDateTime = $('.simcal-field-datetime-format');
	fieldDateTime.sortable({
		items: '> div',
		// eslint-disable-next-line object-shorthand
		stop: function () {
			formatDateTime($(this));
		},
	});
	fieldDateTime.each(function (e, i) {
		const select = $(i).find('> div select');

		// eslint-disable-next-line no-shadow
		select.each(function (_, i) {
			$(i).on('change', function () {
				formatDateTime($(this).closest('div.simcal-field-datetime-format'));
			});
		});

		formatDateTime(i);
	});
	// Helper function for datetime formatter field.
	function formatDateTime(field) {
		const input = $(field).find('input'),
			select = $(field).find('> div select'),
			code = $(field).find('code');
		let preview = '';
		let format = '';

		select.each(function (i, e) {
			const value = $(e).val(),
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
		const input = $(i).find('input'),
			preview = $(i).find('code');

		$(input).on('keyup', function () {
			const data = {
				action: 'simcal_date_i18n_input_preview',
				value: input.val(),
			};
			// eslint-disable-next-line camelcase, no-undef
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
		const field = $(i),
			min = field.attr('min'),
			max = field.attr('max');

		field.on('change', function () {
			const value = parseInt($(this).val());

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
			const options = $(this).find('option');

			options.each(function (e, option) {
				const show = $(option).data('show-field'),
					showMany = $(option).data('show-fields'),
					hide = $(option).data('hide-field'),
					hideMany = $(option).data('hide-fields');

				const fieldShow = show ? $('#' + show) : '',
					fieldHide = hide ? $('#' + hide) : '';

				if ($(option).is(':selected')) {
					if (fieldShow) {
						fieldShow.show();
					}
					if (fieldHide) {
						fieldHide.hide();
					}
					if (showMany) {
						const s = hideMany.split(',');
						$(s).each(function (_, field) {
							$('#' + field).hide();
						});
					}
					if (hideMany) {
						const h = hideMany.split(',');
						$(h).each(function (_, field) {
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
			const options = $(this).find('option');

			options.each(function (e, option) {
				const id = $(option).data('show-field'),
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
			let value, trigger, el, next;

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

				if (value === trigger) {
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

		const spinner = $(this).find('i');

		$.ajax({
			// eslint-disable-next-line camelcase, no-undef
			url: simcal_admin.ajax_url,
			method: 'POST',
			data: {
				action: 'simcal_clear_cache',
				id: $(this).data('id'),
				nonce: simcal_admin.nonce,
			},
			// eslint-disable-next-line object-shorthand
			beforeSend: function () {
				spinner.fadeToggle();
			},
			// eslint-disable-next-line object-shorthand
			success: function () {
				spinner.fadeToggle();
			},
			error(response) {
				// eslint-disable-next-line no-console
				console.log(response);
			},
		});
	});

	// Newsletter signup
	$('#simcal-drip-signup').on('click', function (e) {
		e.preventDefault();

		const nlMetaBox = $('#simcal-drip'),
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
	$('#_feed_type').on('change', function () {
		if ($(this).val() === 'grouped-calendars') {
			$('#use_calendar').remove();
		} else if (!$('#use_calendar').length) {
			// Don't append "event source default" back if already exists.
			// TODO i18n
			const html =
				// eslint-disable-next-line max-len
				'<option id="use_calendar" value="use_calendar" data-show-field="_use_calendar_warning">Event source default</option>';
			$('#_feed_timezone_setting').append(html);
		}
	});

	/* ========================= *
	 * Add-on License Management *
	 * ========================= */

	$('.simcal-addon-manage-license').on('click', function (e) {
		e.preventDefault();

		let manageLicenseAction = '';

		if ($(this).hasClass('activate')) {
			manageLicenseAction = 'activate_license';
		} else if ($(this).hasClass('deactivate')) {
			manageLicenseAction = 'deactivate_license';
		} else {
			return;
		}
		const button = $(this),
			buttons = button.closest('.simcal-addon-manage-license-buttons'),
			field = button.closest('.simcal-addon-manage-license-field').find('> input'),
			error = buttons.find('.error'),
			spinner = button.find('i');

		$.ajax({
			// eslint-disable-next-line camelcase, no-undef
			url: simcal_admin.ajax_url,
			method: 'POST',
			data: {
				action: 'simcal_manage_add_on_license',
				add_on: $(this).data('add-on'),
				license_key: field.val(),
				license_action: manageLicenseAction,
				nonce: $('#simcal_license_manager').val(),
			},
			// eslint-disable-next-line object-shorthand
			beforeSend: function () {
				spinner.fadeToggle();
			},
			success(response) {
				spinner.fadeToggle();
				if ('activate_license' === manageLicenseAction) {
					if ('valid' === response.data) {
						button.hide();
						field.attr('disabled', 'disabled');
						$(buttons).find('.label').show();
						$(buttons).find('.deactivate').show();
						error.hide();
					} else {
						error.show().text(response.data);
					}
				} else if ('deactivated' === response.data) {
					button.hide();
					field.removeAttr('disabled');
					$(buttons).find('.label').hide();
					$(buttons).find('.activate').show();
					error.hide();
				} else {
					error.show().text(response.data);
				}
			},
			error(response) {
				// eslint-disable-next-line no-console
				console.log(response);
				spinner.fadeToggle();
			},
		});
	});

	$('#simcal-reset-licenses').on('click', function (e) {
		e.preventDefault();

		const dialog = $(this).data('dialog'),
			// eslint-disable-next-line no-alert
			reply = confirm(dialog);

		if (true !== reply) {
			return;
		}

		const spinner = $(this).find('i');

		$.ajax({
			// eslint-disable-next-line camelcase, no-undef
			url: simcal_admin.ajax_url,
			method: 'POST',
			data: {
				action: 'simcal_reset_add_ons_licenses',
				nonce: $('#simcal_license_manager').val(),
			},
			// eslint-disable-next-line object-shorthand
			beforeSend: function () {
				spinner.toggle();
			},
			success(response) {
				if ('success' === response.data) {
					location.reload();
				} else {
					// eslint-disable-next-line no-console
					console.log(response);
				}
			},
			error(response) {
				// eslint-disable-next-line no-console
				console.log(response);
			},
		});
	});
});
