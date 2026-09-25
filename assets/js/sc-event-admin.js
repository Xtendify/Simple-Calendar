(function (window, undefined) {
	'use strict';

	jQuery(function ($) {
		/* =========================
		 * SC Event details validation
		 * ========================= */
		(function initScEventDetailsValidation() {
			if (!$('body').hasClass('post-type-sc-event')) {
				return;
			}

			var $start = $('#_sc_event_start');
			var $end = $('#_sc_event_end');
			var $errorBox = $('#simcal-sc-event-details-errors');

			if (!$start.length || !$end.length) {
				return;
			}

			var strings =
				window.simcal_admin && window.simcal_admin.sc_event
					? window.simcal_admin.sc_event
					: {
							start_required: 'Start date/time is required.',
							end_required: 'End date/time is required.',
							end_after_start: 'End date/time must be greater than the start date/time.',
						};

			function clearFieldErrors() {
				$start.removeClass('simcal-sc-event-field-error');
				$end.removeClass('simcal-sc-event-field-error');
				$errorBox.hide().find('p').text('');
			}

			function showErrors(messages, fields) {
				$errorBox
					.show()
					.find('p')
					.html(
						messages
							.map(function (msg) {
								return $('<div/>').text(msg).html();
							})
							.join('<br />')
					);

				(fields || []).forEach(function ($field) {
					$field.addClass('simcal-sc-event-field-error');
				});

				var metabox = document.getElementById('simcal-sc-event-details');
				if (metabox && typeof metabox.scrollIntoView === 'function') {
					metabox.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
			}

			function validateScEventDetails() {
				clearFieldErrors();

				var startVal = $.trim($start.val() || '');
				var endVal = $.trim($end.val() || '');
				var messages = [];
				var fields = [];

				if (!startVal) {
					messages.push(strings.start_required);
					fields.push($start);
				}

				if (!endVal) {
					messages.push(strings.end_required);
					fields.push($end);
				}

				if (startVal && endVal) {
					var startTime = Date.parse(startVal);
					var endTime = Date.parse(endVal);

					if (!isNaN(startTime) && !isNaN(endTime) && endTime <= startTime) {
						messages.push(strings.end_after_start);
						fields.push($end);
					}
				}

				if (messages.length) {
					showErrors(messages, fields);
					return false;
				}

				return true;
			}

			$start.add($end).on('change input', function () {
				$(this).removeClass('simcal-sc-event-field-error');
				if (!$('.simcal-sc-event-field-error').length) {
					$errorBox.hide().find('p').text('');
				}
			});

			$('#post').on('submit.simcalScEvent', function (e) {
				if (!validateScEventDetails()) {
					e.preventDefault();
					e.stopImmediatePropagation();
					return false;
				}
			});

			$('#publish, #save-post').on('click.simcalScEvent', function (e) {
				if (!validateScEventDetails()) {
					e.preventDefault();
					e.stopImmediatePropagation();
					return false;
				}
			});
		})();

		/* =========================
		 * SC Event location autocomplete (Photon)
		 * ========================= */
		(function initScEventLocationAutocomplete() {
			if (!$('body').hasClass('post-type-sc-event')) {
				return;
			}

			var $input = $('#_sc_event_location');
			var $lat = $('#_sc_event_lat');
			var $lng = $('#_sc_event_lng');
			var $list = $('#simcal-sc-event-location-suggestions');
			var $loader = $('#simcal-sc-event-location-loader');

			if (!$input.length || !$list.length) {
				return;
			}

			var strings =
				window.simcal_admin && window.simcal_admin.sc_event
					? window.simcal_admin.sc_event
					: {
							location_no_results: 'No matching locations found.',
							location_search_error: 'Could not look up locations. Please try again.',
						};

			var debounceTimer = null;
			var requestSeq = 0;
			var activeIndex = -1;
			var results = [];
			var suppressClear = false;

			function showLoader() {
				if ($loader.length) {
					$loader.removeAttr('hidden').attr('aria-hidden', 'false');
				}
				$input.attr('aria-busy', 'true');
			}

			function hideLoader() {
				if ($loader.length) {
					$loader.attr('hidden', true).attr('aria-hidden', 'true');
				}
				$input.attr('aria-busy', 'false');
			}

			function clearCoordinates() {
				$lat.val('');
				$lng.val('');
			}

			function hideSuggestions() {
				hideLoader();
				$list.empty().attr('hidden', true);
				$input.attr('aria-expanded', 'false');
				activeIndex = -1;
			}

			function setActive(index) {
				var $items = $list.children('li');
				$items.removeClass('is-active').attr('aria-selected', 'false');

				if (index < 0 || index >= $items.length) {
					activeIndex = -1;
					$input.removeAttr('aria-activedescendant');
					return;
				}

				activeIndex = index;
				$items.eq(activeIndex).addClass('is-active').attr('aria-selected', 'true');
				$input.attr('aria-activedescendant', $items.eq(activeIndex).attr('id'));
			}

			function selectResult(item) {
				if (!item) {
					return;
				}

				suppressClear = true;
				$input.val(item.label);
				$lat.val(item.lat);
				$lng.val(item.lng);
				hideSuggestions();
				suppressClear = false;
			}

			function renderResults(items) {
				hideLoader();
				results = items || [];
				$list.empty();

				if (!results.length) {
					$list.append(
						$('<li/>')
							.addClass('simcal-sc-event-location-empty')
							.attr('role', 'presentation')
							.text(strings.location_no_results)
					);
					$list.removeAttr('hidden');
					$input.attr('aria-expanded', 'true');
					activeIndex = -1;
					return;
				}

				results.forEach(function (item, index) {
					var $li = $('<li/>')
						.attr({
							role: 'option',
							id: 'simcal-sc-event-location-option-' + index,
							'aria-selected': 'false',
						})
						.text(item.label)
						.data('index', index);

					$list.append($li);
				});

				$list.removeAttr('hidden');
				$input.attr('aria-expanded', 'true');
				setActive(0);
			}

			function hideSuggestionsListOnly() {
				$list.empty().attr('hidden', true);
				$input.attr('aria-expanded', 'false');
				activeIndex = -1;
			}

			function fetchSuggestions(query) {
				var seq = ++requestSeq;

				showLoader();
				hideSuggestionsListOnly();

				$.ajax({
					url: (window.simcal_admin && window.simcal_admin.ajax_url) || ajaxurl,
					method: 'GET',
					dataType: 'json',
					data: {
						action: 'simcal_geocode_suggest',
						nonce: (window.simcal_admin && window.simcal_admin.nonce) || '',
						q: query,
					},
				})
					.done(function (response) {
						if (seq !== requestSeq) {
							return;
						}

						if (!response || !response.success || !response.data || !$.isArray(response.data.results)) {
							renderResults([]);
							return;
						}

						renderResults(response.data.results);
					})
					.fail(function () {
						if (seq !== requestSeq) {
							return;
						}

						hideLoader();
						$list
							.empty()
							.append(
								$('<li/>')
									.addClass('simcal-sc-event-location-empty')
									.attr('role', 'presentation')
									.text(strings.location_search_error)
							)
							.removeAttr('hidden');
						$input.attr('aria-expanded', 'true');
						activeIndex = -1;
					});
			}

			$input.on('input', function () {
				if (suppressClear) {
					return;
				}

				clearCoordinates();

				var query = $.trim($input.val() || '');

				window.clearTimeout(debounceTimer);

				if (query.length < 2) {
					hideSuggestions();
					return;
				}

				showLoader();

				debounceTimer = window.setTimeout(function () {
					fetchSuggestions(query);
				}, 300);
			});

			$input.on('keydown', function (e) {
				if ($list.is('[hidden]')) {
					return;
				}

				if (e.key === 'ArrowDown') {
					e.preventDefault();
					setActive(Math.min(activeIndex + 1, results.length - 1));
				} else if (e.key === 'ArrowUp') {
					e.preventDefault();
					setActive(Math.max(activeIndex - 1, 0));
				} else if (e.key === 'Enter') {
					if (activeIndex >= 0 && results[activeIndex]) {
						e.preventDefault();
						selectResult(results[activeIndex]);
					}
				} else if (e.key === 'Escape') {
					hideSuggestions();
				}
			});

			$list.on('mousedown', 'li[role="option"]', function (e) {
				e.preventDefault();
				var index = $(this).data('index');
				selectResult(results[index]);
			});

			$input.on('blur', function () {
				window.setTimeout(hideSuggestions, 150);
			});
		})();
	});
})(window);
