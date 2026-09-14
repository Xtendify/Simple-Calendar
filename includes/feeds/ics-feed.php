<?php
/**
 * ICS Feed
 *
 * @package SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Feeds;

use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Feed;
use SimpleCalendar\Feeds\Admin\Ics_Feed_Admin;
use SimpleCalendar\plugin_deps\Carbon\Carbon;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * ICS feed.
 *
 * A feed that loads events from an uploaded ICS/iCal file.
 *
 * @since 4.1.0
 */
class Ics_Feed extends Feed
{
	/**
	 * Relative ICS file path within the uploads directory.
	 *
	 * @access protected
	 * @var string
	 */
	protected $ics_feed_file = '';

	/**
	 * Search query filter (supports simple OR).
	 *
	 * @access protected
	 * @var string
	 */
	protected $ics_search_query = '';

	/**
	 * Recurring events mode.
	 *
	 * @access protected
	 * @var string
	 */
	protected $ics_events_recurring = 'show';

	/**
	 * Maximum number of events to keep.
	 *
	 * @access protected
	 * @var int
	 */
	protected $ics_max_results = 2500;

	/**
	 * Whether to use event colors.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $ics_events_colors = false;

	/**
	 * Event color hex to apply when colors are enabled (set by Pro when active).
	 *
	 * @access protected
	 * @var string
	 */
	protected $ics_event_color = '';

	/**
	 * TZID aliases resolved from VTIMEZONE / Windows timezone names.
	 *
	 * @access protected
	 * @var array
	 */
	protected $ics_timezone_aliases = [];

	/**
	 * Memoized normalize_ics_timezone() results for this feed parse.
	 *
	 * @access protected
	 * @var array
	 */
	protected $ics_timezone_memo = [];

	/**
	 * Calendar-level timezone from X-WR-TIMEZONE, when valid.
	 *
	 * @access protected
	 * @var string
	 */
	protected $ics_file_timezone = '';

	/**
	 * Set properties.
	 *
	 * @since 4.1.0
	 *
	 * @param string|Calendar $calendar
	 * @param bool            $load_admin Whether to bootstrap the core ICS admin UI.
	 */
	public function __construct($calendar = '', $load_admin = true)
	{
		parent::__construct($calendar);

		$this->type = 'ics-feed';
		$this->name = __('ICS Feed', 'google-calendar-events');

		static $deletion_hook_registered = false;
		if (!$deletion_hook_registered) {
			add_action('before_delete_post', [__CLASS__, 'delete_post_ics_file']);
			$deletion_hook_registered = true;
		}

		if ($this->post_id > 0) {
			$this->ics_feed_file = sanitize_text_field(get_post_meta($this->post_id, '_ics_feed_file', true));
			$this->ics_search_query = (string) get_post_meta($this->post_id, '_ics_feed_search_query', true);
			$this->ics_events_recurring = esc_attr(get_post_meta($this->post_id, '_ics_feed_recurring', true));
			$this->ics_events_recurring = in_array($this->ics_events_recurring, ['show', 'first-only'], true)
				? $this->ics_events_recurring
				: 'show';
			$this->ics_max_results = max(absint(get_post_meta($this->post_id, '_ics_feed_max_results', true)), 0);

			// Color hex is resolved by Pro (Ics_Feed_Pro) via Google_Pro::get_event_colors().
			$this->ics_events_colors = 'yes' === get_post_meta($this->post_id, '_ics_feed_events_colors', true);

			// When a subclass loads admin itself ($load_admin = false), it loads events after its own props.
			if ($load_admin && (!is_admin() || defined('DOING_AJAX'))) {
				$this->events = $this->get_events();
			}
		}

		if (is_admin() && $load_admin && !defined('DOING_AJAX')) {
			new Ics_Feed_Admin($this);
		} elseif (is_admin() && $load_admin) {
			Ics_Feed_Admin::register_hooks();
		}
	}

	/**
	 * Upload subdirectory within wp-content/uploads.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public static function get_upload_subdirectory()
	{
		return 'simple-calendar';
	}

	/**
	 * Absolute path to the ICS upload directory.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public static function get_upload_dir_path()
	{
		$upload_dir = wp_upload_dir();

		if (!empty($upload_dir['error'])) {
			return '';
		}

		$path = trailingslashit($upload_dir['basedir']) . self::get_upload_subdirectory();

		if (!file_exists($path)) {
			wp_mkdir_p($path);
		}

		return is_dir($path) ? $path : '';
	}

	/**
	 * Build an upload file name with timestamp.
	 *
	 * @since 4.1.0
	 *
	 * @param string $original_filename Original file name.
	 * @param string $fallback_ext       Extension to use if the original has none.
	 *
	 * @return string
	 */
	private static function build_timestamped_filename($original_filename, $fallback_ext = 'ics')
	{
		$original_filename = sanitize_file_name((string) $original_filename);
		$ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
		$name = pathinfo($original_filename, PATHINFO_FILENAME);

		if (empty($ext)) {
			$ext = sanitize_key($fallback_ext);
		}
		if (empty($name)) {
			$name = 'calendar';
		}

		return sanitize_file_name($name . '-' . time() . '.' . $ext);
	}

	/**
	 * Save an uploaded ICS file for a calendar post.
	 *
	 * @since 4.1.0
	 *
	 * @param int   $post_id Post ID.
	 * @param array $file    Uploaded file array from $_FILES.
	 *
	 * @return string|\WP_Error Relative path within uploads on success.
	 */
	public static function save_uploaded_file($post_id, $file)
	{
		$post_id = absint($post_id);

		if ($post_id <= 0) {
			return new \WP_Error(
				'ics_invalid_post',
				__('A valid calendar post is required before uploading an ICS file.', 'google-calendar-events'),
			);
		}

		if (!isset($file['error']) || UPLOAD_ERR_NO_FILE === (int) $file['error']) {
			return new \WP_Error('ics_no_file', __('No ICS file was uploaded.', 'google-calendar-events'));
		}

		$upload_error = (int) $file['error'];

		if (UPLOAD_ERR_INI_SIZE === $upload_error || UPLOAD_ERR_FORM_SIZE === $upload_error) {
			return new \WP_Error(
				'ics_file_too_large',
				sprintf(
					/* translators: %s: maximum upload file size */
					__(
						'The ICS file is too large. Please increase the PHP upload limit (currently %s) or upload a smaller file.',
						'google-calendar-events',
					),
					size_format(wp_max_upload_size()),
				),
			);
		}

		if (UPLOAD_ERR_OK !== $upload_error) {
			return new \WP_Error('ics_upload_error', __('The ICS file could not be uploaded.', 'google-calendar-events'));
		}

		$filename = isset($file['name']) ? sanitize_file_name(wp_basename(wp_unslash($file['name']))) : '';
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		if (!in_array($extension, ['ics', 'ical'], true)) {
			$filetype = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], [
				'ics' => 'text/calendar',
				'ical' => 'text/calendar',
			]);

