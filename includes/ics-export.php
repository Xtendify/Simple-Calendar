<?php
/**
 * ICS Export
 *
 * @package SimpleCalendar
 */
namespace SimpleCalendar;

use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Events\Event;
use SimpleCalendar\plugin_deps\Carbon\Carbon;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Handles one-off ICS file downloads and frontend Export/Print actions.
 *
 * Subscription feed URLs are provided by the Google Calendar Pro add-on.
 *
 * @since 4.1.0
 */
class Ics_Export
{
	/**
	 * Hook into WordPress.
	 *
	 * @since 4.1.0
	 *
	 * @param bool $register_hooks Whether to register download hooks.
	 */
	public function __construct($register_hooks = true)
	{
		if (!$register_hooks) {
			return;
		}

		// Download uses $_GET and can run on simcal_init.
		add_action('simcal_init', [$this, 'maybe_export']);
	}

	/**
	 * Handle one-off ICS file download request.
	 *
	 * @since 4.1.0
	 */
	public function maybe_export()
	{
		if (!isset($_GET['simcal_export_ics'])) {
			return;
		}

		$calendar_id = absint($_GET['simcal_export_ics']);
		if ($calendar_id < 1) {
			wp_die(esc_html__('Invalid calendar.', 'google-calendar-events'), 400);
		}

		$post = get_post($calendar_id);
		if ($post->post_status !== 'publish' && !current_user_can('read_post', $calendar_id)) {
			wp_die(esc_html__('You do not have permission to view this calendar.', 'google-calendar-events'), 403);
		}

		if (!self::is_ics_export_enabled($calendar_id)) {
			wp_die(esc_html__('ICS export is not enabled for this calendar.', 'google-calendar-events'), 403);
		}

		$calendar = simcal_get_calendar($calendar_id);
		if (!($calendar instanceof Calendar)) {
			wp_die(esc_html__('Unable to load calendar.', 'google-calendar-events'), 500);
		}

		$ics = $this->build_ics($calendar);
		$filename = self::get_filename($post);

		nocache_headers();
		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . strlen($ics));

