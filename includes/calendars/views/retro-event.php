<?php
/**
 * Shared Retro event markup.
 *
 * @package SimpleCalendar/Calendars
 */
namespace SimpleCalendar\Calendars\Views;

use SimpleCalendar\Calendars\Default_Calendar;
use SimpleCalendar\Events\Event;
use SimpleCalendar\plugin_deps\Carbon\Carbon;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Event cards and detail panels for the Retro views.
 *
 * Uses the existing event template tags for text, links, and optional fields.
 */
class Retro_Event
{
	/**
	 * Format a timestamp in the calendar timezone.
	 *
	 * @param string $format
	 * @param int    $timestamp
	 * @param string $timezone
	 * @return string
	 */
	public static function local_date($format, $timestamp, $timezone)
	{
		try {
			$zone = new \DateTimeZone($timezone ? $timezone : 'UTC');
		} catch (\Exception $exception) {
			$zone = new \DateTimeZone('UTC');
		}

		return wp_date($format, (int) $timestamp, $zone);
	}

	/**
	 * Next unique element id.
	 *
	 * @param int $calendar_id
	 * @return string
	 */
	public static function element_id($calendar_id)
	{
		static $sequence = 0;
		$sequence++;

		return 'simcal-retro-' . absint($calendar_id) . '-' . $sequence;
	}

	/**
	 * Border and tinted backgrounds from the event or calendar color.
	 *
	 * Uses the event color when that option is on. Otherwise it uses the
	 * calendar color. The solid color is the border; a light mix of that
	 * color is the background.
	 *
	 * @param Event $event
	 * @return array{border:string,grid:string,list:string}|null
	 */
	public static function colors(Event $event)
	{
		$hex = strtolower(ltrim((string) $event->get_color(''), '#'));
		if ('' === $hex) {
			$calendar_color = '';
			if (!empty($event->meta['calendar_color'])) {
				$calendar_color = (string) $event->meta['calendar_color'];
			}
			if ('' === $calendar_color) {
				$calendar_color = apply_filters('simcal_retro_calendar_color', '', $event);
			}
			$hex = strtolower(ltrim((string) $calendar_color, '#'));
		}
		if (strlen($hex) === 3) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if (!preg_match('/^[0-9a-f]{6}$/', $hex)) {
			return null;
		}

		$channels = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
		$mix = function ($amount) use ($channels) {
			$mixed = [];
			foreach ($channels as $channel) {
				$mixed[] = (int) round($channel + (255 - $channel) * $amount);
			}
			return sprintf('#%02x%02x%02x', $mixed[0], $mixed[1], $mixed[2]);
		};

		return [
			'border' => '#' . $hex,
			'grid' => $mix(0.9),
			'list' => $mix(0.86),
		];
	}

	/**
	 * Safe inline style for a card surface.
	 *
	 * @param Event  $event
	 * @param string $surface grid|list
	 * @return string
	 */
	public static function surface_style(Event $event, $surface)
	{
		$colors = self::colors($event);
		if (null === $colors) {
			return '';
		}

		$background = 'list' === $surface ? $colors['list'] : $colors['grid'];

		return 'border-color:' . $colors['border'] . ';background-color:' . $background . ';';
	}

	/**
	 * Start time, or an all-day label.
	 *
	 * @param Default_Calendar $calendar
	 * @param Event            $event
	 * @return string
	 */
	public static function time_html(Default_Calendar $calendar, Event $event)
	{
		if ($event->whole_day) {
			return '<span class="simcal-retro-time">' . esc_html__('All day', 'google-calendar-events') . '</span>';
		}

		return '<span class="simcal-retro-time">' . $calendar->get_event_html($event, '[start-time]') . '</span>';
	}

	/**
	 * Event title markup.
	 *
	 * @param Default_Calendar $calendar
	 * @param Event            $event
	 * @return string
	 */
	public static function title_html(Default_Calendar $calendar, Event $event)
	{
		$title = $calendar->get_event_html($event, '[title]');
		if ('' === trim(wp_strip_all_tags($title))) {
			$title = '<span class="simcal-event-title">' . esc_html__('Event', 'google-calendar-events') . '</span>';
		}

		return $title;
	}

	/**
	 * Date, time, and location for the [time-location-common] tag.
	 *
	 * Date and time use the calendar's admin date and time formats, the same
	 * formats as [start-date], [start-time], and [end-time].
	 *
	 * @param Default_Calendar $calendar
	 * @param Event            $event
	 * @return string
	 */
	public static function time_location_html(Default_Calendar $calendar, Event $event)
	{
		$schedule = self::schedule_text($calendar, $event);
		$address = self::address($event);
		$location = '';
		if ('' !== $address) {
			$location =
				'<span class="simcal-time-location-common-row">' .
				self::icon_pin() .
				'<span class="simcal-time-location-common-text simcal-event-address simcal-event-start-location">' .
				esc_html($address) .
				'</span></span>';
		}

		if ('' === $schedule && '' === $location) {
			return '';
		}

		return '<span class="simcal-time-location-common">' . $schedule . $location . '</span>';
	}

