<?php
/**
 * Default Calendar - List Retro View
 *
 * @package SimpleCalendar/Calendars
 */
namespace SimpleCalendar\Calendars\Views;

use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Calendar_View;
use SimpleCalendar\Calendars\Default_Calendar;
use SimpleCalendar\Events\Event;
use SimpleCalendar\plugin_deps\Carbon\Carbon;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Default Calendar: List Retro view.
 */
class Default_Calendar_List_Retro implements Calendar_View
{
	/**
	 * Calendar.
	 *
	 * @access public
	 * @var Default_Calendar
	 */
	public $calendar = null;

	/**
	 * @var int
	 */
	private $start = 0;

	/**
	 * @var int
	 */
	private $end = 0;

	/**
	 * @var int
	 */
	private $prev = 0;

	/**
	 * @var int
	 */
	private $next = 0;

	/**
	 * Visible page heading.
	 *
	 * @var string
	 */
	private $heading = '';

	/**
	 * Constructor.
	 *
	 * @param string|Calendar $calendar
	 */
	public function __construct($calendar = '')
	{
		$this->calendar = $calendar;
	}

	/**
	 * @return string
	 */
	public function get_parent()
	{
		return 'default-calendar';
	}

	/**
	 * @return string
	 */
	public function get_type()
	{
		return 'list-retro';
	}

	/**
	 * @return string
	 */
	public function get_name()
	{
		return __('List Retro', 'google-calendar-events');
	}

	/**
	 * Add ajax actions.
	 */
	public function add_ajax_actions()
	{
		add_action('wp_ajax_simcal_default_calendar_draw_list_retro', [$this, 'draw_list_ajax']);
		add_action('wp_ajax_nopriv_simcal_default_calendar_draw_list_retro', [$this, 'draw_list_ajax']);
	}

	/**
	 * @return array
	 */
	public function scripts()
	{
		return [
			'simcal-default-calendar' => [
				'src' => SIMPLE_CALENDAR_ASSETS . 'generated/default-calendar.min.js',
				'deps' => ['jquery'],
				'in_footer' => true,
				'localize' => [
					'simcal_default_calendar' => simcal_common_scripts_variables(),
				],
			],
		];
	}

	/**
	 * @return array
	 */
	public function styles()
	{
		return [
			'simcal-default-calendar-common' => [
				'src' => SIMPLE_CALENDAR_ASSETS . 'generated/default-calendar-common.min.css',
				'media' => 'all',
			],
			'simcal-default-calendar-list-retro' => [
				'src' => SIMPLE_CALENDAR_ASSETS . 'generated/default-calendar-list-retro.min.css',
				'deps' => ['simcal-default-calendar-common'],
				'ver' => file_exists(SIMPLE_CALENDAR_PATH . 'assets/generated/default-calendar-list-retro.min.css')
					? filemtime(SIMPLE_CALENDAR_PATH . 'assets/generated/default-calendar-list-retro.min.css')
					: SIMPLE_CALENDAR_VERSION,
				'media' => 'all',
			],
		];
	}

	/**
	 * List Retro markup.
	 */
	public function html()
	{
		$calendar = $this->calendar;
		if (!($calendar instanceof Default_Calendar)) {
			return;
		}

		$disabled = $calendar->static || empty($calendar->events) ? ' disabled="disabled"' : '';
		$hide_header = 'yes' === get_post_meta($calendar->id, '_default_calendar_list_header', true);

		edit_post_link(
			__('Edit Calendar', 'google-calendar-events'),
			'<p class="simcal-align-right"><small>',
			'</small></p>',
			$calendar->id,
		);

		$body = $this->draw_list($calendar->start);

		echo '<div class="simcal-retro-list">';

		if (!$hide_header && !$calendar->static) {
			echo '<header class="simcal-calendar-head simcal-retro-header">';
			echo '<div class="simcal-retro-nav">';
			echo '<button type="button" class="simcal-nav-button simcal-prev" title="' .
				esc_attr__('Previous', 'google-calendar-events') .
				'" aria-label="' .
				esc_attr__('Previous', 'google-calendar-events') .
				'"' .
				$disabled .
				'>' .
				Retro_Event::chevron('prev') .
				'</button>';
			echo '<div class="simcal-nav simcal-current" aria-live="polite" aria-atomic="true" data-calendar-current="' .
				esc_attr($calendar->start) .
				'">';
			echo '<h2 class="simcal-retro-heading">' . esc_html($this->heading) . '</h2>';
			echo '</div>';
			echo '<button type="button" class="simcal-nav-button simcal-next" title="' .
				esc_attr__('Next', 'google-calendar-events') .
				'" aria-label="' .
				esc_attr__('Next', 'google-calendar-events') .
				'"' .
				$disabled .
				'>' .
				Retro_Event::chevron('next') .
				'</button>';
			echo '</div>';
			echo '</header>';
		}

		echo $body;
		echo '<div class="simcal-ajax-loader simcal-retro-loader" style="display: none;" aria-hidden="true"><i class="simcal-icon-spinner simcal-icon-spin"></i></div>';
		echo '</div>';

		\SimpleCalendar\Ics_Export::print_actions($calendar->id);
	}

