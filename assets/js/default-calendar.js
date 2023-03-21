// eslint-disable-next-line import/no-extraneous-dependencies
import dayjs from 'dayjs';
// eslint-disable-next-line import/no-extraneous-dependencies
import utc from 'dayjs/plugin/utc';
// eslint-disable-next-line import/no-extraneous-dependencies
import timezone from 'dayjs/plugin/timezone';

(function (window) {
	'use strict';

	dayjs.extend(utc);
	dayjs.extend(timezone);

	window.jQuery(function ($) {
		// Browse calendar pages.
		$('.simcal-default-calendar').each(function (e, i) {
			const calendar = $(i),
				id = calendar.data('calendar-id'),
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
				currentDate = dayjs(currentTime * 1000).tz(calendar.data('timezone'));
			let date, action;

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
				const direction = $(this).hasClass('simcal-next') ? 'next' : 'prev';

				if (action === 'simcal_default_calendar_draw_grid') {
					// Monthly grid calendars.

					const body = calendar.find('.simcal-month');

					let newDate;

					if ('prev' === direction) {
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

					const month = newDate.getMonth();
					const year = newDate.getFullYear();

					$.ajax({
						// eslint-disable-next-line camelcase, no-undef
						url: simcal_default_calendar.ajax_url,
						type: 'POST',
						dataType: 'json',
						cache: false,
						data: {
							action,
							month: month + 1, // month count in PHP goes 1-12 vs 0-11 in JavaScript
							year,
							id,
						},
						beforeSend() {
							spinner.fadeToggle();
						},
						success(response) {
							currentMonth.text(
								// eslint-disable-next-line camelcase, no-undef
								simcal_default_calendar.months.full[month]
							);
							currentYear.text(year);
							current.attr('data-calendar-current', newDate.getTime() / 1000 + offset + 1);

							toggleGridNavButtons(buttons, newDate.getTime() / 1000, start, end);

							spinner.fadeToggle();

							date = newDate;

							body.replaceWith(response.data);

							calendarBubbles(calendar);
							expandEventsToggle();
						},
						error(response) {
							// eslint-disable-next-line no-console
							console.log(response);
						},
					});
				} else {
					// List calendars.

					const list = calendar.find('.simcal-events-list-container'),
						prev = list.data('prev'),
						next = list.data('next'),
						timestamp = direction === 'prev' ? prev : next;

					$.ajax({
						// eslint-disable-next-line camelcase, no-undef
						url: simcal_default_calendar.ajax_url,
						type: 'POST',
						dataType: 'json',
						cache: false,
						data: {
							action,
							ts: timestamp,
							id,
						},
						beforeSend() {
							spinner.fadeToggle();
						},
						success(response) {
							list.replaceWith(response.data);
							current.attr('data-calendar-current', timestamp);

							toggleListHeading(calendar);
							toggleListNavButtons(buttons, calendar, start, end, direction, timestamp);

							spinner.fadeToggle();
							expandEventsToggle();
						},
						error(response) {
							// eslint-disable-next-line no-console
							console.log(response);
						},
					});
				}
			});
		});

		/**
		 * Enable or disable grid calendar navigation buttons.
		 *
		 * @param {HTMLElement[]} buttons Previous and Next buttons elements.
		 * @param {number}        time    Current time.
		 * @param {number}        min     Lower bound timestamp.
		 * @param {number}        max     Upper bound timestamp.
		 */
		function toggleGridNavButtons(buttons, time, min, max) {
			buttons.each(function (e, i) {
				const button = $(i);
				let month = new Date(time * 1000);

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
		 * @param {HTMLElement[]} buttons     Previous and Next button elements.
		 * @param {HTMLElement}   calendar    Current calendar.
		 * @param {number}        start       Lower bound timestamp.
		 * @param {number}        end         Upper bound timestamp.
		 * @param {string}        direction   Direction intent.
		 * @param {number}        currentTime
		 */
		function toggleListNavButtons(buttons, calendar, start, end, direction, currentTime) {
			const list = calendar.find('.simcal-events-list-container'),
				prev = list.data('prev'),
				next = list.data('next'),
				// eslint-disable-next-line camelcase
				last_event = list.find('li.simcal-event:last').data('start');

			buttons.each(function (e, b) {
				const button = $(b);

				if (direction) {
					if (button.hasClass('simcal-prev')) {
						if (direction === 'prev') {
							if (prev <= start && currentTime <= start) {
								button.attr('disabled', 'disabled');
							}
						} else {
							button.removeAttr('disabled');
						}
					} else if (button.hasClass('simcal-next')) {
						if (direction === 'next') {
							if (
								(next >= end && currentTime >= end) ||
								// eslint-disable-next-line camelcase
								last_event >= end
							) {
								button.attr('disabled', 'disabled');
							}
						} else {
							button.removeAttr('disabled');
						}
					}
				} else if (button.hasClass('simcal-prev')) {
					if (prev <= start && currentTime <= start) {
						button.attr('disabled', 'disabled');
					}
				} else if (button.hasClass('simcal-next')) {
					if (
						(next >= end && currentTime >= end) ||
						// eslint-disable-next-line camelcase
						last_event >= end
					) {
						button.attr('disabled', 'disabled');
					}
				}
			});
		}

		/**
		 * Replace the list heading with current page.
		 *
		 * @param {HTMLElement} calendar Current calendar.
		 */
		function toggleListHeading(calendar) {
			const current = $(calendar).find('.simcal-current'),
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

		const gridCalendars = $('.simcal-default-calendar-grid');

		/**
		 * Default calendar grid event bubbles.
		 *
		 * Initializes tooltips for events in grid.
		 * Adjusts UI for mobile or desktop.
		 *
		 * @param {HTMLElement} calendar The calendar element.
		 */
		function calendarBubbles(calendar) {
			const table = $(calendar).find('> table'),
				thead = table.find('thead'),
				weekDayNames = thead.find('th.simcal-week-day'),
				cells = table.find('td.simcal-day > div'),
				eventsList = table.find('ul.simcal-events'),
				eventTitles = eventsList.find('> li > .simcal-event-title'),
				eventsToggle = table.find('.simcal-events-toggle'),
				eventsDots = table.find('span.simcal-events-dots'),
				events = table.find('.simcal-tooltip-content'),
				hiddenEvents = table.find('.simcal-event-toggled'),
				width = cells.first().width();

			let bubbleTrigger = table.data('event-bubble-trigger');

			if (width < 60) {
				weekDayNames.each(function (e, w) {
					$(w).text($(w).data('screen-small'));
				});

				// Hide list of events titles and show dots.
				eventsList.hide();
				eventTitles.hide();
				if (eventsToggle !== 'undefined') {
					eventsToggle.hide();
					if (hiddenEvents !== 'undefined') {
						hiddenEvents.show();
					}
				}
				eventsDots.show();

				// Force click/tap on mobile.
				bubbleTrigger = 'click';
				// Adapts cells to be more squareish on mobile.
				const minH = width - 10 + 'px';
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
				if (eventsToggle !== 'undefined') {
					eventsToggle.show();
					if (hiddenEvents !== 'undefined') {
						hiddenEvents.hide();
					}
				}
				eventsDots.hide();

				// Cells default min-height value.
				cells.css('min-height', width + 'px');
			}

			// Create bubbles for each cell.
			cells.each(function (_, cell) {
				const cellDots = $(cell).find('span.simcal-events-dots'),
					tooltips = $(cell).find('.simcal-tooltip');
				let eventBubbles, last;

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

				eventBubbles.each(function (__, i) {
					$(i).qtip({
						content: width < 60 ? $(cell).find('ul.simcal-events') : $(i).find('> .simcal-tooltip-content'),
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
							event: bubbleTrigger === 'hover' ? 'mouseenter' : 'click',
						},
						hide: {
							fixed: true,
							effect: false,
							event: bubbleTrigger === 'click' ? 'unfocus' : 'mouseleave',
							delay: 100,
						},
						events: {
							show(event, current) {
								// Hide when another tooltip opens:
								if (last && last.id) {
									if (last.id !== current.id) {
										last.hide();
									}
								}
								last = current;
							},
						},
						overwrite: false,
					});
				});
			});
		}

		// Event bubbles and calendar UI triggers.
		gridCalendars.each(function (e, calendar) {
			calendarBubbles(calendar);
			$(calendar).on('change', function () {
				calendarBubbles(this);
			});
		});
		// Viewport changes might require triggering calendar mobile mode.
		window.onresize = function () {
			gridCalendars.each(function (e, calendar) {
				calendarBubbles(calendar);
			});
		};

		/**
		 * Toggle to expand events.
		 */
		function expandEventsToggle() {
			$('.simcal-events-toggle').each(function (e, button) {
				const list = $(button).prev('.simcal-events'),
					toggled = list.find('.simcal-event-toggled'),
					arrow = $(button).find('i');

				$(button).on('click', function () {
					arrow.toggleClass('simcal-icon-rotate-180');
					toggled.slideToggle();
				});
			});
		}
		expandEventsToggle();
	});
})(this);
