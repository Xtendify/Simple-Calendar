<?php
/**
 * SC Event Feed
 *
 * @package SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Feeds;

use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Feed;
use SimpleCalendar\Feeds\Admin\Sc_Event_Admin;
use SimpleCalendar\plugin_deps\Carbon\Carbon;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * SC Event feed.
 *
 * A feed that loads locally created SC Event custom post type items.
 *
 * @since 4.2.0
 */
class Sc_Event extends Feed
{
	/**
	 * Selected SC Event category term ID.
	 *
	 * 0 means all categories (no category filter).
	 *
	 * @access protected
	 * @var int
	 */
	protected $category_id = 0;

	/**
	 * Set properties.
	 *
	 * @since 4.2.0
	 *
	 * @param string|Calendar $calendar
	 * @param bool            $load_admin Whether to bootstrap the admin UI.
	 */
	public function __construct($calendar = '', $load_admin = true)
	{
		parent::__construct($calendar);

		$this->type = 'sc-event';
		$this->name = __('SC Event', 'google-calendar-events');

		if ($this->post_id > 0) {
			// Local events are authored in the site timezone, so "event source default"
			// falls back to the WordPress timezone rather than a remote calendar TZ.
			if ('use_calendar' === $this->timezone_setting) {
				$this->timezone = self::esc_timezone_string(simcal_get_wp_timezone());
			}

			$this->category_id = absint(get_post_meta($this->post_id, '_sc_event_category', true));

			if (!is_admin() || defined('DOING_AJAX')) {
				$this->events = $this->get_events();
			}
		}

		if (is_admin() && $load_admin && !defined('DOING_AJAX')) {
			new Sc_Event_Admin($this);
		} elseif (is_admin() && $load_admin) {
			Sc_Event_Admin::register_hooks();
		}
	}

	/**
	 * Parse a datetime-local input value into a Unix timestamp.
	 *
	 * @since 4.2.0
	 *
	 * @param string $value    Datetime string from a datetime-local input.
	 * @param string $timezone Timezone identifier used to interpret the value.
	 *
	 * @return int 0 when the value cannot be parsed.
	 */
	public static function parse_datetime($value, $timezone = '')
	{
		$value = trim((string) $value);

		if ('' === $value) {
			return 0;
		}

		$timezone = self::esc_timezone_string($timezone);
		$formats = ['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d'];

		foreach ($formats as $format) {
			try {
				$parsed = Carbon::createFromFormat($format, $value, $timezone);
			} catch (\Exception $e) {
				$parsed = false;
			}

			if ($parsed instanceof Carbon) {
				if ('Y-m-d' === $format) {
					$parsed->startOfDay();
				}

				return $parsed->getTimestamp();
			}
		}

		try {
			$parsed = Carbon::parse($value, $timezone);
		} catch (\Exception $e) {
			return 0;
		}

		return $parsed instanceof Carbon ? $parsed->getTimestamp() : 0;
	}

	/**
	 * Format a Unix timestamp for a datetime-local input.
	 *
	 * @since 4.2.0
	 *
	 * @param int    $timestamp Unix timestamp.
	 * @param string $timezone  Timezone identifier.
	 *
	 * @return string
	 */
	public static function format_datetime($timestamp, $timezone = '')
	{
		$timestamp = absint($timestamp);

		if ($timestamp <= 0) {
			return '';
		}

		$timezone = self::esc_timezone_string($timezone);

		try {
			return Carbon::createFromTimestamp($timestamp, $timezone)->format('Y-m-d\TH:i');
		} catch (\Exception $e) {
			return '';
		}
	}

	/**
	 * Return a valid timezone string.
	 *
	 * @since 4.2.0
	 *
	 * @param string $timezone Timezone identifier.
	 *
	 * @return string
	 */
	public static function esc_timezone_string($timezone = '')
	{
		$timezone = is_string($timezone) ? trim($timezone) : '';

		if (empty($timezone)) {
			$timezone = simcal_get_wp_timezone();
		}

		return !empty($timezone) ? $timezone : 'UTC';
	}

