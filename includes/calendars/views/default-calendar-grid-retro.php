<?php
/**
 * Default Calendar - Grid Retro View
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
 * Default Calendar: Grid Retro view.
 */
class Default_Calendar_Grid_Retro implements Calendar_View
{
	/**
	 * Calendar.
	 *
	 * @access public
	 * @var Default_Calendar
	 */
	public $calendar = null;

	/**
	 * Current display start.
	 *
	 * @access private
	 * @var int
	 */
	private $start = 0;

	/**
	 * Current display end.
	 *
	 * @access private
	 * @var int
	 */
	private $end = 0;

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
	 * Get the view parent calendar type.
	 *
	 * @return string
	 */
	public function get_parent()
	{
		return 'default-calendar';
	}

	/**
	 * Get the view type.
	 *
	 * @return string
	 */
	public function get_type()
	{
		return 'grid-retro';
	}

	/**
	 * Get the view name.
	 *
	 * @return string
	 */
	public function get_name()
	{
		return __('Grid Retro', 'google-calendar-events');
	}

	/**
	 * Add ajax actions.
	 */
	public function add_ajax_actions()
	{
		add_action('wp_ajax_simcal_default_calendar_draw_grid_retro', [$this, 'draw_grid_ajax']);
		add_action('wp_ajax_nopriv_simcal_default_calendar_draw_grid_retro', [$this, 'draw_grid_ajax']);
	}

	/**
	 * Scripts to load for this view.
	 *
	 * Uses the same qTip bubble script as Grid Classic.
	 *
	 * @return array
	 */
	public function scripts()
	{
		return [
			'simcal-qtip' => [
				'src' => SIMPLE_CALENDAR_ASSETS . 'generated/vendor/jquery.qtip.min.js',
				'deps' => ['jquery'],
				'in_footer' => true,
			],
			'simcal-default-calendar' => [
				'src' => SIMPLE_CALENDAR_ASSETS . 'generated/default-calendar.min.js',
				'deps' => ['jquery', 'simcal-qtip'],
				'in_footer' => true,
				'localize' => [
					'simcal_default_calendar' => simcal_common_scripts_variables(),
				],
			],
		];
	}

	/**
	 * Styles to load for this view.
	 *
	 * @return array
	 */
	public function styles()
	{
		return [
			'simcal-qtip' => [
				'src' => SIMPLE_CALENDAR_ASSETS . 'generated/vendor/jquery.qtip.min.css',
				'media' => 'all',
			],
			'simcal-default-calendar-common' => [
				'src' => SIMPLE_CALENDAR_ASSETS . 'generated/default-calendar-common.min.css',
				'deps' => ['simcal-qtip'],
				'media' => 'all',
			],
			'simcal-default-calendar-grid-retro' => [
				'src' => SIMPLE_CALENDAR_ASSETS . 'generated/default-calendar-grid-retro.min.css',
				'deps' => ['simcal-qtip', 'simcal-default-calendar-common'],
				'media' => 'all',
			],
		];
	}

