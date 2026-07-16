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
 * Handles ICS file generation and download.
 *
 * @since 4.1.0
 */
class Ics_Export
{
	/**
	 * Hook into WordPress.
	 *
	 * @since 4.1.0
	 */
	public function __construct()
	{
		// Run after Plugin::init() creates the Objects factory (simcal_init).
		add_action('simcal_init', [$this, 'maybe_export']);
	}

	/**
	 * Handle ICS export download request.
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
		if (!$post || 'calendar' !== $post->post_type) {
			wp_die(esc_html__('Calendar not found.', 'google-calendar-events'), 404);
		}

		$enabled = get_post_meta($calendar_id, '_display_ics_export', true);
		if ('yes' !== $enabled) {
			wp_die(esc_html__('ICS export is not enabled for this calendar.', 'google-calendar-events'), 403);
		}

		$calendar = simcal_get_calendar($calendar_id);
		if (!($calendar instanceof Calendar)) {
			wp_die(esc_html__('Unable to load calendar.', 'google-calendar-events'), 500);
		}

		$ics = $this->build_ics($calendar);
		$filename = $this->get_filename($post);

		nocache_headers();
		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . strlen($ics));

		echo $ics;
		exit();
	}

	/**
	 * Build ICS export URL for a calendar.
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
	 * Print the ICS export button when enabled.
	 *
	 * @since 4.1.0
	 *
	 * @param int $calendar_id Calendar post ID.
	 */
	public static function print_button($calendar_id)
	{
		$is_ics_export = get_post_meta($calendar_id, '_display_ics_export');
		if (!isset($is_ics_export[0]) || empty($is_ics_export[0]) || $is_ics_export[0] !== 'yes') {
			return;
		}

		$url = esc_url(self::get_export_url($calendar_id));
		$label = esc_html__('Export ICS', 'google-calendar-events');

		echo '<a href="' .
			$url .
			'" class="button ics-export-button" id="ics-export-button-' .
			absint($calendar_id) .
			'">' .
			$label .
			'</a>';
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
				$end_date = $event->end_dt->copy()->addSeconds(59)->format('Ymd');
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
			$folded .= substr($line, 0, 75) . "\r\n ";
			$line = substr($line, 75);
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
	private function get_filename($post)
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
