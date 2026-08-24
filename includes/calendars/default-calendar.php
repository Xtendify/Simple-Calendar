<?php
/**
 * Default Calendar
 *
 * @package SimpleCalendar\Calendars
 */
namespace SimpleCalendar\Calendars;

use SimpleCalendar\plugin_deps\Carbon\Carbon;
use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Calendar_View;
use SimpleCalendar\Calendars\Admin\Default_Calendar_Admin;
use SimpleCalendar\Calendars\Views;
use SimpleCalendar\Events\Event;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Default Calendar.
 *
 * The default calendar view bundled with the plugin.
 *
 * @since 3.0.0
 */
class Default_Calendar extends Calendar
{
	/**
	 * Limit visibility of daily events.
	 *
	 * @access public
	 * @var int
	 */
	public $events_limit = -1;

	/**
	 * Trim characters event titles in grid.
	 *
	 * @access public
	 * @var int
	 */
	public $trim_titles = -1;

	/**
	 * Event bubbles action trigger.
	 *
	 * @access public
	 * @var string
	 */
	public $event_bubble_trigger = 'click';

	/**
	 * Hide navigation buttons.
	 *
	 * @access public
	 * @var bool
	 */
	public $compact_list = false;

	/**
	 * Grouped list type.
	 *
	 * @access public
	 * @var string
	 */
	public $group_type = '';

	/**
	 * Grouped list span.
	 *
	 * @access public
	 * @var int
	 */
	public $group_span = 1;

	/**
	 * Skin theme.
	 *
	 * @access public
	 * @var string
	 */
	public $theme = 'light';

	/**
	 * Today color.
	 *
	 * @access public
	 * @var string
	 */
	public $today_color = '#FF0000';

	/**
	 * Days with events color.
	 *
	 * @access public
	 * @var string
	 */
	public $days_events_color = '#000000';

	/**
	 * Show grid on desktop and list on mobile.
	 *
	 * @access public
	 * @var bool
	 */
	public $grid_desktop_list_mobile = false;

	/**
	 * Whether multi-day events have already been expanded.
	 *
	 * @access private
	 * @var bool
	 */
	private $events_expanded = false;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param int|object|\WP_Post|Calendar $calendar
	 */
	public function __construct($calendar)
	{
		$this->type = 'default-calendar';
		$this->name = __('Default', 'google-calendar-events');
		$this->views = apply_filters('simcal_default_calendar_views', [
			'grid' => __('Grid', 'google-calendar-events'),
			'list' => __('List', 'google-calendar-events'),
		]);

		parent::__construct($calendar);

		if (!is_null($this->post)) {
			$this->grid_desktop_list_mobile =
				'yes' === get_post_meta($this->id, '_default_calendar_grid_desktop_list_mobile', true);

			$this->set_properties($this->view->get_type());

			$id = $this->id;
			$theme = $this->theme;

			add_filter(
				'simcal_calendar_class',
				function ($class, $post_id) use ($theme, $id) {
					if (in_array('default-calendar', $class) && $post_id === $id) {
						array_push($class, 'default-calendar-' . $theme);
					}

					return $class;
				},
				10,
				2,
			);
		}

		// Calendar settings handling.
		if (is_admin() && !defined('DOING_AJAX')) {
			$admin = new Default_Calendar_Admin();
			$this->settings = $admin->settings_fields();
		}
	}

	/**
	 * Output the calendar markup.
	 *
	 * When grid-desktop/list-mobile is enabled, both views are rendered as
	 * siblings and toggled via CSS at the mobile breakpoint. Only one view
	 * is visible at a time; JS defers initializing the hidden sibling.
	 *
	 * @since 4.2.0
	 *
	 * @param string $view The calendar view to display.
	 */
	public function html($view = '')
	{
		$requested = $view;
		$view = empty($view) ? $this->view : $this->get_view($view);

		$load_grid_view_only =
			!($view instanceof Calendar_View) ||
			!$this->grid_desktop_list_mobile ||
			'grid' !== $view->get_type() ||
			!empty($this->errors);
		if ($load_grid_view_only) {
			parent::html($requested);
			return;
		}

		echo '<div class="simcal-responsive-views">';

		do_action('simcal_calendar_html_before', $this->id);

		// Grid (desktop) — properties already set for grid in constructor.
		$this->render_view_shell($view, false, false);

		// List (mobile) — apply list properties once, then render.
		$this->set_properties('list');
		$list_view = $this->get_view('list');
		$this->render_view_shell($list_view, false, false);

		do_action('simcal_calendar_html_after', $this->id);

		$this->render_powered_by();
		$this->render_empty_events_notice();

		echo '</div>';
	}