	/**
	 * Calendar-icon row for the event date and time.
	 *
	 * @param Default_Calendar $calendar
	 * @param Event            $event
	 * @return string
	 */
	private static function schedule_text(Default_Calendar $calendar, Event $event)
	{
		$date = $calendar->get_event_html($event, '[start-date]');
		$has_date = '' !== trim(wp_strip_all_tags($date));
		$text = $date;

		if ($event->whole_day) {
			if (!empty($event->multiple_days) && $event->end_dt instanceof Carbon) {
				$end_date = $calendar->get_event_html($event, '[end-date]');
				if ('' !== trim(wp_strip_all_tags($end_date))) {
					$text .= ' - ' . $end_date;
				}
			}
		} else {
			$start_time = $calendar->get_event_html($event, '[start-time]');
			$end_time = $event->end_dt instanceof Carbon ? $calendar->get_event_html($event, '[end-time]') : '';
			$end_date = '';
			if (!empty($event->multiple_days) && $event->end_dt instanceof Carbon) {
				$end_date = $calendar->get_event_html($event, '[end-date]');
			}

			$range = '';
			if ('' !== trim(wp_strip_all_tags($start_time))) {
				$range .= $start_time;
			}
			if ('' !== trim(wp_strip_all_tags($end_time))) {
				if ('' !== $range) {
					$range .= ' - ';
				}
				if ('' !== trim(wp_strip_all_tags($end_date))) {
					$range .= $end_date . ', ';
				}
				$range .= $end_time;
			}

			if ($has_date && '' !== trim(wp_strip_all_tags($range))) {
				$text .= ', ' . $range;
			} else {
				$text .= $range;
			}
		}

		if ('' === trim(wp_strip_all_tags($text))) {
			return '';
		}

		return '<span class="simcal-time-location-common-row">' .
			self::icon_calendar() .
			'<span class="simcal-time-location-common-text">' .
			$text .
			'</span></span>';
	}

	/**
	 * Three-dot menu for the [event-dropdown-menu] event template tag.
	 *
	 * Anchors to the top-right of the List Retro card. Hover and keyboard
	 * focus open the event link and the Add to Google Calendar link.
	 *
	 * @param Default_Calendar $calendar
	 * @param Event            $event
	 * @return string
	 */
	public static function menu_html(Default_Calendar $calendar, Event $event)
	{
		$links = '';

		if (!empty($event->link)) {
			$links .= $calendar->get_event_html(
				$event,
				'[link newwindow="yes"]' . __('See more details', 'google-calendar-events') . '[/link]',
			);
		}

		$links .= $calendar->get_event_html(
			$event,
			'[add-to-gcal-link]' . __('Add to GCal', 'google-calendar-events') . '[/add-to-gcal-link]',
		);

		if ('' === trim(wp_strip_all_tags($links))) {
			return '';
		}

		return '<span class="simcal-retro-menu">' .
			'<button type="button" class="simcal-retro-menu-button" aria-haspopup="true" aria-label="' .
			esc_attr__('Event actions', 'google-calendar-events') .
			'"><span aria-hidden="true"></span></button>' .
			'<span class="simcal-retro-actions">' .
			$links .
			'</span></span>';
	}

	/**
	 * Detail links that already exist on the event.
	 *
	 * @param Default_Calendar $calendar
	 * @param Event            $event
	 * @return string
	 */
	public static function actions_html(Default_Calendar $calendar, Event $event)
	{
		$links = '';

		if (!empty($event->link)) {
			$links .= $calendar->get_event_html(
				$event,
				'[link newwindow="yes"]' . __('See more details', 'google-calendar-events') . '[/link]',
			);
		}

		$links .= $calendar->get_event_html(
			$event,
			'[add-to-gcal-link]' . __('Add To GCal', 'google-calendar-events') . '[/add-to-gcal-link]',
		);

		if ('' === trim(wp_strip_all_tags($links))) {
			return '';
		}

		return '<div class="simcal-retro-actions">' . $links . '</div>';
	}