	/**
	 * Events list for one page of the configured range.
	 *
	 * @param int $timestamp
	 * @param int $id
	 * @return string
	 */
	private function draw_list($timestamp, $id = 0)
	{
		$calendar = $this->calendar;
		if (!($calendar instanceof Default_Calendar)) {
			$calendar = simcal_get_calendar(intval($id));
			if (!($calendar instanceof Default_Calendar)) {
				return '';
			}
			$this->calendar = $calendar;
		}

		if (!$calendar->group_type) {
			$calendar->group_type = 'monthly';
		}
		if (!$calendar->group_span) {
			$calendar->group_span = 1;
		}

		$grouped = $this->get_events((int) $timestamp);
		$heading = $this->heading_text((int) $this->start);
		$this->heading = $heading;

		ob_start();
		$body_class = 'simcal-events-list-container simcal-retro-list-body';
		if (!empty($calendar->compact_list)) {
			$body_class .= ' simcal-retro-list-compact';
		}
		echo '<div class="' .
			esc_attr($body_class) .
			'" data-prev="' .
			esc_attr($this->prev) .
			'" data-next="' .
			esc_attr($this->next) .
			'" data-heading="' .
			esc_attr($heading) .
			'">';

		if (empty($grouped)) {
			echo '<p class="simcal-retro-empty">' . $this->empty_message_html() . '</p>';
		} else {
			foreach ($grouped as $ymd => $groups) {
				$day_events = $this->flatten_day($groups);
				if (empty($day_events)) {
					continue;
				}
				usort($day_events, [$this, 'compare_events']);

				$day_date = $this->day_from_ymd($ymd, $calendar->timezone);
				$day_timestamp = $day_date->getTimestamp();
				$format = $calendar->date_format ? $calendar->date_format : 'F j, Y';
				$label = Retro_Event::local_date($format, $day_timestamp, $calendar->timezone);
				$weekday = Retro_Event::local_date('D', $day_timestamp, $calendar->timezone);
				$day_number = Retro_Event::local_date('j', $day_timestamp, $calendar->timezone);

				$show_date = empty($calendar->compact_list);
				echo '<section class="simcal-retro-day">';
				if ($show_date) {
					echo '<h3 class="simcal-retro-day-heading">';
					echo '<span class="simcal-retro-day-label">' . esc_html($label) . '</span>';
					echo '<span class="simcal-retro-day-badge" aria-hidden="true"><span class="simcal-retro-day-name">' .
						esc_html($weekday) .
						'</span><span class="simcal-retro-day-number">' .
						esc_html($day_number) .
						'</span></span>';
					echo '</h3>';
					echo '<div class="simcal-retro-day-line" aria-hidden="true"></div>';
				}
				echo '<div class="simcal-retro-day-cards' . ($show_date ? '' : ' simcal-retro-day-cards-full') . '">';
				echo $this->cards_html($calendar, $day_events);
				echo '</div>';
				if ($show_date) {
					echo '<div class="simcal-retro-day-line simcal-retro-day-line-right" aria-hidden="true"></div>';
				}
				echo '<div class="simcal-retro-day-rule" aria-hidden="true"></div>';
				echo '</section>';
			}
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Event cards for one day, including the shared visible-event limit.
	 *
	 * @param Default_Calendar $calendar
	 * @param Event[]          $events
	 * @return string
	 */
	private function cards_html(Default_Calendar $calendar, array $events)
	{
		$limit = (int) $calendar->events_limit;
		$html = '<div class="simcal-events simcal-retro-events">';
		$index = 0;

		foreach ($events as $event) {
			if (!($event instanceof Event)) {
				continue;
			}
			$html .= $this->card_html($calendar, $event, $limit > -1 && $index >= $limit);
			$index++;
		}

		$html .= '</div>';

		if ($limit > -1 && $index > $limit) {
			$html .=
				'<button class="simcal-events-toggle" type="button" aria-expanded="false" aria-label="' .
				esc_attr__('Show more events', 'google-calendar-events') .
				'"><i class="simcal-icon-down simcal-icon-animate" aria-hidden="true"></i></button>';
		}

		return $html;
	}

	/**
	 * One event card.
	 *
	 * The card shell is the Retro layout. The contents are the same parsed
	 * event template List Classic prints, so admin template tags and the
	 * default template control what appears.
	 *
	 * @param Default_Calendar $calendar
	 * @param Event            $event
	 * @param bool             $hidden
	 * @return string
	 */
	private function card_html(Default_Calendar $calendar, Event $event, $hidden = false)
	{
		$style = Retro_Event::surface_style($event, 'list');
		$classes = 'simcal-retro-list-card simcal-event';
		$classes .= ' simcal-events-calendar-' . strval($event->calendar);
		if ($event->recurrence) {
			$classes .= ' simcal-event-recurring';
		}
		if ($event->venue) {
			$classes .= ' simcal-event-has-location';
		}
		if ($hidden) {
			$classes .= ' simcal-event-toggled';
			$style .= 'display:none;';
		}

		$html =
			'<article class="' .
			esc_attr($classes) .
			'" style="' .
			esc_attr($style) .
			'" itemscope itemtype="http://schema.org/Event" data-start="' .
			esc_attr($event->start) .
			'">';
		$html .= '<div class="simcal-event-details">' . $calendar->get_event_html($event) . '</div>';
		$html .= '</article>';

		return $html;
	}

	/**
	 * Empty-range message from the calendar settings, matching List Classic.
	 *
	 * @return string
	 */
	private function empty_message_html()
	{
		$calendar = $this->calendar;
		$message = get_post_meta($calendar->id, '_no_events_message', true);

		if ('events' == $calendar->group_type) {
			return !empty($message) ? $message : esc_html__('There are no upcoming events.', 'google-calendar-events');
		}

		if (!empty($message)) {
			return $message;
		}

		$from = Carbon::createFromTimestamp($this->start, $calendar->timezone)->getTimestamp();
		$to = Carbon::createFromTimestamp($this->end, $calendar->timezone)->getTimestamp();

		return apply_filters(
			'simcal_no_events_message',
			sprintf(
				__('Nothing from %1$s to %2$s.', 'google-calendar-events'),
				date_i18n($calendar->date_format, $from),
				date_i18n($calendar->date_format, $to),
			),
			$calendar->id,
			$from,
			$to,
		);
	}

	/**
	 * Page heading such as "July - 2023".
	 *
	 * @param int $timestamp
	 * @return string
	 */
	private function heading_text($timestamp)
	{
		$calendar = $this->calendar;
		$start = Carbon::createFromTimestamp($timestamp, $calendar->timezone);
		$type = $calendar->group_type ? $calendar->group_type : 'monthly';

		if ('monthly' === $type) {
			return Retro_Event::local_date('F', $start->getTimestamp(), $calendar->timezone) .
				' - ' .
				Retro_Event::local_date('Y', $start->getTimestamp(), $calendar->timezone);
		}

		$end = Carbon::createFromTimestamp($this->end > 0 ? $this->end : $timestamp, $calendar->timezone);
		$format = $calendar->date_format ? $calendar->date_format : 'M j, Y';
		$start_label = Retro_Event::local_date($format, $start->getTimestamp(), $calendar->timezone);
		$end_label = Retro_Event::local_date($format, $end->getTimestamp(), $calendar->timezone);

		if ($start_label === $end_label) {
			return $start_label;
		}

		return $start_label . ' – ' . $end_label;
	}

	/**
	 * @param array $groups
	 * @return Event[]
	 */
	private function flatten_day(array $groups)
	{
		$day_events = [];
		foreach ($groups as $group) {
			foreach ((array) $group as $event) {
				if ($event instanceof Event) {
					$day_events[] = $event;
				}
			}
		}

		return $day_events;
	}

	/**
	 * @param int|string $ymd
	 * @param string     $timezone
	 * @return Carbon
	 */
	private function day_from_ymd($ymd, $timezone)
	{
		$ymd = str_pad((string) $ymd, 8, '0', STR_PAD_LEFT);

		return Carbon::createFromFormat('Ymd', $ymd, $timezone)->startOfDay();
	}

	/**
	 * Events for the current list page, grouped by Ymd.
	 *
	 * Mirrors the Classic list range so month, week, day, and event paging stay the same.
	 *
	 * @param int $timestamp
	 * @return array
	 */
	private function get_events($timestamp)
	{
		$calendar = $this->calendar;
		$current = Carbon::createFromTimestamp($timestamp, $calendar->timezone);
		// A monthly page is the calendar month, so the heading matches the events.
		if ('monthly' == $calendar->group_type) {
			$current->startOfMonth();
		}
		$prev = $current->copy();
		$next = $current->copy();
		$this->start = $current->getTimestamp();
		$interval = $span = max(absint($calendar->group_span), 1);

		if ('monthly' == $calendar->group_type) {
			$this->prev = $prev->subMonths($span)->getTimestamp();
			$this->next = $next->addMonths($span)->getTimestamp();
		} elseif ('weekly' == $calendar->group_type) {
			$this->prev = $prev->subWeeks($span)->getTimestamp();
			$this->next = $next->addWeeks($span)->getTimestamp();
		} elseif ('daily' == $calendar->group_type) {
			$this->prev = $prev->subDays($span)->getTimestamp();
			$this->next = $next->addDays($span)->getTimestamp();
		}

		$events = is_array($calendar->events) ? $calendar->events : [];
		$daily_events = [];
		$paged_events = [];

		if ('events' != $calendar->group_type) {
			$this->end = $this->next - 1;
			$timestamps = array_keys($events);
			$lower_bound = array_filter($timestamps, [$this, 'filter_events_before']);
			$higher_bound = array_filter($lower_bound, [$this, 'filter_events_after']);
			if (!empty($higher_bound)) {
				$filtered = array_intersect_key($events, array_combine($higher_bound, $higher_bound));
				foreach ($filtered as $event_timestamp => $day_events) {
					$paged_events[intval($event_timestamp)] = $day_events;
				}
			}
		} else {
			$flattened_events = [];
			foreach ($events as $event_timestamp => $e) {
				$second = 0;
				foreach ($e as $event) {
					$flattened_events[intval($event_timestamp + $second)][] = $event;
					$second++;
				}
			}
			ksort($flattened_events, SORT_NUMERIC);
			$keys = array_keys($flattened_events);
			$cursor = 0;
			foreach ($keys as $event_timestamp) {
				if ($event_timestamp < $this->start) {
					$cursor++;
				}
			}
			$paged_events = array_slice($flattened_events, $cursor, $interval, true);
			$events_end = isset($keys[$cursor + $interval]) ? $keys[$cursor + $interval] : $calendar->end;
			$this->end = $events_end > $calendar->end ? $calendar->end : $events_end;
			$this->prev = isset($keys[$cursor - $interval]) ? $keys[$cursor - $interval] : $calendar->earliest_event;
			$this->next = isset($keys[$cursor + $interval]) ? $keys[$cursor + $interval] : $this->end;
		}

		foreach ($paged_events as $event_timestamp => $day_events) {
			if ($event_timestamp > $this->end) {
				continue;
			}
			$timezone = new \DateTimeZone($calendar->timezone);
			$date = \DateTime::createFromFormat('U', (string) $event_timestamp);
			if (!$date) {
				continue;
			}
			$offset = $timezone->getOffset($date);
			$date->add(\DateInterval::createFromDateString($offset . ' seconds'));
			$daily_events[intval($date->format('Ymd'))][] = $day_events;
		}

		ksort($daily_events, SORT_NUMERIC);

		return $daily_events;
	}

	/**
	 * @param Event $a
	 * @param Event $b
	 * @return int
	 */
	private function compare_events($a, $b)
	{
		$a_start = $a->start_dt instanceof Carbon ? $a->start_dt->getTimestamp() : (int) $a->start;
		$b_start = $b->start_dt instanceof Carbon ? $b->start_dt->getTimestamp() : (int) $b->start;
		if ($a_start === $b_start) {
			if ($a->calendar_priority != $b->calendar_priority) {
				return $a->calendar_priority < $b->calendar_priority ? -1 : 1;
			}
			if ($a->title == $b->title) {
				return 0;
			}
			return $a->title < $b->title ? -1 : 1;
		}

		return $a_start < $b_start ? -1 : 1;
	}

	/**
	 * Ajax callback to request another list page.
	 */
	public function draw_list_ajax()
	{
		if (!isset($_POST['ts']) || !isset($_POST['id'])) {
			wp_send_json_error('Missing arguments in default calendar list ajax request.');
		}

		$ts = absint($_POST['ts']);
		$id = absint($_POST['id']);
		$post = get_post($id);

		if (!$post || 'calendar' !== $post->post_type) {
			wp_send_json_error('Calendar not found.');
		}

		if ('publish' !== $post->post_status && !current_user_can('read_post', $id)) {
			wp_send_json_error('You do not have permission to view this calendar.');
		}

		wp_send_json_success($this->draw_list($ts, $id));
	}

	/**
	 * @param int $event
	 * @return bool
	 */
	private function filter_events_before($event)
	{
		return intval($event) >= intval($this->start);
	}

	/**
	 * @param int $event
	 * @return bool
	 */
	private function filter_events_after($event)
	{
		return intval($event) < intval($this->end);
	}
}