	/**
	 * Set properties.
	 *
	 * @since  3.0.0
	 * @access protected
	 *
	 * @param  $view
	 */
	protected function set_properties($view)
	{
		// Set styles.
		if ('dark' == get_post_meta($this->id, '_default_calendar_style_theme', true)) {
			$this->theme = 'dark';
		}
		if ($today_color = get_post_meta($this->id, '_default_calendar_style_today', true)) {
			$this->today_color = esc_attr($today_color);
		}
		if ($day_events_color = get_post_meta($this->id, '_default_calendar_style_days_events', true)) {
			$this->days_events_color = esc_attr($day_events_color);
		}

		// Hide too many events.
		if ('yes' == get_post_meta($this->id, '_default_calendar_limit_visible_events', true)) {
			$this->events_limit = absint(get_post_meta($this->id, '_default_calendar_visible_events', true));
		}

		// List settings are needed for list view and for grid+list-on-mobile AJAX navigation.
		$needs_list_props = 'list' === $view || $this->grid_desktop_list_mobile;

		// Expand multiple day events (once only when dual-rendering).
		// current_day_only is list-view-only; do not expand during the grid pass.
		if (
			!$this->events_expanded &&
			('yes' == get_post_meta($this->id, '_default_calendar_expand_multi_day_events', true) ||
				('list' === $view &&
					'current_day_only' == get_post_meta($this->id, '_default_calendar_expand_multi_day_events', true)))
		) {
			$this->events = $this->expand_multiple_days_events();
			$this->events_expanded = true;
		}

		if ('grid' == $view) {
			// Use hover to open event bubbles.
			if ('hover' == get_post_meta($this->id, '_default_calendar_event_bubble_trigger', true)) {
				$this->event_bubble_trigger = 'hover';
			}

			// Trim long event titles.
			if ('yes' == get_post_meta($this->id, '_default_calendar_trim_titles', true)) {
				$this->trim_titles = max(absint(get_post_meta($this->id, '_default_calendar_trim_titles_chars', true)), 1);
			}
		}

		if ($needs_list_props) {
			// List range (default monthly when unset — required for list prev/next timestamps).
			$list_type = get_post_meta($this->id, '_default_calendar_list_range_type', true);
			$this->group_type = $list_type ? esc_attr($list_type) : 'monthly';
			$this->group_span = max(absint(get_post_meta($this->id, '_default_calendar_list_range_span', true)), 1);

			// Make the list look more compact.
			if ('yes' == get_post_meta($this->id, '_default_calendar_compact_list', true)) {
				$this->compact_list = true;
			}
		}
	}

	/**
	 * Expand multiple day events.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @return array
	 */
	private function expand_multiple_days_events()
	{
		$old_events = $this->events;
		$new_events = [];

		if (!empty($old_events)) {
			foreach ($old_events as $events) {
				foreach ($events as $event) {
					if ($event instanceof Event) {
						if (false !== $event->multiple_days) {
							$days = $event->multiple_days;

							if ($days > 0) {
								for ($d = 1; $d <= $days; $d++) {
									$current_day_ts = $event->start + ($d * DAY_IN_SECONDS - 1);
									$new_events[intval($current_day_ts)][] = $event;
								}
							}
						}
					}
				}
			}
		}

		$events = $old_events + $new_events;
		ksort($events, SORT_NUMERIC);

		return $events;
	}

	/**
	 * Get a view.
	 *
	 * Returns one of this calendar's views.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $view
	 *
	 * @return null|Calendar_View
	 */
	public function get_view($view = '')
	{
		$view = !empty($view) ? $view : 'grid';

		do_action('simcal_calendar_get_view', $this->type, $view);

		if ('grid' == $view) {
			return new Views\Default_Calendar_Grid($this);
		} elseif ('list' == $view) {
			return new Views\Default_Calendar_List($this);
		}

		return null;
	}
}