	/**
	 * Location, description, organizer, guests, and actions.
	 *
	 * @param Default_Calendar $calendar
	 * @param Event            $event
	 * @param bool             $include_title
	 * @param string           $label_id
	 * @param bool             $include_schedule Include the date, time, and location rows.
	 * @return string
	 */
	public static function details_html(
		Default_Calendar $calendar,
		Event $event,
		$include_title = true,
		$label_id = '',
		$include_schedule = true
	) {
		$html = '';

		if ($include_title) {
			$id_attr = '' !== $label_id ? ' id="' . esc_attr($label_id) . '"' : '';
			$html .= '<h3 class="simcal-retro-detail-title"' . $id_attr . '>';
			$html .= self::title_html($calendar, $event);
			$html .= '</h3>';
		}

		if ($include_schedule) {
			$html .= '<p class="simcal-time-location-common">';
			$html .= self::icon_calendar();
			$html .= '<span>' . self::date_html($calendar, $event) . ' ' . self::time_html($calendar, $event) . '</span>';
			$html .= '</p>';

			$address = self::address($event);
			if ('' !== $address) {
				$html .= '<p class="simcal-retro-location">';
				$html .= self::icon_pin();
				$html .= $calendar->get_event_html($event, '[location]');
				$html .= '</p>';
			}
		}

		if ('' !== trim(wp_strip_all_tags((string) $event->description))) {
			$html .= '<div class="simcal-retro-description">' . $calendar->get_event_html($event, '[description]') . '</div>';
		}

		$organizer = $event->get_organizer();
		if (!empty($organizer)) {
			$html .= '<div class="simcal-retro-people">';
			$html .=
				'<span class="simcal-retro-people-label">' . esc_html__('Organized by:', 'google-calendar-events') . '</span>';
			$html .= $calendar->get_event_html($event, '[organizer]');
			$html .= '</div>';
		}

		$attendees = $event->get_attendees();
		if (!empty($attendees)) {
			$html .= '<div class="simcal-retro-people">';
			$html .= '<span class="simcal-retro-people-label">' . esc_html__('Guests:', 'google-calendar-events') . '</span>';
			$html .= $calendar->get_event_html($event, '[attendees]');
			$html .= '</div>';
		}

		$html .= self::actions_html($calendar, $event);

		return $html;
	}

	/**
	 * Localized start date.
	 *
	 * @param Default_Calendar $calendar
	 * @param Event            $event
	 * @return string
	 */
	public static function date_html(Default_Calendar $calendar, Event $event)
	{
		$date = $calendar->get_event_html($event, '[start-date]');
		if ('' === trim(wp_strip_all_tags($date))) {
			return '';
		}

		return '<span class="simcal-retro-date">' . $date . '</span>';
	}

	/**
	 * Start location address, when one exists.
	 *
	 * @param Event $event
	 * @return string
	 */
	public static function address(Event $event)
	{
		if (!is_array($event->start_location)) {
			return '';
		}

		$address = isset($event->start_location['address'])
			? trim(wp_strip_all_tags(html_entity_decode((string) $event->start_location['address'], ENT_QUOTES, 'UTF-8')))
			: '';
		if ('' !== $address) {
			return $address;
		}

		$name = isset($event->start_location['name'])
			? trim(wp_strip_all_tags(html_entity_decode((string) $event->start_location['name'], ENT_QUOTES, 'UTF-8')))
			: '';

		return $name;
	}

	/**
	 * Previous or next chevron.
	 *
	 * @param string $direction prev|next
	 * @return string
	 */
	public static function chevron($direction)
	{
		$points = 'prev' === $direction ? '15 6 9 12 15 18' : '9 6 15 12 9 18';

		return '<svg class="simcal-retro-chevron" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><polyline points="' .
			$points .
			'" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></polyline></svg>';
	}

	/**
	 * Calendar icon.
	 *
	 * @return string
	 */
	public static function icon_calendar()
	{
		return '<svg class="simcal-retro-icon" viewBox="0 0 14 14" width="14" height="14" aria-hidden="true" focusable="false"><rect x="0.8" y="2.8" width="12.4" height="10.4" rx="2.4" fill="none" stroke="currentColor" stroke-width="1.6"></rect><path d="M4.4 0.8v2.8M9.6 0.8v2.8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path><circle cx="3.9" cy="7.6" r="0.95" fill="currentColor"></circle><circle cx="7" cy="7.6" r="0.95" fill="currentColor"></circle><circle cx="10.1" cy="7.6" r="0.95" fill="currentColor"></circle></svg>';
	}

	/**
	 * Clock icon.
	 *
	 * @return string
	 */
	public static function icon_clock()
	{
		return '<svg class="simcal-retro-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.6"></circle><polyline points="12 8 12 12 15 14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></polyline></svg>';
	}

	/**
	 * Location pin icon.
	 *
	 * @return string
	 */
	public static function icon_pin()
	{
		return '<svg class="simcal-retro-icon" viewBox="0 0 14 14" width="14" height="14" aria-hidden="true" focusable="false"><path d="M7 13.2L3.336 9.363A5.065 5.065 0 1 1 10.664 9.363Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"></path><circle cx="7" cy="5.865" r="1.8" fill="none" stroke="currentColor" stroke-width="1.6"></circle></svg>';
	}
}