	/**
	 * Get events feed.
	 *
	 * Queries published SC Event posts, optionally filtered by category,
	 * and normalizes them into the standard Simple Calendar event array.
	 *
	 * When no category is selected, all published SC Events are returned.
	 * Events without a category are included only in that unfiltered case.
	 *
	 * @since 4.2.0
	 *
	 * @return array
	 */
	public function get_events()
	{
		$timezone = $this->get_feed_timezone();
		$this->timezone = $timezone;

		$query_args = [
			'post_type' => 'sc-event',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'nopaging' => true,
			'no_found_rows' => true,
			'ignore_sticky_posts' => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		];

		if ($this->category_id > 0) {
			if (!term_exists($this->category_id, 'sc-event-category')) {
				return [];
			}
		}
		$query_args['tax_query'] = [
			[
				'taxonomy' => 'sc-event-category',
				'field' => 'term_id',
				'terms' => [$this->category_id],
				'include_children' => true,
			],
		];

		$meta_query = [
			'relation' => 'AND',
			[
				'key' => '_sc_event_start',
				'value' => 0,
				'compare' => '>',
				'type' => 'NUMERIC',
			],
		];

		$earliest_event = intval($this->time_min);
		$latest_event = intval($this->time_max);

		if ($earliest_event > 0) {
			$meta_query[] = [
				'key' => '_sc_event_end',
				'value' => $earliest_event,
				'compare' => '>=',
				'type' => 'NUMERIC',
			];
		}

		if ($latest_event > 0) {
			$meta_query[] = [
				'key' => '_sc_event_start',
				'value' => $latest_event,
				'compare' => '<=',
				'type' => 'NUMERIC',
			];
		}

		$query_args['meta_query'] = $meta_query;

		$posts = get_posts($query_args);

		if (empty($posts) || !is_array($posts)) {
			return [];
		}

		$events = [];

		foreach ($posts as $post) {
			$event = $this->normalize_post_to_event($post, $timezone);

			if (empty($event)) {
				continue;
			}

			$key = intval($event['start']);

			while (isset($events[$key])) {
				$key--;
			}

			$events[$key][] = $event;
		}

		if (!empty($events)) {
			ksort($events, SORT_NUMERIC);
		}

		return $events;
	}

	/**
	 * Timezone used when converting local SC Events to calendar events.
	 *
	 * "Event source default" maps to the site timezone, because SC Events
	 * are authored in WordPress using the site timezone.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	protected function get_feed_timezone()
	{
		if ('use_calendar' === $this->timezone_setting || empty($this->timezone)) {
			return self::esc_timezone_string(simcal_get_wp_timezone());
		}

		return self::esc_timezone_string($this->timezone);
	}

	/**
	 * Convert an SC Event post into the shared event array structure.
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post $post     SC Event post.
	 * @param string   $timezone Calendar timezone.
	 *
	 * @return array
	 */
	protected function normalize_post_to_event($post, $timezone)
	{
		if (!($post instanceof \WP_Post)) {
			return [];
		}

		$start_ts = absint(get_post_meta($post->ID, '_sc_event_start', true));

		if ($start_ts <= 0) {
			return [];
		}

		$end_ts = absint(get_post_meta($post->ID, '_sc_event_end', true));

		if ($end_ts <= 0) {
			$end_ts = $start_ts;
		}

		if ($end_ts < $start_ts) {
			$end_ts = $start_ts;
		}

		try {
			$start = Carbon::createFromTimestamp($start_ts, $timezone);
			$end = Carbon::createFromTimestamp($end_ts, $timezone);
			$start_utc = Carbon::createFromTimestamp($start_ts, 'UTC');
			$end_utc = Carbon::createFromTimestamp($end_ts, 'UTC');
		} catch (\Exception $e) {
			return [];
		}

		$span = $start->diffInDays($end->copy()->endOfDay());

		if (0 === $span) {
			if ($start->toDateString() !== $end->toDateString() && '00:00:00' !== $end->toTimeString()) {
				$span = 1;
			}
		}

		$location = sanitize_text_field((string) get_post_meta($post->ID, '_sc_event_location', true));
		$lat = get_post_meta($post->ID, '_sc_event_lat', true);
		$lng = get_post_meta($post->ID, '_sc_event_lng', true);
		$categories = $this->get_event_categories($post->ID);

		$location_data = [
			'name' => $location,
			'address' => $location,
			'lat' => is_numeric($lat) ? (float) $lat : 0,
			'lng' => is_numeric($lng) ? (float) $lng : 0,
		];

		return [
			'type' => 'sc-event',
			'source' => $this->name,
			'title' => get_the_title($post),
			'description' => $post->post_content,
			'link' => get_permalink($post),
			'visibility' => 'public',
			'uid' => (string) $post->ID,
			'calendar' => $this->post_id,
			'timezone' => $timezone,
			'start' => $start_ts,
			'start_utc' => $start_utc->getTimestamp(),
			'start_timezone' => $timezone,
			'start_location' => $location_data,
			'end' => $end_ts,
			'end_utc' => $end_utc->getTimestamp(),
			'end_timezone' => $timezone,
			'end_location' => $location_data,
			'whole_day' => false,
			'multiple_days' => $span > 0 ? $span : false,
			'recurrence' => false,
			'template' => $this->events_template,
			'meta' => [
				'categories' => $categories,
			],
		];
	}

	/**
	 * Get category data assigned to an SC Event.
	 *
	 * @since 4.2.0
	 *
	 * @param int $post_id SC Event post ID.
	 *
	 * @return array
	 */
	protected function get_event_categories($post_id)
	{
		$terms = get_the_terms($post_id, 'sc-event-category');

		if (is_wp_error($terms) || empty($terms) || !is_array($terms)) {
			return [];
		}

		$categories = [];

		foreach ($terms as $term) {
			$categories[] = [
				'id' => absint($term->term_id),
				'name' => sanitize_text_field($term->name),
				'slug' => sanitize_title($term->slug),
			];
		}

		return $categories;
	}
}