		echo $ics;
		exit();
	}

	/**
	 * Whether ICS export is enabled for a calendar.
	 *
	 * @since 4.1.0
	 *
	 * @param int $calendar_id Calendar post ID.
	 * @return bool
	 */
	public static function is_ics_export_enabled($calendar_id)
	{
		return 'yes' === get_post_meta(absint($calendar_id), '_display_ics_export', true);
	}

	/**
	 * Build ICS export (download) URL for a calendar.
	 *
	 * @since 4.1.0
	 *
	 * @param int $calendar_id Calendar post ID.
	 * @return string
	 */
	public static function get_export_url($calendar_id)
	{
		return add_query_arg(
			[
				'simcal_export_ics' => absint($calendar_id),
			],
			home_url('/'),
		);
	}

	/**
	 * Build ICS content string for a calendar ID.
	 *
	 * @since 4.1.0
	 *
	 * @param int $calendar_id Calendar post ID.
	 * @return string
	 */
	public static function build_ics_for_calendar($calendar_id)
	{
		$calendar = simcal_get_calendar(absint($calendar_id));
		if (!($calendar instanceof Calendar)) {
			return '';
		}

		$builder = new self(false);

		return $builder->build_ics($calendar);
	}

	/**
	 * Print calendar action buttons (Export, Print).
	 *
	 * Pro can inject extra buttons via {@see 'simcal_calendar_action_buttons'}.
	 *
	 * @since 4.1.0
	 *
	 * @param int $calendar_id Calendar post ID.
	 */
	public static function print_actions($calendar_id)
	{
		$calendar_id = absint($calendar_id);
		if ($calendar_id < 1) {
			return;
		}

		$show_ics_export = self::is_ics_export_enabled($calendar_id);
		$show_print = 'yes' === get_post_meta($calendar_id, '_display_print_calendar', true);

		ob_start();
		/**
		 * Print extra calendar action buttons (e.g. Pro ICS URL).
		 *
		 * @since 4.1.0
		 *
		 * @param int  $calendar_id      Calendar post ID.
		 * @param bool $show_ics_export  Whether ICS Export is enabled in admin.
		 */
		do_action('simcal_calendar_action_buttons', $calendar_id, $show_ics_export);
		$extra_buttons = ob_get_clean();

		if (!$show_ics_export && !$show_print && '' === $extra_buttons) {
			return;
		}

		echo '<div class="simcal-calendar-actions" role="group" aria-label="' .
			esc_attr__('Calendar actions', 'google-calendar-events') .
			'">';

		if ($show_ics_export) {
			$export_url = esc_url(self::get_export_url($calendar_id));
			$export_tooltip = esc_attr__(
				'By pressing this you will get an ICS file of this calendar.',
				'google-calendar-events',
			);

			echo '<a href="' .
				$export_url .
				'" class="button simcal-calendar-action simcal-ics-export-button" title="' .
				$export_tooltip .
				'">';
			echo self::render_icon('export');
			echo '<span class="simcal-calendar-action-label">' . esc_html__('Export', 'google-calendar-events') . '</span>';
			echo '</a>';
		}

		echo $extra_buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by add-on callbacks.

		if ($show_print) {
			$print_tooltip = esc_attr__('Print a copy of this calendar view.', 'google-calendar-events');

			echo '<button type="button" class="button simcal-calendar-action simcal-print-calendar-button" title="' .
				$print_tooltip .
				'">';
			echo self::render_icon('print');
			echo '<span class="simcal-calendar-action-label">' . esc_html__('Print', 'google-calendar-events') . '</span>';
			echo '</button>';
		}

		echo '</div>';
	}

	/**
	 * @deprecated 4.1.0 Use print_actions() instead.
	 */
	public static function print_button($calendar_id)
	{
		self::print_actions($calendar_id);
	}

	/**
	 * Render an inline SVG icon for calendar action buttons.
	 *
	 * @since 4.1.0
	 *
	 * @param string $icon Icon key.
	 * @return string
	 */
	public static function render_icon($icon)
	{
		$icons = [
			'export' =>
				'<svg class="simcal-calendar-action-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentcolor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
			'link' =>
				'<svg class="simcal-calendar-action-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentcolor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
			'print' =>
				'<svg class="simcal-calendar-action-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentcolor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>',
		];

		return isset($icons[$icon]) ? $icons[$icon] : '';
	}

	/**
	 * Build ICS content for a calendar.
	 *
	 * @since 4.1.0
	 *
	 * @param Calendar $calendar Calendar instance.
	 * @return string
	 */
	public function build_ics(Calendar $calendar)
	{
		$calendar_name = get_the_title($calendar->id);
		$lines = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//Simple Calendar//Google Calendar Events//' . SIMPLE_CALENDAR_VERSION,
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'X-WR-CALNAME:' . $this->escape_text($calendar_name),
		];

		$seen = [];
		if (!empty($calendar->events) && is_array($calendar->events)) {
			foreach ($calendar->events as $event_group) {
				if (!is_array($event_group)) {
					continue;
				}
				foreach ($event_group as $event) {
					if (!($event instanceof Event)) {
						continue;
					}

					$dedupe_key = !empty($event->uid) ? $event->uid : $event->ical_id . '-' . $event->start;
					if (isset($seen[$dedupe_key])) {
						continue;
					}
					$seen[$dedupe_key] = true;

					$lines = array_merge($lines, $this->build_vevent($event));
				}
			}
		}

		$lines[] = 'END:VCALENDAR';

		$output = '';
		foreach ($lines as $line) {
			$output .= $this->fold_line($line) . "\r\n";
		}

		return $output;
	}

	/**
	 * Build a VEVENT block for an event.
	 *
	 * @since 4.1.0
	 *
	 * @param Event $event Event instance.
	 * @return array
	 */
	private function build_vevent(Event $event)
	{
		$uid = !empty($event->uid) ? $event->uid : $event->ical_id;
		if (empty($uid)) {
			$uid = md5($event->title . $event->start . $event->end);
		}
		$uid .= '@simple-calendar';

		$lines = ['BEGIN:VEVENT', 'UID:' . $this->escape_text($uid), 'DTSTAMP:' . gmdate('Ymd\THis\Z')];

		if ($event->whole_day) {
			$start_dt = $event->start_dt ?: Carbon::createFromTimestamp($event->start);
			$start_date = $start_dt->format('Ymd');
			if ($event->end_dt) {
				// Stored all-day end is last inclusive moment; ICS DTEND is exclusive next date.
				$end_date = $event->end_dt->copy()->startOfDay()->addDay()->format('Ymd');
			} else {
				$end_date = $start_dt->copy()->startOfDay()->addDay()->format('Ymd');
			}
			$lines[] = 'DTSTART;VALUE=DATE:' . $start_date;
			$lines[] = 'DTEND;VALUE=DATE:' . $end_date;
		} else {
			$start_utc = !empty($event->start_utc) ? $event->start_utc : $event->start;
			$end_utc = !empty($event->end_utc) ? $event->end_utc : $event->end;
			if (empty($end_utc)) {
				$end_utc = $start_utc;
			}
			$lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', $start_utc);
			$lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', $end_utc);
		}

		if (!empty($event->title)) {
			$lines[] = 'SUMMARY:' . $this->escape_text($event->title);
		}

		if (!empty($event->description)) {
			$lines[] = 'DESCRIPTION:' . $this->escape_text($event->description);
		}

		$location = '';
		if (!empty($event->start_location['name'])) {
			$location = $event->start_location['name'];
		} elseif (!empty($event->start_location['address'])) {
			$location = $event->start_location['address'];
		}
		if (!empty($location)) {
			$lines[] = 'LOCATION:' . $this->escape_text($location);
		}

		if (!empty($event->link)) {
			$lines[] = 'URL:' . $this->escape_text($event->link);
		}

		$lines[] = 'END:VEVENT';

		return $lines;
	}

	/**
	 * Escape text for ICS content lines.
	 *
	 * @since 4.1.0
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function escape_text($text)
	{
		$text = wp_strip_all_tags((string) $text);
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = str_replace('\\', '\\\\', $text);
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);
		$text = str_replace("\n", '\\n', $text);
		$text = str_replace([';', ','], ['\\;', '\\,'], $text);

		return $text;
	}

	/**
	 * Fold long ICS lines per RFC 5545.
	 *
	 * @since 4.1.0
	 *
	 * @param string $line Content line.
	 * @return string
	 */
	private function fold_line($line)
	{
		if (strlen($line) <= 75) {
			return $line;
		}

		$folded = '';
		while (strlen($line) > 75) {
			if (function_exists('mb_strcut')) {
				$chunk = mb_strcut($line, 0, 75, 'UTF-8');
			} else {
				$chunk = substr($line, 0, 75);
			}
			$folded .= $chunk . "\r\n ";
			$line = substr($line, strlen($chunk));
		}

		return $folded . $line;
	}

	/**
	 * Build a safe ICS filename from the calendar post title.
	 *
	 * @since 4.1.0
	 *
	 * @param \WP_Post $post Calendar post.
	 * @return string
	 */
	public static function get_filename($post)
	{
		$name = sanitize_file_name($post->post_title);
		if (empty($name)) {
			$name = 'calendar-' . $post->ID;
		}

		if (!preg_match('/\.ics$/i', $name)) {
			$name .= '.ics';
		}

		return $name;
	}
}
