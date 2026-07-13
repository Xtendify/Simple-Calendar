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
	 * Whether to use event colors from the ICS feed.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $ics_events_colors = false;

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
		$this->name = __('ICS Calendar', 'google-calendar-events');

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
			$colors = get_post_meta($this->post_id, '_ics_feed_events_colors', true);
			$this->ics_events_colors = 'yes' === $colors;

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

		if (UPLOAD_ERR_OK !== (int) $file['error']) {
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
				__('The uploaded file does not appear to be a valid ICS calendar.', 'google-calendar-events'),
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

			$start_timezone = !empty($dtstart['params']['TZID']) ? $dtstart['params']['TZID'] : $this->timezone;
			$start = $this->parse_ics_datetime($dtstart['value'], $start_timezone, $dtstart['params']);

			$dtend = $this->get_ics_property($properties, 'DTEND');
			$end_timezone = !empty($dtend['params']['TZID']) ? $dtend['params']['TZID'] : $start_timezone;
			$end = !empty($dtend['value'])
				? $this->parse_ics_datetime($dtend['value'], $end_timezone, $dtend['params'])
				: $start;

			if (!$start || !$end) {
				continue;
			}

			$whole_day = $this->is_ics_whole_day_value($dtstart['value'], $dtstart['params']);
			$title = sanitize_text_field($this->unescape_ics_text($this->get_ics_property_value($properties, 'SUMMARY')));
			$description = wp_kses_post($this->unescape_ics_text($this->get_ics_property_value($properties, 'DESCRIPTION')));
			$location = sanitize_text_field($this->unescape_ics_text($this->get_ics_property_value($properties, 'LOCATION')));
			$uid = sanitize_text_field($this->get_ics_property_value($properties, 'UID'));
			$link = esc_url_raw($this->get_ics_property_value($properties, 'URL'));
			$rrule = $this->get_ics_property_value($properties, 'RRULE');
			$color = sanitize_text_field($this->get_ics_property_value($properties, 'COLOR'));
			$duration = max(0, $end->getTimestamp() - $start->getTimestamp());
			$occurrences = [[$start, $end]];
			if (!empty($rrule) && 'show' === $this->ics_events_recurring) {
				$occurrences = $this->expand_rrule_occurrences($start, $duration, $rrule);
			}

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

				$meta = [];
				if ($this->ics_events_colors && !empty($color)) {
					$meta['color'] = $color;
				}

				$events[$key][] = [
					'type' => 'ics-feed',
					'source' => $this->ics_feed_file,
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
		$matched = 0;

		if ($window_start > 0 && $current->getTimestamp() < $window_start) {
			$current = $this->fast_forward_rrule($current, $rule, $window_start);
		}

		while ($iteration < $max_iterations) {
			$start_ts = $current->getTimestamp();
			$end_ts = $start_ts + $duration;

			if ($until instanceof Carbon && $start_ts > $until->getTimestamp()) {
				break;
			}
			if ($window_end > 0 && $start_ts > $window_end) {
				break;
			}
			if ($count_limit > 0 && $matched >= $count_limit) {
				break;
			}

			if (($window_start <= 0 || $end_ts > $window_start) && ($window_end <= 0 || $start_ts < $window_end)) {
				$occurrences[] = [$current->copy(), $current->copy()->addSeconds($duration)];
				$matched++;
			}

			$current = $this->advance_rrule($current, $rule);
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
	 * @param Carbon $current      Current occurrence start.
	 * @param array  $rule         Parsed RRULE.
	 * @param int    $window_start Earliest allowed timestamp.
	 *
	 * @return Carbon
	 */
	private function fast_forward_rrule($current, $rule, $window_start)
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
			}
		} elseif ('WEEKLY' === $freq) {
			$weeks = (int) floor(($window_start - $current_ts) / (WEEK_IN_SECONDS * $interval));
			if ($weeks > 0) {
				$current->addWeeks($weeks * $interval);
			}
		} elseif ('MONTHLY' === $freq) {
			while ($current->getTimestamp() < $window_start) {
				$current->addMonths($interval);
			}
			return $current;
		} elseif ('YEARLY' === $freq) {
			while ($current->getTimestamp() < $window_start) {
				$current->addYears($interval);
			}
			return $current;
		}

		$guard = 0;
		while ($current->getTimestamp() < $window_start && $guard < 1000) {
			$current = $this->advance_rrule($current, $rule);
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
					$params[strtoupper($param_key)] = $param_value;
				}
			}

			$properties[$name] = [
				'value' => $value,
				'params' => $params,
			];
		}

		return $properties;
	}

	/**
	 * Get a parsed ICS property.
	 *
	 * @since 4.1.0
	 *
	 * @param array  $properties Parsed properties.
	 * @param string $name       Property name.
	 *
	 * @return array
	 */
	private function get_ics_property($properties, $name)
	{
		if (!isset($properties[$name]) || !is_array($properties[$name])) {
			return [
				'value' => '',
				'params' => [],
			];
		}

		return $properties[$name];
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
		$timezone = !empty($timezone) ? $timezone : $this->timezone;

		if ($this->is_ics_whole_day_value($value, $params)) {
			$date = Carbon::createFromFormat('Ymd', substr($value, 0, 8), $timezone);
			return $date ? $date->startOfDay()->addSeconds(59) : false;
		}

		if (substr($value, -1) === 'Z') {
			$date = Carbon::createFromFormat('Ymd\THis\Z', $value, 'UTC');
			if (!$date) {
				$date = Carbon::createFromFormat('Ymd\THi\Z', $value, 'UTC');
			}
			return $date ? $date->setTimezone($timezone) : false;
		}

		$date = Carbon::createFromFormat('Ymd\THis', substr($value, 0, 15), $timezone);
		if (!$date) {
			$date = Carbon::createFromFormat('Ymd\THi', substr($value, 0, 13), $timezone);
		}

		return $date ?: false;
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
	private function unescape_ics_text($value)
	{
		return str_replace(['\\n', '\\N', '\\,', '\\;'], ["\n", "\n", ',', ';'], $value);
	}
}
