import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';

dayjs.extend(utc);
dayjs.extend(timezone);

jQuery(function ($) {
	/**
	 * Initialize navigation / AJAX for a single default calendar.
	 *
	 * @param {Element|jQuery} calendarEl Calendar root element.
	 */
	function initDefaultCalendarNav(calendarEl) {
		var calendar = $(calendarEl);

		if (calendar.data('simcal-nav-initialized')) {
			return;
		}
		calendar.data('simcal-nav-initialized', true);

		var id = calendar.data('calendar-id'),
			offset = calendar.data('offset'),
			start = calendar.data('events-first'),
			end = calendar.data('calendar-end'),
			nav = calendar.find('.simcal-calendar-head'),
			buttons = nav.find('.simcal-nav-button'),
			spinner = calendar.find('.simcal-ajax-loader'),
			current = nav.find('.simcal-current'),
			currentTime = current.data('calendar-current'),
			currentMonth = current.find('span.simcal-current-month'),
			currentYear = current.find('span.simcal-current-year'),
			currentDate = dayjs(currentTime * 1000).tz(calendar.data('timezone')),
			date,
			action;

		if (calendar.hasClass('simcal-default-calendar-grid')) {
			action = 'simcal_default_calendar_draw_grid';
			// Always use the first of the month in grid.
			date = new Date(currentDate.year(), currentDate.month());
			toggleGridNavButtons(buttons, date.getTime() / 1000, start, end);
		} else {
			action = 'simcal_default_calendar_draw_list';
			toggleListNavButtons(buttons, calendar, start, end, false, currentTime);
			toggleListHeading(calendar);
		}

		// Navigate the calendar.
		buttons.on('click', function () {
			var direction = $(this).hasClass('simcal-next') ? 'next' : 'prev';

			if (action == 'simcal_default_calendar_draw_grid') {
				// Monthly grid calendars.

				var body = calendar.find('.simcal-month'),
					newDate,
					month,
					year;

				if ('prev' == direction) {
					// Beginning of the previous month.
					newDate = new Date(date.setMonth(date.getMonth() - 1, 1));
				} else {
					// Last day of next month.
					newDate = new Date(date.setMonth(date.getMonth() + 2, 1));
					newDate.setDate(0);
					newDate.setHours(23);
					newDate.setMinutes(59);
					newDate.setSeconds(59);
				}

				month = newDate.getMonth();
				year = newDate.getFullYear();

				$.ajax({
					url: simcal_default_calendar.ajax_url,
					type: 'POST',
					dataType: 'json',
					cache: false,
					data: {
						action: action,
						month: month + 1, // month count in PHP goes 1-12 vs 0-11 in JavaScript
						year: year,
						id: id,
					},
					beforeSend: function () {
						spinner.fadeToggle();
						body.attr('aria-busy', 'true');
					},
					success: function (response) {
						currentMonth.text(simcal_default_calendar.months.full[month]);
						currentYear.text(year);
						current.attr('data-calendar-current', newDate.getTime() / 1000 + offset + 1);

						toggleGridNavButtons(buttons, newDate.getTime() / 1000, start, end);

						spinner.fadeToggle();

						date = newDate;

						body.replaceWith(response.data);
						body = calendar.find('.simcal-month');
						body.removeAttr('aria-busy');

						calendarBubbles(calendar);
					},
					error: function (response) {
						body.removeAttr('aria-busy');
						console.log(response);
					},
				});
			} else {
				// List calendars.

				var list = calendar.find('.simcal-events-list-container'),
					prev = list.data('prev'),
					next = list.data('next'),
					timestamp = direction == 'prev' ? prev : next;

				$.ajax({
					url: simcal_default_calendar.ajax_url,
					type: 'POST',
					dataType: 'json',
					cache: false,
					data: {
						action: action,
						ts: timestamp,
						id: id,
					},
					beforeSend: function () {
						spinner.fadeToggle();
						list.attr('aria-busy', 'true');
					},
					success: function (response) {
						list.replaceWith(response.data);
						list = calendar.find('.simcal-events-list-container');
						list.removeAttr('aria-busy');
						current.attr('data-calendar-current', timestamp);

						toggleListHeading(calendar);
						toggleListNavButtons(buttons, calendar, start, end, direction, timestamp);

						spinner.fadeToggle();
					},
					error: function (response) {
						list.removeAttr('aria-busy');
						console.log(response);
					},
				});
			}
		});
	}

	/**
	 * Whether a calendar is a hidden sibling inside a responsive dual-view wrapper.
	 * Uses matchMedia so deferral does not depend on CSS having painted yet.
	 *
	 * @param {jQuery} calendar
	 * @return {boolean}
	 */
	function isDeferredResponsiveSibling(calendar) {
		if (!calendar.closest('.simcal-responsive-views').length) {
			return false;
		}
		var isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
		if (isMobile) {
			return calendar.hasClass('simcal-default-calendar-grid');
		}
		return calendar.hasClass('simcal-default-calendar-list');
	}

	/**
	 * Init any deferred responsive siblings that are now visible.
	 */
	function initDeferredResponsiveViews() {
		$('.simcal-responsive-views .simcal-default-calendar[data-simcal-deferred="1"]').each(function (e, el) {
			var calendar = $(el);
			if (isDeferredResponsiveSibling(calendar)) {
				return;
			}
			calendar.removeAttr('data-simcal-deferred');
			initDefaultCalendarNav(calendar);
			if (calendar.hasClass('simcal-default-calendar-grid')) {
				calendarBubbles(calendar);
				calendar.off('change.simcalBubbles').on('change.simcalBubbles', function () {
					calendarBubbles(this);
				});
			} else {
				toggleListHeading(calendar);
			}
		});
	}

	// Browse calendar pages — skip hidden dual-view siblings until shown (keeps load light).
	$('.simcal-default-calendar').each(function (e, i) {
		var calendar = $(i);

		if (isDeferredResponsiveSibling(calendar)) {
			calendar.attr('data-simcal-deferred', '1');
			return;
		}

		initDefaultCalendarNav(calendar);
	});

	/**
	 * Enable or disable grid calendar navigation buttons.
	 *
	 * @param buttons Previous and Next buttons elements.
	 * @param time    Current time.
	 * @param min     Lower bound timestamp.
	 * @param max     Upper bound timestamp.
	 */
	function toggleGridNavButtons(buttons, time, min, max) {
		buttons.each(function (e, i) {
			var button = $(i),
				month = new Date(time * 1000);

			if (button.hasClass('simcal-prev')) {
				month = new Date(month.setMonth(month.getMonth(), 1));
				month.setDate(0);

				if (month.getTime() / 1000 <= min) {
					button.attr('disabled', 'disabled');
				} else {
					button.removeAttr('disabled');
				}
			} else {
				month = new Date(month.setMonth(month.getMonth() + 1, 1));
				month.setDate(0);
				month.setHours(23);
				month.setMinutes(59);
				month.setSeconds(59);

				if (month.getTime() / 1000 >= max) {
					button.attr('disabled', 'disabled');
				} else {
					button.removeAttr('disabled');
				}
			}
		});
	}

	/**
	 * Enable or disable grid calendar navigation buttons.
	 *
	 * @param buttons   Previous and Next button elements.
	 * @param calendar  Current calendar.
	 * @param start     Lower bound timestamp.
	 * @param end       Upper bound timestamp.
	 * @param direction Direction intent.
	 */
	function toggleListNavButtons(buttons, calendar, start, end, direction, currentTime) {
		var list = calendar.find('.simcal-events-list-container'),
			prev = list.data('prev'),
			next = list.data('next'),
			last_event = list.find('li.simcal-event:last').data('start');

		buttons.each(function (e, b) {
			var button = $(b);

			if (direction) {
				if (button.hasClass('simcal-prev')) {
					if (direction == 'prev') {
						if (prev <= start && currentTime <= start) {
							button.attr('disabled', 'disabled');
						}
					} else {
						button.removeAttr('disabled');
					}
				} else if (button.hasClass('simcal-next')) {
					if (direction == 'next') {
						if ((next >= end && currentTime >= end) || last_event >= end) {
							button.attr('disabled', 'disabled');
						}
					} else {
						button.removeAttr('disabled');
					}
				}
			} else {
				if (button.hasClass('simcal-prev')) {
					if (prev <= start && currentTime <= start) {
						button.attr('disabled', 'disabled');
					}
				} else if (button.hasClass('simcal-next')) {
					if ((next >= end && currentTime >= end) || last_event >= end) {
						button.attr('disabled', 'disabled');
					}
				}
			}
		});
	}

	/**
	 * Replace the list heading with current page.
	 *
	 * @param calendar Current calendar.
	 */
	function toggleListHeading(calendar) {
		var current = $(calendar).find('.simcal-current'),
			heading = $(calendar).find('.simcal-events-list-container'),
			small = heading.data('heading-small'),
			large = heading.data('heading-large'),
			newHeading = $('<h3 />');

		if (calendar.width() < 400) {
			newHeading.text(small);
		} else {
			newHeading.text(large);
		}

		current.html(newHeading);
	}

	var gridCalendars = $('.simcal-default-calendar-grid');

	/**
	 * Default calendar grid event bubbles.
	 *
	 * Initializes tooltips for events in grid.
	 * Adjusts UI for mobile or desktop.
	 *
	 * @param calendar The calendar element.
	 */
	function calendarBubbles(calendar) {
		var table = $(calendar).find('> table'),
			thead = table.find('thead'),
			weekDayNames = thead.find('th.simcal-week-day'),
			cells = table.find('td.simcal-day > div'),
			eventsList = table.find('ul.simcal-events'),
			eventTitles = eventsList.find('> li > .simcal-event-title'),
			eventsToggle = table.find('.simcal-events-toggle'),
			eventsDots = table.find('span.simcal-events-dots'),
			events = table.find('.simcal-tooltip-content'),
			hiddenEvents = table.find('.simcal-event-toggled'),
			bubbleTrigger = table.data('event-bubble-trigger'),
			width = cells.first().width();

		if (width < 60) {
			weekDayNames.each(function (e, w) {
				$(w).text($(w).data('screen-small'));
			});

			// Hide list of events titles and show dots.
			eventsList.hide();
			eventTitles.hide();
			if (eventsToggle != 'undefined') {
				eventsToggle.hide();
				if (hiddenEvents != 'undefined') {
					hiddenEvents.show();
				}
			}
			eventsDots.show();

			// Force click/tap on mobile.
			bubbleTrigger = 'click';
			// Adapts cells to be more squareish on mobile.
			var minH = width - 10 + 'px';
			cells.css('min-height', minH);
			table.find('span.simcal-events-dots:not(:empty)').css('min-height', minH);
		} else {
			if (width <= 240) {
				weekDayNames.each(function (e, w) {
					$(w).text($(w).data('screen-medium'));
				});
			} else {
				weekDayNames.each(function (e, w) {
					$(w).text($(w).data('screen-large'));
				});
			}

			// Hide dots and show list of events titles and toggle.
			eventsList.show();
			eventTitles.show();
			if (eventsToggle != 'undefined') {
				eventsToggle.show();
				if (hiddenEvents != 'undefined') {
					hiddenEvents.hide();
				}
			}
			eventsDots.hide();

			// Cells default min-height value.
			cells.css('min-height', width + 'px');
		}

		// Create bubbles for each cell.
		cells.each(function (e, cell) {
			var cellDots = $(cell).find('span.simcal-events-dots'),
				tooltips = $(cell).find('.simcal-tooltip'),
				eventBubbles,
				content,
				last;

			// Mobile mode.
			if (width < 60) {
				events.show();
				// Use a single bubble from dots as a whole.
				eventBubbles = cellDots;
			} else {
				events.hide();
				// Create a bubble for each event in list.
				eventBubbles = tooltips;
			}

			eventBubbles.each(function (e, i) {
				$(i).qtip({
					content: {
						text: function () {
							const isMobile = width < 60;
							const content = isMobile
								? $(cell).find('ul.simcal-events').clone(true, true).css({ display: 'block' })[0]
								: $(i).find('> .simcal-tooltip-content').clone(true, true).css({ display: 'block' })[0];
							return content || 'No event info available';
						},
					},
					position: {
						my: 'top center',
						at: 'bottom center',
						target: $(i),
						viewport: width < 60 ? $(window) : true,
						adjust: {
							method: 'shift',
							scroll: false,
						},
					},
					style: {
						def: false,
						classes: 'simcal-default-calendar simcal-event-bubble',
					},
					show: {
						solo: true,
						effect: false,
						event: bubbleTrigger == 'hover' ? 'mouseenter' : 'click',
					},
					hide: {
						fixed: true,
						effect: false,
						event: bubbleTrigger == 'click' ? 'unfocus' : 'mouseleave',
						delay: 100,
					},
					events: {
						render: function (event, api) {
							setTimeout(() => {
								api.reposition();
							}, 10); // ensures top/left are recalculated after DOM draw
						},
						show: function (event, current) {
							if (last && last.id && last.id !== current.id) {
								last.hide();
							}
							last = current;
						},
					},
					overwrite: false,
				});
			});
		});
	}

	// Event bubbles and calendar UI triggers (skip deferred/hidden dual-view grids).
	gridCalendars.each(function (e, calendar) {
		var $calendar = $(calendar);
		if ($calendar.attr('data-simcal-deferred') === '1' || isDeferredResponsiveSibling($calendar)) {
			return;
		}
		calendarBubbles(calendar);
		$calendar.on('change', function () {
			calendarBubbles(this);
		});
	});

	// Viewport changes: debounce, only touch visible grids, and lazy-init deferred siblings.
	var resizeTimer = null;
	window.onresize = function () {
		if (resizeTimer) {
			clearTimeout(resizeTimer);
		}
		resizeTimer = setTimeout(function () {
			initDeferredResponsiveViews();
			gridCalendars.each(function (e, calendar) {
				if ($(calendar).is(':visible')) {
					calendarBubbles(calendar);
				}
			});
		}, 150);
	};

	if (window.matchMedia) {
		var responsiveViewsMq = window.matchMedia('(max-width: 768px)');
		var onResponsiveViewsChange = function () {
			initDeferredResponsiveViews();
			gridCalendars.each(function (e, calendar) {
				if ($(calendar).is(':visible')) {
					calendarBubbles(calendar);
				}
			});
		};
		if (responsiveViewsMq.addEventListener) {
			responsiveViewsMq.addEventListener('change', onResponsiveViewsChange);
		} else if (responsiveViewsMq.addListener) {
			responsiveViewsMq.addListener(onResponsiveViewsChange);
		}
	}
	/*
	 * Calendar action buttons (Export, URL, Print).
	 */
	$(document).on('click', '.simcal-print-calendar-button', function () {
		var $calendar = $(this).closest('.simcal-calendar');
		if (!$calendar.length) {
			return;
		}

		var $divToPrint = $calendar.clone(false);
		var $toHide = $('body').children().not('script').filter(':visible');
		$toHide.hide();
		$('body').append($divToPrint);
		$('body').addClass('simcal-print-calendar');

		window.print();

		$divToPrint.remove();
		$toHide.show();
		$('body').removeClass('simcal-print-calendar');
	});

	$(document).on('click', '.simcal-ics-url-button', function (e) {
		e.preventDefault();

		var $button = $(this);
		var url = $button.data('url');
		var $label = $button.find('.simcal-calendar-action-label');
		var originalLabel = $label.text();

		if (!url) {
			return;
		}

		function showCopied() {
			$label.text('Copied!');
			setTimeout(function () {
				$label.text(originalLabel);
			}, 2000);
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard
				.writeText(url)
				.then(showCopied)
				.catch(function () {
					copyWithTextarea(url);
					showCopied();
				});
		} else {
			copyWithTextarea(url);
			showCopied();
		}

		function copyWithTextarea(text) {
			var $temp = $('<textarea readonly>')
				.val(text)
				.css({ position: 'fixed', top: '-9999px', left: '-9999px', opacity: 0 })
				.appendTo('body');
			$temp[0].focus();
			$temp[0].select();
			document.execCommand('copy');
			$temp.remove();
		}
	});

	/**
	 * Toggle to expand events. Delegated so AJAX-replaced and deferred
	 * sibling buttons work without re-binding.
	 */
	$(document).on('click.simcalEventsToggle', '.simcal-events-toggle', function () {
		var button = $(this);
		button.find('i').toggleClass('simcal-icon-rotate-180');
		button.prev('.simcal-events').find('.simcal-event-toggled').slideToggle();
	});
});