			if (!empty($filetype['ext']) && in_array($filetype['ext'], ['ics', 'ical'], true)) {
				$extension = $filetype['ext'];
			} else {
				return new \WP_Error(
					'ics_invalid_type',
					__('Please upload a valid .ics or .ical file.', 'google-calendar-events'),
				);
			}
		}

		if (empty($filename)) {
			$filename = 'calendar.' . $extension;
		}

		$contents = file_get_contents($file['tmp_name']);
		if (false === $contents || false === stripos($contents, 'BEGIN:VCALENDAR')) {
			return new \WP_Error(
				'ics_invalid_contents',
				__('The uploaded file does not appear to be a valid ICS feed.', 'google-calendar-events'),
			);
		}

		$upload_dir = self::get_upload_dir_path();
		if (empty($upload_dir)) {
			return new \WP_Error(
				'ics_upload_dir',
				__('Unable to create the ICS upload directory.', 'google-calendar-events'),
			);
		}

		// Replace any existing file saved for this calendar post.
		self::delete_post_ics_file($post_id);

		$filename = self::build_timestamped_filename($filename, $extension);
		$destination = trailingslashit($upload_dir) . $filename;
		$moved = is_uploaded_file($file['tmp_name']) ? @move_uploaded_file($file['tmp_name'], $destination) : false;

		if (!$moved && false === file_put_contents($destination, $contents)) {
			return new \WP_Error('ics_move_failed', __('The ICS file could not be saved.', 'google-calendar-events'));
		}

		$relative_path = trailingslashit(self::get_upload_subdirectory()) . $filename;
		update_post_meta($post_id, '_ics_feed_file', $relative_path);
		delete_post_meta($post_id, '_ics_feed_url');

		return $relative_path;
	}

	/**
	 * Resolve a stored ICS file path.
	 *
	 * @since 4.1.0
	 *
	 * @param string $relative_path Relative path within uploads.
	 *
	 * @return string
	 */
	public static function get_ics_file_path($relative_path)
	{
		$relative_path = ltrim((string) $relative_path, '/');

		if (empty($relative_path)) {
			return '';
		}

		$upload_dir = wp_upload_dir();
		$path = trailingslashit($upload_dir['basedir']) . $relative_path;

		return is_readable($path) ? $path : '';
	}

	/**
	 * Delete the ICS file associated with a calendar post.
	 *
	 * @since 4.1.0
	 *
	 * @param int $post_id Post ID.
	 */
	public static function delete_post_ics_file($post_id)
	{
		$post_id = absint($post_id);

		if ($post_id <= 0 || 'calendar' !== get_post_type($post_id)) {
			return;
		}

		$relative_path = get_post_meta($post_id, '_ics_feed_file', true);

		if (!empty($relative_path)) {
			$file_path = self::get_ics_file_path($relative_path);

			if (!empty($file_path) && file_exists($file_path)) {
				wp_delete_file($file_path);
			}
		}

		delete_post_meta($post_id, '_ics_feed_file');
		delete_post_meta($post_id, '_ics_feed_url');
	}

	/**
	 * Get events feed.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	public function get_events()
	{
		$events = get_transient('_simple-calendar_feed_id_' . strval($this->post_id) . '_' . $this->type);

		if (!empty($events)) {
			$this->maybe_set_timezone_from_cached_events($events);
			return is_array($events) ? $events : [];
		}

		$body = $this->get_ics_source_content();
		if (empty($body)) {
			return [];
		}

		$events = $this->parse_ics_events($body);

		if (!empty($events)) {
			$events = $this->filter_events_by_range($events);
			$events = $this->filter_events_by_search_query($events);

			if (!empty($events)) {
				ksort($events, SORT_NUMERIC);
				$events = $this->limit_events($events, $this->ics_max_results);
				set_transient(
					'_simple-calendar_feed_id_' . strval($this->post_id) . '_' . $this->type,
					$events,
					max(absint($this->cache), 1),
				);
			}
		}

		return is_array($events) ? $events : [];
	}

	/**
	 * Resolve raw ICS content from the configured source.
	 *
	 * Core uses the uploaded file. Add-ons may override to prefer a remote URL.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_ics_source_content()
	{
		if (empty($this->ics_feed_file)) {
			return '';
		}

		$file_path = self::get_ics_file_path($this->ics_feed_file);
		if (empty($file_path)) {
			return '';
		}

		$body = file_get_contents($file_path);

		return false === $body ? '' : $body;
	}

	/**
	 * Filter parsed events to the feed date range.
	 *
	 * @since 4.1.0
	 *
	 * @param array $events Parsed events.
	 *
	 * @return array
	 */
	protected function filter_events_by_range($events)
	{
		$filtered = [];
		$earliest_event = intval($this->time_min);
		$latest_event = intval($this->time_max);

		foreach ($events as $timestamp => $group) {
			foreach ($group as $event) {
				$start = intval($event['start']);
				$end = intval($event['end']);

				if ($earliest_event > 0 && $end <= $earliest_event) {
					continue;
				}
				if ($latest_event > 0 && $start >= $latest_event) {
					continue;
				}

				$key = intval($timestamp);
				while (isset($filtered[$key])) {
					$key--;
				}
				$filtered[$key][] = $event;
			}
		}

		return $filtered;
	}

	/**
	 * Parse ICS content into Simple Calendar events.
	 *
	 * @since 4.1.0
	 *
	 * @param string $ics_content Raw ICS file contents.
	 *
	 * @return array
	 */
	protected function parse_ics_events($ics_content)
	{
		$events = [];
		$ics_content = str_replace(["\r\n", "\r"], "\n", $ics_content);
		$ics_content = preg_replace("/\n[ \t]/", '', $ics_content);
		$this->prime_ics_timezone_map($ics_content);
		if ('use_calendar' === $this->timezone_setting) {
			$this->apply_ics_file_timezone();
		}
		$blocks = preg_split('/(?=BEGIN:VEVENT)/', $ics_content);

		if (empty($blocks) || !is_array($blocks)) {
			return $events;
		}

		foreach ($blocks as $block) {
			if (strpos($block, 'BEGIN:VEVENT') === false) {
				continue;
			}

			$properties = $this->parse_ics_block($block);
			$dtstart = $this->get_ics_property($properties, 'DTSTART');

			if (empty($dtstart['value'])) {
				continue;
			}

			$whole_day = $this->is_ics_whole_day_value($dtstart['value'], $dtstart['params']);
			$raw_start_tzid = !empty($dtstart['params']['TZID']) ? $dtstart['params']['TZID'] : '';
			// DATE values have no TZID; do not use X-WR-TIMEZONE unless timezone setting is use_calendar.
			$start_timezone = $this->normalize_ics_timezone(
				!$whole_day && '' !== $raw_start_tzid ? $raw_start_tzid : $this->get_ics_fallback_timezone(),
			);
			$start = $this->parse_ics_datetime($dtstart['value'], $start_timezone, $dtstart['params']);

			$dtend = $this->get_ics_property($properties, 'DTEND');
			$end_is_date = !empty($dtend['value']) && $this->is_ics_whole_day_value($dtend['value'], $dtend['params']);
			$raw_end_tzid = !empty($dtend['params']['TZID']) ? $dtend['params']['TZID'] : '';
			$end_timezone = $this->normalize_ics_timezone(
				!$end_is_date && '' !== $raw_end_tzid ? $raw_end_tzid : $start_timezone,
			);
			$end = $start;
			if (!empty($dtend['value'])) {
				if ($whole_day && $end_is_date) {
					// RFC 5545: DATE DTEND is exclusive. Derive from the date string so
					// DST zones that skip local midnight cannot leave the end on DTEND's day.
					$inclusive_end = $this->ics_date_to_inclusive_end($dtend['value'], $end_timezone);
					$end = $inclusive_end && $inclusive_end->gte($start) ? $inclusive_end : $start->copy();
				} else {
					$end = $this->parse_ics_datetime($dtend['value'], $end_timezone, $dtend['params']);
				}
			}

			if (!$start || !$end) {
				continue;
			}
			$title = sanitize_text_field($this->unescape_ics_text($this->get_ics_property_value($properties, 'SUMMARY')));
			$description = wp_kses_post($this->unescape_ics_text($this->get_ics_property_value($properties, 'DESCRIPTION')));
			$location = sanitize_text_field($this->unescape_ics_text($this->get_ics_property_value($properties, 'LOCATION')));
			$uid = sanitize_text_field($this->get_ics_property_value($properties, 'UID'));
			$link = esc_url_raw($this->get_ics_property_value($properties, 'URL'));
			$rrule = $this->get_ics_property_value($properties, 'RRULE');
			$duration = max(0, $end->getTimestamp() - $start->getTimestamp());
			$occurrences = [[$start, $end]];
			if (!empty($rrule) && 'show' === $this->ics_events_recurring) {
				$occurrences = $this->expand_rrule_occurrences($start, $duration, $rrule);
			}

			$meta = $this->get_ics_event_meta($properties);

			foreach ($occurrences as $occurrence) {
				[$occurrence_start, $occurrence_end] = $occurrence;
				$start_utc = Carbon::createFromTimestamp($occurrence_start->getTimestamp(), 'UTC');
				$end_utc = Carbon::createFromTimestamp($occurrence_end->getTimestamp(), 'UTC');
				$span = $occurrence_start->diffInDays($occurrence_end->copy()->endOfDay());
				$multiple_days = $span > 0 ? $span : false;
				$key = intval($occurrence_start->getTimestamp());

				while (isset($events[$key])) {
					$key--;
				}

				$events[$key][] = [
					'type' => 'ics-feed',
					'source' => !empty($this->ics_feed_url) ? $this->ics_feed_url : $this->ics_feed_file,
					'title' => $title,
					'description' => $description,
					'link' => $link,
					'visibility' => 'public',
					'uid' => $uid,
					'ical_id' => $uid,
					'calendar' => $this->post_id,
					'timezone' => $this->timezone,
					'start' => $occurrence_start->getTimestamp(),
					'start_utc' => $start_utc->getTimestamp(),
					'start_timezone' => $start_timezone,
					'start_location' => $location,
					'end' => $occurrence_end->getTimestamp(),
					'end_utc' => $end_utc->getTimestamp(),
					'end_timezone' => $end_timezone,
					'end_location' => $location,
					'whole_day' => $whole_day,
					'multiple_days' => $multiple_days,
					'recurrence' => !empty($rrule),
					'template' => $this->events_template,
					'meta' => $meta,
				];
			}
		}

		return $events;
	}

	/**
	 * Build event meta for a parsed ICS VEVENT.
	 *
	 * Core only sets color. Pro overrides this to add attachments, attendees, organizer.
	 *
	 * @since 4.1.0
	 *
	 * @param array $properties Parsed VEVENT properties.
	 * @return array
	 */
	protected function get_ics_event_meta($properties)
	{
		$meta = [];
		$color = $this->ics_event_color;
		if ($this->ics_events_colors && !empty($color)) {
			$meta['color'] = $color;
		}

		return $meta;
	}

	/**
	 * Filter events by the configured search query.
	 *
	 * @since 4.1.0
	 *
	 * @param array $events Parsed events.
	 *
	 * @return array
	 */
	protected function filter_events_by_search_query($events)
	{
		$query = trim((string) $this->ics_search_query);
		if (empty($query) || empty($events) || !is_array($events)) {
			return $events;
		}

		$terms = explode(strtolower(' OR '), strtolower($query));
		$filtered = [];

		foreach ($events as $timestamp => $group) {
			foreach ($group as $event) {
				$title = !empty($event['title']) ? strtolower((string) $event['title']) : '';
				$description = !empty($event['description'])
					? strtolower(wp_strip_all_tags((string) $event['description']))
					: '';
				$location = !empty($event['start_location']) ? strtolower((string) $event['start_location']) : '';

				$match = false;
				for ($i = 0; $i < count($terms); $i++) {
					$needle = trim((string) $terms[$i]);
					if ('' === $needle) {
						continue;
					}

					if (
						false !== strpos($title, $needle) ||
						false !== strpos($description, $needle) ||
						false !== strpos($location, $needle)
					) {
						$match = true;
						break;
					}
				}

				if (!$match) {
					continue;
				}

				$key = intval($timestamp);
				while (isset($filtered[$key])) {
					$key--;
				}
				$filtered[$key][] = $event;
			}
		}

		return $filtered;
	}

	/**
	 * Limit the number of events stored/displayed.
	 *
	 * @since 4.1.0
	 *
	 * @param array $events Parsed and filtered events.
	 * @param int   $max    Maximum events (0 = unlimited).
	 *
	 * @return array
	 */
	protected function limit_events($events, $max)
	{
		$max = absint($max);
		if ($max <= 0 || empty($events) || !is_array($events)) {
			return $events;
		}

		$out = [];
		$count = 0;

		foreach ($events as $timestamp => $group) {
			foreach ($group as $event) {
				if ($count >= $max) {
					break 2;
				}
				$key = intval($timestamp);
				while (isset($out[$key])) {
					$key--;
				}
				$out[$key][] = $event;
				$count++;
			}
		}

		return $out;
	}

	/**
	 * Expand recurring ICS events into occurrences.
	 *
	 * @since 4.1.0
	 *
	 * @param Carbon $start    Event start.
	 * @param int    $duration Event duration in seconds.
	 * @param string $rrule    RRULE string.
	 *
	 * @return array
	 */
	protected function expand_rrule_occurrences($start, $duration, $rrule)
	{
		$rule = $this->parse_rrule($rrule);
		$freq = isset($rule['FREQ']) ? strtoupper($rule['FREQ']) : '';

		if (empty($freq)) {
			return [[$start, $start->copy()->addSeconds($duration)]];
		}

		$occurrences = [];
		$current = $start->copy();
		$until = isset($rule['UNTIL']) ? $this->parse_rrule_until($rule['UNTIL'], $current->timezoneName) : null;
		$count_limit = isset($rule['COUNT']) ? max(1, absint($rule['COUNT'])) : 0;
		$window_start = intval($this->time_min);
		$window_end = intval($this->time_max);
		$max_iterations = 1000;
		$iteration = 0;
		$occurrence_index = 1;

		if ($window_start > 0 && $current->getTimestamp() < $window_start) {
			$current = $this->fast_forward_rrule($current, $rule, $window_start, $occurrence_index);
		}

		while ($iteration < $max_iterations) {
			if ($count_limit > 0 && $occurrence_index > $count_limit) {
				break;
			}

			$start_ts = $current->getTimestamp();
			$end_ts = $start_ts + $duration;

			if ($until instanceof Carbon && $start_ts > $until->getTimestamp()) {
				break;
			}
			if ($window_end > 0 && $start_ts > $window_end) {
				break;
			}

			if (($window_start <= 0 || $end_ts > $window_start) && ($window_end <= 0 || $start_ts < $window_end)) {
				$occurrences[] = [$current->copy(), $current->copy()->addSeconds($duration)];
			}

			$current = $this->advance_rrule($current, $rule);
			$occurrence_index++;
			$iteration++;
		}

		return !empty($occurrences) ? $occurrences : [[$start, $start->copy()->addSeconds($duration)]];
	}

	/**
	 * Parse an RRULE string.
	 *
	 * @since 4.1.0
	 *
	 * @param string $rrule RRULE string.
	 *
	 * @return array
	 */
	private function parse_rrule($rrule)
	{
		$parts = [];

		foreach (explode(';', strtoupper($rrule)) as $segment) {
			if (false === strpos($segment, '=')) {
				continue;
			}

			[$key, $value] = explode('=', $segment, 2);
			$parts[trim($key)] = trim($value);
		}

		return $parts;
	}

	/**
	 * Parse an RRULE UNTIL value.
	 *
	 * @since 4.1.0
	 *
	 * @param string $value    UNTIL value.
	 * @param string $timezone Timezone.
	 *
	 * @return Carbon|false
	 */
	private function parse_rrule_until($value, $timezone)
	{
		return $this->parse_ics_datetime($value, $timezone, []);
	}

	/**
	 * Fast-forward a recurring event toward the feed window.
	 *
	 * @since 4.1.0
	 *
	 * @param Carbon $current          Current occurrence start.
	 * @param array  $rule             Parsed RRULE.
	 * @param int    $window_start     Earliest allowed timestamp.
	 * @param int    $occurrence_index 1-based recurrence index in the series (updated in place).
	 *
	 * @return Carbon
	 */
	private function fast_forward_rrule($current, $rule, $window_start, &$occurrence_index = 1)
	{
		$freq = isset($rule['FREQ']) ? strtoupper($rule['FREQ']) : '';
		$interval = isset($rule['INTERVAL']) ? max(1, absint($rule['INTERVAL'])) : 1;
		$current_ts = $current->getTimestamp();

		if ($current_ts >= $window_start) {
			return $current;
		}

		if ('DAILY' === $freq) {
			$days = (int) floor(($window_start - $current_ts) / (DAY_IN_SECONDS * $interval));
			if ($days > 0) {
				$current->addDays($days * $interval);
				$occurrence_index += $days;
			}
		} elseif ('WEEKLY' === $freq) {
			$weeks = (int) floor(($window_start - $current_ts) / (WEEK_IN_SECONDS * $interval));
			if ($weeks > 0) {
				$current->addWeeks($weeks * $interval);
				$occurrence_index += $weeks;
			}
		} elseif ('MONTHLY' === $freq) {
			while ($current->getTimestamp() < $window_start) {
				$current->addMonths($interval);
				$occurrence_index++;
			}
			return $current;
		} elseif ('YEARLY' === $freq) {
			while ($current->getTimestamp() < $window_start) {
				$current->addYears($interval);
				$occurrence_index++;
			}
			return $current;
		}

		$guard = 0;
		while ($current->getTimestamp() < $window_start && $guard < 1000) {
			$current = $this->advance_rrule($current, $rule);
			$occurrence_index++;
			$guard++;
		}

		return $current;
	}

	/**
	 * Advance a recurring event to its next occurrence.
	 *
	 * @since 4.1.0
	 *
	 * @param Carbon $current Current occurrence start.
	 * @param array  $rule    Parsed RRULE.
	 *
	 * @return Carbon
	 */
	private function advance_rrule($current, $rule)
	{
		$freq = isset($rule['FREQ']) ? strtoupper($rule['FREQ']) : '';
		$interval = isset($rule['INTERVAL']) ? max(1, absint($rule['INTERVAL'])) : 1;

		switch ($freq) {
			case 'DAILY':
				return $current->copy()->addDays($interval);
			case 'WEEKLY':
				return $current->copy()->addWeeks($interval);
			case 'MONTHLY':
				return $current->copy()->addMonths($interval);
			case 'YEARLY':
				return $current->copy()->addYears($interval);
			default:
				return $current->copy()->addSecond();
		}
	}

	/**
	 * Parse a VEVENT block into property/value pairs.
	 *
	 * @since 4.1.0
	 *
	 * @param string $block ICS VEVENT block.
	 *
	 * @return array
	 */
	protected function parse_ics_block($block)
	{
		$properties = [];
		$lines = explode("\n", $block);
		$multi_value = ['ATTACH', 'ATTENDEE', 'EXDATE', 'RDATE'];

		foreach ($lines as $line) {
			$line = trim($line);
			if (empty($line) || false === strpos($line, ':')) {
				continue;
			}

			[$name_part, $value] = explode(':', $line, 2);
			$segments = explode(';', $name_part);
			$name = strtoupper($segments[0]);
			$params = [];

			if (count($segments) > 1) {
				foreach (array_slice($segments, 1) as $param_part) {
					if (false === strpos($param_part, '=')) {
						continue;
					}
					[$param_key, $param_value] = explode('=', $param_part, 2);
					$params[strtoupper($param_key)] = trim($param_value, '"');
				}
			}

			$entry = [
				'value' => $value,
				'params' => $params,
			];

			if (in_array($name, $multi_value, true)) {
				if (!isset($properties[$name]) || !is_array($properties[$name])) {
					$properties[$name] = [];
				}
				// Detect list-of-entries vs single entry shape.
				if (isset($properties[$name]['value'])) {
					$properties[$name] = [$properties[$name]];
				}
				$properties[$name][] = $entry;
			} else {
				$properties[$name] = $entry;
			}
		}

		return $properties;
	}

	/**
	 * Get a parsed ICS property (first entry for multi-value properties).
	 *
	 * @since 4.1.0
	 *
	 * @param array  $properties Parsed properties.
	 * @param string $name       Property name.
	 *
	 * @return array
	 */
	protected function get_ics_property($properties, $name)
	{
		$entries = $this->get_ics_properties($properties, $name);

		return !empty($entries[0])
			? $entries[0]
			: [
				'value' => '',
				'params' => [],
			];
	}

	/**
	 * Get all parsed ICS properties for a name (supports multi-value).
	 *
	 * @since 4.1.0
	 *
	 * @param array  $properties Parsed properties.
	 * @param string $name       Property name.
	 *
	 * @return array
	 */
	protected function get_ics_properties($properties, $name)
	{
		if (!isset($properties[$name]) || !is_array($properties[$name])) {
			return [];
		}

		// Single property shape: ['value' => ..., 'params' => ...].
		if (isset($properties[$name]['value'])) {
			return [$properties[$name]];
		}

		$list = [];
		foreach ($properties[$name] as $entry) {
			if (is_array($entry) && isset($entry['value'])) {
				$list[] = $entry;
			}
		}

		return $list;
	}

	/**
	 * Get a parsed ICS property value.
	 *
	 * @since 4.1.0
	 *
	 * @param array  $properties Parsed properties.
	 * @param string $name       Property name.
	 *
	 * @return string
	 */
	private function get_ics_property_value($properties, $name)
	{
		return $this->get_ics_property($properties, $name)['value'];
	}

	/**
	 * Parse an ICS datetime value.
	 *
	 * @since 4.1.0
	 *
	 * @param string $value    ICS datetime value.
	 * @param string $timezone Default timezone.
	 * @param array  $params   ICS property params.
	 *
	 * @return Carbon|false
	 */
	protected function parse_ics_datetime($value, $timezone, $params = [])
	{
		$value = trim($value);
		$timezone = $this->normalize_ics_timezone($timezone);

		try {
			if ($this->is_ics_whole_day_value($value, $params)) {
				$date = $this->ics_create_from_format('Ymd', substr($value, 0, 8), $timezone);
				return $date ? $date->startOfDay() : false;
			}

			if (substr($value, -1) === 'Z') {
				if (preg_match('/^\d{8}T\d{4}Z$/', $value)) {
					$date = $this->ics_create_from_format('Ymd\THi\Z', $value, 'UTC');
				} else {
					$date = $this->ics_create_from_format('Ymd\THis\Z', $value, 'UTC');
					if (!$date) {
						$date = $this->ics_create_from_format('Ymd\THi\Z', $value, 'UTC');
					}
				}
				return $date ? $date->setTimezone($timezone) : false;
			}

			if (preg_match('/^\d{8}T\d{4}$/', $value)) {
				$date = $this->ics_create_from_format('Ymd\THi', $value, $timezone);
			} else {
				$date = $this->ics_create_from_format('Ymd\THis', substr($value, 0, 15), $timezone);
				if (!$date) {
					$date = $this->ics_create_from_format('Ymd\THi', substr($value, 0, 13), $timezone);
				}
			}

			return $date ?: false;
		} catch (\Exception $e) {
			// InvalidTimeZoneException from setTimezone / startOfDay, etc.
			return false;
		}
	}

	/**
	 * Create a Carbon instance from a format without aborting sibling fallbacks.
	 *
	 * Carbon 2 strict mode throws InvalidFormatException instead of returning
	 * false, so each attempt must catch on its own.
	 *
	 * @since 4.2.1
	 *
	 * @param string $format   Date format.
	 * @param string $value    Date string.
	 * @param string $timezone Timezone name.
	 * @return Carbon|false
	 */
	protected function ics_create_from_format($format, $value, $timezone)
	{
		try {
			$date = Carbon::createFromFormat($format, $value, $timezone);

			return $date ?: false;
		} catch (\InvalidArgumentException $e) {
			return false;
		}
	}

	/**
	 * Convert an exclusive DATE-valued DTEND to the last inclusive moment.
	 *
	 * Exclusive DATE DTEND is the next calendar day at 00:00; minus one second
	 * is the prior day at 23:59:59. Uses subDay()->endOfDay() from the Ymd
	 * string so DST zones that skip local midnight cannot leave the end on the
	 * exclusive DTEND date.
	 *
	 * @since 4.2.1
	 *
	 * @param string $value    ICS DATE value (YYYYMMDD).
	 * @param string $timezone Timezone for the DATE.
	 * @return Carbon|false
	 */
	protected function ics_date_to_inclusive_end($value, $timezone)
	{
		$timezone = $this->normalize_ics_timezone($timezone);

		try {
			$date = $this->ics_create_from_format('Ymd', substr(trim($value), 0, 8), $timezone);

			return $date ? $date->subDay()->endOfDay() : false;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Collect timezone aliases from VCALENDAR / VTIMEZONE data.
	 *
	 * @since 4.2.1
	 *
	 * @param string $ics_content Unfolded ICS content.
	 */
	protected function prime_ics_timezone_map($ics_content)
	{
		$this->ics_timezone_aliases = [];
		$this->ics_timezone_memo = [];
		$this->ics_file_timezone = '';

		if (preg_match('/^X-WR-TIMEZONE:(.+)$/mi', $ics_content, $matches)) {
			$hint = $this->normalize_ics_timezone(trim($matches[1]), false, true);
			if ('' !== $hint) {
				$this->ics_file_timezone = $hint;
			}
		}

		if (!preg_match_all('/BEGIN:VTIMEZONE(.*?)END:VTIMEZONE/s', $ics_content, $zones)) {
			return;
		}

		foreach ($zones[1] as $zone) {
			$tzid = '';
			$location = '';
			if (preg_match('/^TZID:(.+)$/mi', $zone, $matches)) {
				$tzid = trim($matches[1]);
			}
			if (preg_match('/^X-LIC-LOCATION:(.+)$/mi', $zone, $matches)) {
				$location = trim($matches[1]);
			}
			if ('' === $tzid) {
				continue;
			}

			$resolved = '';
			if ('' !== $location) {
				$resolved = $this->normalize_ics_timezone($location, false, true);
			}
			if ('' === $resolved) {
				$resolved = $this->normalize_ics_timezone($tzid, false, true);
			}
			if ('' !== $resolved) {
				$this->ics_timezone_aliases[$tzid] = $resolved;
			}
		}
	}

	/**
	 * Normalize an ICS TZID to a valid IANA timezone identifier.
	 *
	 * Maps Microsoft Windows names (e.g. "GMT Standard Time") and VTIMEZONE
	 * aliases onto IANA IDs PHP/Carbon can use.
	 *
	 * @since 4.2.1
	 *
	 * @param string $tzid        Raw TZID or X-WR-TIMEZONE value.
	 * @param bool   $use_aliases Whether to consult VTIMEZONE aliases.
	 * @param bool   $strict      When true, return '' instead of the calendar fallback.
	 * @return string
	 */
	protected function normalize_ics_timezone($tzid, $use_aliases = true, $strict = false)
	{
		$tzid = html_entity_decode(trim((string) $tzid), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$tzid = trim($tzid, "\"'");
		$tzid = ltrim($tzid, '/');

		if ('' === $tzid) {
			return $strict ? '' : $this->get_ics_fallback_timezone();
		}

		$memo_key = $tzid . "\0" . (int) $use_aliases . "\0" . (int) $strict;
		if (array_key_exists($memo_key, $this->ics_timezone_memo)) {
			return $this->ics_timezone_memo[$memo_key];
		}

		$resolved = $tzid;
		if ($this->is_valid_iana_timezone($tzid)) {
			$this->ics_timezone_memo[$memo_key] = $resolved;
			return $resolved;
		}

		if ($use_aliases && !empty($this->ics_timezone_aliases[$tzid])) {
			$resolved = $this->ics_timezone_aliases[$tzid];
		} elseif (
			preg_match(
				'#((?:Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific|Etc)/[A-Za-z0-9_+\-/]+)$#',
				$tzid,
				$matches,
			) &&
			$this->is_valid_iana_timezone($matches[1])
		) {
			$resolved = $matches[1];
		} else {
			$windows = $this->map_windows_timezone($tzid);
			if ('' !== $windows) {
				$resolved = $windows;
			} elseif (preg_match('/^(UTC|GMT|Z)$/i', $tzid)) {
				$resolved = 'UTC';
			} else {
				$resolved = $strict ? '' : $this->get_ics_fallback_timezone();
			}
		}

		$this->ics_timezone_memo[$memo_key] = $resolved;

		return $resolved;
	}

	/**
	 * Fallback timezone when a TZID cannot be resolved.
	 *
	 * X-WR-TIMEZONE is used only when the calendar timezone setting is
	 * "use_calendar", matching Google Calendar feeds. use_site / use_custom
	 * keep the configured calendar timezone so DATE events stay on the
	 * displayed calendar day.
	 *
	 * @since 4.2.1
	 *
	 * @return string
	 */
	protected function get_ics_fallback_timezone()
	{
		if (
			'use_calendar' === $this->timezone_setting &&
			'' !== $this->ics_file_timezone &&
			$this->is_valid_iana_timezone($this->ics_file_timezone)
		) {
			return $this->ics_file_timezone;
		}

		if (!empty($this->timezone) && $this->is_valid_iana_timezone($this->timezone)) {
			return $this->timezone;
		}

		return 'UTC';
	}

	/**
	 * Apply X-WR-TIMEZONE to the feed when timezone setting is use_calendar.
	 *
	 * Calendar::set_events copies $feed->timezone onto $calendar->timezone,
	 * so DATE timestamps and grid placement share the same zone.
	 *
	 * @since 4.2.1
	 */
	protected function apply_ics_file_timezone()
	{
		if ('' === $this->ics_file_timezone || !$this->is_valid_iana_timezone($this->ics_file_timezone)) {
			return;
		}

		$this->timezone = $this->ics_file_timezone;
	}

	/**
	 * Restore use_calendar timezone from cached events.
	 *
	 * ICS transients store the events array only. Event rows include the
	 * timezone used at parse time so grid placement can match on cache hits.
	 *
	 * @since 4.2.1
	 *
	 * @param array $events Cached events.
	 */
	protected function maybe_set_timezone_from_cached_events($events)
	{
		if ('use_calendar' !== $this->timezone_setting || !is_array($events) || empty($events)) {
			return;
		}

		$group = reset($events);
		if (!is_array($group) || empty($group[0]['timezone'])) {
			return;
		}

		$tz = $group[0]['timezone'];
		if ($this->is_valid_iana_timezone($tz)) {
			$this->timezone = $tz;
		}
	}

	/**
	 * Whether a string is a valid IANA timezone identifier.
	 *
	 * Includes backward-compatible names (Asia/Calcutta, Europe/Kiev, Etc/GMT+N)
	 * that PHP still accepts. timezone_identifiers_list() defaults to ALL, which
	 * omits those and would silently fall back to the site timezone.
	 *
	 * @since 4.2.1
	 *
	 * @param string $timezone Timezone ID.
	 * @return bool
	 */
	protected function is_valid_iana_timezone($timezone)
	{
		static $iana = null;

		if (null === $iana) {
			$iana = array_flip(timezone_identifiers_list(\DateTimeZone::ALL_WITH_BC));
		}

		return isset($iana[(string) $timezone]);
	}

	/**
	 * Map a Microsoft Windows timezone name to IANA.
	 *
	 * @since 4.2.1
	 *
	 * @param string $tzid Windows timezone ID.
	 * @return string
	 */
	protected function map_windows_timezone($tzid)
	{
		static $lower = null;

		$map = $this->get_windows_timezone_map();
		if (null === $lower) {
			$lower = [];
			foreach ($map as $name => $iana) {
				$lower[strtolower((string) $name)] = $iana;
			}
		}

		if (isset($map[$tzid])) {
			$candidates = (array) $map[$tzid];
		} else {
			$key = strtolower((string) $tzid);
			$candidates = isset($lower[$key]) ? (array) $lower[$key] : [];
		}

		foreach ($candidates as $candidate) {
			if ($this->is_valid_iana_timezone($candidate)) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Windows timezone ID → IANA candidates (CLDR territory 001 defaults).
	 *
	 * @since 4.2.1
	 *
	 * @return array
	 */
	protected function get_windows_timezone_map()
	{
		static $map = null;

		if (null !== $map) {
			return $map;
		}

		$map = [
			'Afghanistan Standard Time' => ['Asia/Kabul'],
			'Alaskan Standard Time' => ['America/Anchorage'],
			'Aleutian Standard Time' => ['America/Adak'],
			'Altai Standard Time' => ['Asia/Barnaul'],
			'Arab Standard Time' => ['Asia/Riyadh'],
			'Arabian Standard Time' => ['Asia/Dubai'],
			'Arabic Standard Time' => ['Asia/Baghdad'],
			'Argentina Standard Time' => ['America/Argentina/Buenos_Aires', 'America/Buenos_Aires'],
			'Astrakhan Standard Time' => ['Europe/Astrakhan'],
			'Atlantic Standard Time' => ['America/Halifax'],
			'AUS Central Standard Time' => ['Australia/Darwin'],
			'Aus Central W. Standard Time' => ['Australia/Eucla'],
			'AUS Eastern Standard Time' => ['Australia/Sydney'],
			'Azerbaijan Standard Time' => ['Asia/Baku'],
			'Azores Standard Time' => ['Atlantic/Azores'],
			'Bahia Standard Time' => ['America/Bahia'],
			'Bangladesh Standard Time' => ['Asia/Dhaka'],
			'Belarus Standard Time' => ['Europe/Minsk'],
			'Bougainville Standard Time' => ['Pacific/Bougainville'],
			'Canada Central Standard Time' => ['America/Regina'],
			'Cape Verde Standard Time' => ['Atlantic/Cape_Verde'],
			'Caucasus Standard Time' => ['Asia/Yerevan'],
			'Cen. Australia Standard Time' => ['Australia/Adelaide'],
			'Central America Standard Time' => ['America/Guatemala'],
			'Central Asia Standard Time' => ['Asia/Almaty'],
			'Central Brazilian Standard Time' => ['America/Cuiaba'],
			'Central Europe Standard Time' => ['Europe/Budapest'],
			'Central European Standard Time' => ['Europe/Warsaw'],
			'Central Pacific Standard Time' => ['Pacific/Guadalcanal'],
			'Central Standard Time' => ['America/Chicago'],
			'Central Standard Time (Mexico)' => ['America/Mexico_City'],
			'Chatham Islands Standard Time' => ['Pacific/Chatham'],
			'China Standard Time' => ['Asia/Shanghai'],
			'Cuba Standard Time' => ['America/Havana'],
			'Dateline Standard Time' => ['Etc/GMT+12'],
			'E. Africa Standard Time' => ['Africa/Nairobi'],
			'E. Australia Standard Time' => ['Australia/Brisbane'],
			'E. Europe Standard Time' => ['Europe/Chisinau'],
			'E. South America Standard Time' => ['America/Sao_Paulo'],
			'Easter Island Standard Time' => ['Pacific/Easter'],
			'Eastern Standard Time' => ['America/New_York'],
			'Eastern Standard Time (Mexico)' => ['America/Cancun'],
			'Egypt Standard Time' => ['Africa/Cairo'],
			'Ekaterinburg Standard Time' => ['Asia/Yekaterinburg'],
			'Fiji Standard Time' => ['Pacific/Fiji'],
			'FLE Standard Time' => ['Europe/Kyiv', 'Europe/Kiev'],
			'Georgian Standard Time' => ['Asia/Tbilisi'],
			'GMT Standard Time' => ['Europe/London'],
			'Greenland Standard Time' => ['America/Nuuk', 'America/Godthab'],
			'Greenwich Standard Time' => ['Atlantic/Reykjavik'],
			'GTB Standard Time' => ['Europe/Bucharest'],
			'Haiti Standard Time' => ['America/Port-au-Prince'],
			'Hawaiian Standard Time' => ['Pacific/Honolulu'],
			'India Standard Time' => ['Asia/Kolkata', 'Asia/Calcutta'],
			'Iran Standard Time' => ['Asia/Tehran'],
			'Israel Standard Time' => ['Asia/Jerusalem'],
			'Jordan Standard Time' => ['Asia/Amman'],
			'Kaliningrad Standard Time' => ['Europe/Kaliningrad'],
			'Korea Standard Time' => ['Asia/Seoul'],
			'Libya Standard Time' => ['Africa/Tripoli'],
			'Line Islands Standard Time' => ['Pacific/Kiritimati'],
			'Lord Howe Standard Time' => ['Australia/Lord_Howe'],
			'Magadan Standard Time' => ['Asia/Magadan'],
			'Magallanes Standard Time' => ['America/Punta_Arenas'],
			'Marquesas Standard Time' => ['Pacific/Marquesas'],
			'Mauritius Standard Time' => ['Indian/Mauritius'],
			'Middle East Standard Time' => ['Asia/Beirut'],
			'Montevideo Standard Time' => ['America/Montevideo'],
			'Morocco Standard Time' => ['Africa/Casablanca'],
			'Mountain Standard Time' => ['America/Denver'],
			'Mountain Standard Time (Mexico)' => ['America/Mazatlan'],
			'Myanmar Standard Time' => ['Asia/Yangon', 'Asia/Rangoon'],
			'N. Central Asia Standard Time' => ['Asia/Novosibirsk'],
			'Namibia Standard Time' => ['Africa/Windhoek'],
			'Nepal Standard Time' => ['Asia/Kathmandu', 'Asia/Katmandu'],
			'New Zealand Standard Time' => ['Pacific/Auckland'],
			'Newfoundland Standard Time' => ['America/St_Johns'],
			'Norfolk Standard Time' => ['Pacific/Norfolk'],
			'North Asia East Standard Time' => ['Asia/Irkutsk'],
			'North Asia Standard Time' => ['Asia/Krasnoyarsk'],
			'North Korea Standard Time' => ['Asia/Pyongyang'],
			'Omsk Standard Time' => ['Asia/Omsk'],
			'Pacific SA Standard Time' => ['America/Santiago'],
			'Pacific Standard Time' => ['America/Los_Angeles'],
			'Pacific Standard Time (Mexico)' => ['America/Tijuana'],
			'Pakistan Standard Time' => ['Asia/Karachi'],
			'Paraguay Standard Time' => ['America/Asuncion'],
			'Qyzylorda Standard Time' => ['Asia/Qyzylorda'],
			'Romance Standard Time' => ['Europe/Paris'],
			'Russia Time Zone 3' => ['Europe/Samara'],
			'Russia Time Zone 10' => ['Asia/Srednekolymsk'],
			'Russia Time Zone 11' => ['Asia/Kamchatka'],
			'Russian Standard Time' => ['Europe/Moscow'],
			'SA Eastern Standard Time' => ['America/Cayenne'],
			'SA Pacific Standard Time' => ['America/Bogota'],
			'SA Western Standard Time' => ['America/La_Paz'],
			'Saint Pierre Standard Time' => ['America/Miquelon'],
			'Sakhalin Standard Time' => ['Asia/Sakhalin'],
			'Samoa Standard Time' => ['Pacific/Apia'],
			'Sao Tome Standard Time' => ['Africa/Sao_Tome'],
			'Saratov Standard Time' => ['Europe/Saratov'],
			'SE Asia Standard Time' => ['Asia/Bangkok'],
			'Singapore Standard Time' => ['Asia/Singapore'],
			'South Africa Standard Time' => ['Africa/Johannesburg'],
			'South Sudan Standard Time' => ['Africa/Juba'],
			'Sri Lanka Standard Time' => ['Asia/Colombo'],
			'Sudan Standard Time' => ['Africa/Khartoum'],
			'Syria Standard Time' => ['Asia/Damascus'],
			'Taipei Standard Time' => ['Asia/Taipei'],
			'Tasmania Standard Time' => ['Australia/Hobart'],
			'Tocantins Standard Time' => ['America/Araguaina'],
			'Tokyo Standard Time' => ['Asia/Tokyo'],
			'Tomsk Standard Time' => ['Asia/Tomsk'],
			'Tonga Standard Time' => ['Pacific/Tongatapu'],
			'Transbaikal Standard Time' => ['Asia/Chita'],
			'Turkey Standard Time' => ['Europe/Istanbul'],
			'Turks And Caicos Standard Time' => ['America/Grand_Turk'],
			'Ulaanbaatar Standard Time' => ['Asia/Ulaanbaatar'],
			'US Eastern Standard Time' => ['America/Indiana/Indianapolis', 'America/Indianapolis'],
			'US Mountain Standard Time' => ['America/Phoenix'],
			'UTC' => ['UTC'],
			'UTC-02' => ['Etc/GMT+2'],
			'UTC-08' => ['Etc/GMT+8'],
			'UTC-09' => ['Etc/GMT+9'],
			'UTC-11' => ['Etc/GMT+11'],
			'UTC+12' => ['Etc/GMT-12'],
			'UTC+13' => ['Etc/GMT-13'],
			'Venezuela Standard Time' => ['America/Caracas'],
			'Vladivostok Standard Time' => ['Asia/Vladivostok'],
			'Volgograd Standard Time' => ['Europe/Volgograd'],
			'W. Australia Standard Time' => ['Australia/Perth'],
			'W. Central Africa Standard Time' => ['Africa/Lagos'],
			'W. Europe Standard Time' => ['Europe/Berlin'],
			'W. Mongolia Standard Time' => ['Asia/Hovd'],
			'West Asia Standard Time' => ['Asia/Tashkent'],
			'West Bank Standard Time' => ['Asia/Hebron'],
			'West Pacific Standard Time' => ['Pacific/Port_Moresby'],
			'Yakutsk Standard Time' => ['Asia/Yakutsk'],
			'Yukon Standard Time' => ['America/Whitehorse'],
		];

		/**
		 * Filter Windows timezone ID to IANA mapping used by ICS Feed.
		 *
		 * @since 4.2.1
		 *
		 * @param array $map Windows TZID => list of IANA candidates.
		 */
		$filtered = apply_filters('simcal_ics_windows_timezone_map', $map);
		$map = is_array($filtered) ? $filtered : $map;

		return $map;
	}

	/**
	 * Whether an ICS datetime value represents a whole-day event.
	 *
	 * @since 4.1.0
	 *
	 * @param string $value  ICS datetime value.
	 * @param array  $params ICS property params.
	 *
	 * @return bool
	 */
	private function is_ics_whole_day_value($value, $params = [])
	{
		if (isset($params['VALUE']) && 'DATE' === strtoupper($params['VALUE'])) {
			return true;
		}

		return 8 === strlen(trim($value));
	}

	/**
	 * Unescape ICS text values.
	 *
	 * @since 4.1.0
	 *
	 * @param string $value Escaped ICS text.
	 *
	 * @return string
	 */
	protected function unescape_ics_text($value)
	{
		return str_replace(['\\n', '\\N', '\\,', '\\;'], ["\n", "\n", ',', ';'], $value);
	}
}