	/**
	 * Grid Retro markup.
	 */
	public function html()
	{
		$calendar = $this->calendar;
		if (!($calendar instanceof Default_Calendar)) {
			return;
		}

		$start = Carbon::createFromTimestamp($calendar->start, $calendar->timezone)->startOfMonth();
		$disabled = $calendar->static ? ' disabled="disabled"' : '';

		edit_post_link(
			__('Edit Calendar', 'google-calendar-events'),
			'<p class="simcal-align-right"><small>',
			'</small></p>',
			$calendar->id,
		);
		?>
		<div class="simcal-retro-grid">
			<header class="simcal-calendar-head simcal-retro-header">
				<button type="button" class="simcal-nav-button simcal-prev" title="<?php esc_attr_e(
    	'Previous Month',
    	'google-calendar-events',
    ); ?>" aria-label="<?php esc_attr_e(
	'Previous Month',
	'google-calendar-events',
); ?>"<?php echo $disabled; ?>><?php echo Retro_Event::chevron('prev'); ?></button>
				<div class="simcal-nav simcal-current simcal-retro-heading" aria-live="polite" aria-atomic="true" data-calendar-current="<?php echo esc_attr(
    	$start->getTimestamp(),
    ); ?>">
					<h2><?php
     // Same order Grid Classic uses: whichever of year or month appears first in the date format.
     $year_pos = strcspn($calendar->date_format, 'Y y');
     $month_pos = strcspn($calendar->date_format, 'F M m n');
     $month_html =
     	'<span class="simcal-current-month">' .
     	esc_html(Retro_Event::local_date('F', $start->getTimestamp(), $calendar->timezone)) .
     	'</span>';
     $year_html =
     	'<span class="simcal-current-year">' .
     	esc_html(Retro_Event::local_date('Y', $start->getTimestamp(), $calendar->timezone)) .
     	'</span>';
     if ($year_pos < $month_pos) {
     	echo $year_html . ' ' . $month_html;
     } else {
     	echo $month_html . '<span class="simcal-retro-comma" aria-hidden="true">,</span> ' . $year_html;
     }
     ?></h2>
				</div>
				<button type="button" class="simcal-nav-button simcal-next" title="<?php esc_attr_e(
    	'Next Month',
    	'google-calendar-events',
    ); ?>" aria-label="<?php esc_attr_e(
	'Next Month',
	'google-calendar-events',
); ?>"<?php echo $disabled; ?>><?php echo Retro_Event::chevron('next'); ?></button>
			</header>
			<div class="simcal-ajax-loader simcal-retro-loader" style="display: none;" aria-hidden="true"><i class="simcal-icon-spinner simcal-icon-spin"></i></div>
			<table class="simcal-retro-table" data-event-bubble-trigger="<?php echo esc_attr($calendar->event_bubble_trigger); ?>">
				<caption class="simcal-retro-sr"><?php echo esc_html(
    	sprintf(
    		/* translators: 1: month name, 2: year. */
    		__('%1$s %2$s', 'google-calendar-events'),
    		Retro_Event::local_date('F', $start->getTimestamp(), $calendar->timezone),
    		Retro_Event::local_date('Y', $start->getTimestamp(), $calendar->timezone),
    	),
    ); ?></caption>
				<thead>
					<tr>
						<?php echo $this->weekday_headings($calendar); ?>
					</tr>
				</thead>
				<?php echo $this->draw_month((int) $start->month, (int) $start->year); ?>
			</table>
		</div>
		<?php \SimpleCalendar\Ics_Export::print_actions($calendar->id);
	}

	/**
	 * Weekday column headings.
	 *
	 * @param Default_Calendar $calendar
	 * @return string
	 */
	private function weekday_headings(Default_Calendar $calendar)
	{
		$week_starts = (int) $calendar->week_starts;
		$short = simcal_get_calendar_names_i18n('day', 'short');
		$full = simcal_get_calendar_names_i18n('day', 'full');
		$html = '';

		$indexes = [];
		for ($i = $week_starts; $i <= 6; $i++) {
			$indexes[] = $i;
		}
		if (0 !== $week_starts) {
			for ($i = 0; $i < $week_starts; $i++) {
				$indexes[] = $i;
			}
		}

		foreach ($indexes as $i) {
			$letter = function_exists('mb_substr') ? mb_substr($short[$i], 0, 1, 'UTF-8') : substr($short[$i], 0, 1);
			$html .=
				'<th class="simcal-week-day" scope="col" abbr="' .
				esc_attr($full[$i]) .
				'">' .
				'<span class="simcal-retro-weekday-full">' .
				esc_html($full[$i]) .
				'</span>' .
				'<span class="simcal-retro-weekday-short" aria-hidden="true">' .
				esc_html($short[$i]) .
				'</span>' .
				'<span class="simcal-retro-weekday-letter" aria-hidden="true">' .
				esc_html($letter) .
				'</span>' .
				'<span class="simcal-retro-weekday-compact" aria-hidden="true">' .
				esc_html(function_exists('mb_substr') ? mb_substr($short[$i], 0, 2, 'UTF-8') : substr($short[$i], 0, 2)) .
				'</span>' .
				'</th>';
		}

		return $html;
	}

	/**
	 * Make a calendar grid for one month.
	 *
	 * @param int $month
	 * @param int $year
	 * @param int $id
	 * @return string
	 */
	private function draw_month($month, $year, $id = 0)
	{
		$calendar = $this->calendar;
		if (!($calendar instanceof Default_Calendar)) {
			$calendar = simcal_get_calendar(intval($id));
			if (!($calendar instanceof Default_Calendar)) {
				return '';
			}
			$this->calendar = $calendar;
		}

		$now = $calendar->now;
		$current = Carbon::create($year, $month, 1, 0, 0, 0, $calendar->timezone);
		$current_min = $current->getTimestamp();
		$current_max = $current->copy()->endOfDay()->getTimestamp();
		$week_starts = (int) $calendar->week_starts;
		$month_starts = (int) $current->dayOfWeek;
		$days_in_month = (int) $current->daysInMonth;
		$this->start = $current_min;
		$this->end = $current->copy()->endOfMonth()->getTimestamp();

		$day_events = $this->events_by_day($calendar);

		ob_start();
		echo '<tbody class="simcal-month simcal-month-' . esc_attr($month) . '">';

		$days_in_row = 0;
		$week_day = $month_starts;
		$void_days_needed = ($month_starts - $week_starts + 7) % 7;

		echo '<tr class="simcal-week">';
		for ($a = 0; $a < $void_days_needed; $a++) {
			echo '<td class="simcal-day simcal-day-void"></td>';
			$days_in_row++;
		}
		$week_day = $month_starts;

		for ($day = 1; $day <= $days_in_month; $day++) {
			$count = isset($day_events[$day]) ? count($day_events[$day]) : 0;
			$classes = 'simcal-day simcal-day-' . $day . ' simcal-weekday-' . $week_day;

			if ($current_min <= $now && $current_max >= $now) {
				$classes .= ' simcal-today simcal-present';
			} elseif ($current_max < $now) {
				$classes .= ' simcal-past';
			} else {
				$classes .= ' simcal-future';
			}
			if ($count > 0) {
				$classes .= ' simcal-day-has-events simcal-day-has-' . $count . '-events';
			}

			echo '<td class="' . esc_attr($classes) . '" data-events-count="' . esc_attr($count) . '">';
			echo '<span class="simcal-day-number">' . esc_html($day) . '</span>';

			if ($count > 0) {
				echo $this->day_events_html($calendar, $day_events[$day], $day);
			}

			echo '</td>';

			if (6 === $week_day) {
				$week_day = -1;
			}
			$week_day++;

			if (6 === $days_in_row) {
				echo '</tr>';
				if ($day < $days_in_month) {
					echo '<tr class="simcal-week">';
				}
				$days_in_row = -1;
			}
			$days_in_row++;

			$current_min = Carbon::createFromTimestamp($current_min, $calendar->timezone)->addDay()->getTimestamp();
			$current_max = Carbon::createFromTimestamp($current_max, $calendar->timezone)->addDay()->getTimestamp();
		}

		if ($days_in_row > 0) {
			$remainder = 6 - $days_in_row;
			for ($i = 0; $i <= $remainder; $i++) {
				echo '<td class="simcal-day simcal-day-void"></td>';
			}
			echo '</tr>';
		}
		echo '</tbody>';

		return ob_get_clean();
	}

	/**
	 * Events indexed by day of the month.
	 *
	 * @param Default_Calendar $calendar
	 * @return array
	 */
	private function events_by_day(Default_Calendar $calendar)
	{
		$events = $calendar->events;
		$day_events = [];

		if (!$events || !is_array($events)) {
			return $day_events;
		}

		$timestamps = array_keys($events);
		$lower_bound = array_filter($timestamps, [$this, 'filter_events_before']);
		$higher_bound = array_filter($lower_bound, [$this, 'filter_events_after']);
		$filtered = !empty($higher_bound) ? array_intersect_key($events, array_combine($higher_bound, $higher_bound)) : [];

		$current_month = Carbon::createFromTimestamp($this->start, $calendar->timezone)->month;
		$current_year = Carbon::createFromTimestamp($this->start, $calendar->timezone)->year;
		$expand = 'yes' == get_post_meta($calendar->id, '_default_calendar_expand_multi_day_events', true);

		foreach ($filtered as $timestamp => $events_in_day) {
			foreach ($events_in_day as $event) {
				if (!($event instanceof Event)) {
					continue;
				}

				if ($expand) {
					$event_start = Carbon::createFromTimestamp($event->start, $calendar->timezone);
					$event_end = Carbon::createFromTimestamp($event->end, $calendar->timezone);
					for ($day = $event_start->copy(); $day->lte($event_end->copy()->endOfDay()); $day->addDay()) {
						if ((int) $day->month !== (int) $current_month || (int) $day->year !== (int) $current_year) {
							continue;
						}
						if ($day->isSameDay($event_end) && 0 === (int) $event_end->hour && 0 === (int) $event_end->minute) {
							continue;
						}
						$day_key = (int) $day->format('j');
						$day_events[$day_key][$event->uid] = $event;
					}
				} else {
					$first_day = (int) Carbon::createFromTimestamp($timestamp, $calendar->timezone)->endOfDay()->day;
					$day_events[$first_day][] = $event;
				}
			}
		}

		foreach ($day_events as $day_key => $items) {
			$items = array_values($items);
			usort($items, [$this, 'compare_events']);
			$day_events[$day_key] = $items;
		}
		ksort($day_events, SORT_NUMERIC);

		return $day_events;
	}

	/**
	 * Cards for one day.
	 *
	 * @param Default_Calendar $calendar
	 * @param array            $events
	 * @param int              $day
	 * @return string
	 */
	private function day_events_html(Default_Calendar $calendar, array $events, $day)
	{
		$limit = (int) $calendar->events_limit;
		$show_time = empty($calendar->hide_grid_event_time);
		$html = '<ul class="simcal-events simcal-retro-events">';
		$index = 0;
		$bullet_colors = [];
		$bullet_labels = [];

		foreach ($events as $event) {
			if (!($event instanceof Event)) {
				continue;
			}

			// Same marker classic grid uses for the narrow/widget dots.
			$event_color = $event->get_color();
			$bullet_colors[] = !empty($event_color) ? $event_color : '#000';
			$extra = $limit > -1 && $index >= $limit;
			$style = Retro_Event::surface_style($event, 'grid');
			$classes = 'simcal-retro-event simcal-event simcal-tooltip';
			if (!$show_time) {
				$classes .= ' simcal-retro-event-no-time';
			}
			if ($extra) {
				$classes .= ' simcal-event-toggled';
				$style .= 'display:none;';
			}

			$title = !empty($event->title) ? trim($event->title) : __('Event', 'google-calendar-events');
			if ($calendar->trim_titles >= 1 && mb_strlen($title) > $calendar->trim_titles) {
				$title = mb_substr($title, 0, $calendar->trim_titles) . '…';
			}
			$bullet_labels[] = $title;

			$html .= '<li class="' . esc_attr($classes) . '" style="' . esc_attr($style) . '">';
			$html .= '<span class="simcal-retro-card">';
			if ($show_time) {
				$html .= Retro_Event::time_html($calendar, $event);
			}
			$html .= '<span class="simcal-event-title simcal-retro-title">' . esc_html($title) . '</span>';
			$html .= '</span>';
			$html .=
				'<div class="simcal-event-details simcal-tooltip-content" style="display: none;">' .
				$calendar->get_event_html($event) .
				'</div>';
			$html .= '</li>';
			$index++;
		}

		$html .= '</ul>';

		if ($limit > -1 && $index > $limit) {
			$html .=
				'<button class="simcal-events-toggle" type="button" aria-expanded="false" aria-label="' .
				esc_attr__('Show more events', 'google-calendar-events') .
				'"><i class="simcal-icon-down simcal-icon-animate" aria-hidden="true"></i></button>';
		}

		$html .= '<span class="simcal-events-dots" style="display: none;">';
		foreach ($bullet_colors as $dot_index => $bullet_color) {
			$dot_label = isset($bullet_labels[$dot_index])
				? $bullet_labels[$dot_index]
				: __('Event', 'google-calendar-events');
			$html .=
				'<span class="simcal-event-color simcal-event-color-dot" role="button" tabindex="0" aria-label="' .
				esc_attr($dot_label) .
				'" style="background-color: ' .
				esc_attr($bullet_color) .
				';"></span>';
		}
		$html .= '</span>';

		return $html;
	}

	/**
	 * Sort events by start, then calendar priority, then title.
	 *
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
	 * Ajax callback to request a new month.
	 */
	public function draw_grid_ajax()
	{
		if (!isset($_POST['month']) || !isset($_POST['year']) || !isset($_POST['id'])) {
			wp_send_json_error('Missing arguments in default calendar grid ajax request.');
		}

		$month = absint($_POST['month']);
		$year = absint($_POST['year']);
		$id = absint($_POST['id']);
		$post = get_post($id);

		if (!$post || 'calendar' !== $post->post_type) {
			wp_send_json_error('Calendar not found.');
		}

		if ('publish' !== $post->post_status && !current_user_can('read_post', $id)) {
			wp_send_json_error('You do not have permission to view this calendar.');
		}

		wp_send_json_success($this->draw_month($month, $year, $id));
	}

	/**
	 * @param int $event Timestamp.
	 * @return bool
	 */
	private function filter_events_before($event)
	{
		return intval($event) >= intval($this->start);
	}

	/**
	 * @param int $event Timestamp.
	 * @return bool
	 */
	private function filter_events_after($event)
	{
		return intval($event) < intval($this->end);
	}
}
