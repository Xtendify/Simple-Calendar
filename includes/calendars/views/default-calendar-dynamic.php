<?php
/**
 * Default Calendar - Dynamic View
 *
 * @package SimpleCalendar/Calendars
 */
namespace SimpleCalendar\Calendars\Views;

use SimpleCalendar\Abstracts\Calendar_View;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Default Calendar: Dynamic view.
 *
 * Loads the assets for every view a visitor can switch to. The switcher and
 * each view's markup are printed by Default_Calendar.
 */
class Default_Calendar_Dynamic implements Calendar_View
{
	/**
	 * Calendar.
	 *
	 * @access public
	 * @var Default_Calendar|string
	 */
	public $calendar = null;

	/**
	 * Constructor.
	 *
	 * @since 4.3.3
	 *
	 * @param string|Default_Calendar $calendar
	 */
	public function __construct($calendar = '')
	{
		$this->calendar = $calendar;
	}

	/**
	 * Get the view parent calendar type.
	 *
	 * @since  4.3.3
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
	 * @since  4.3.3
	 *
	 * @return string
	 */
	public function get_type()
	{
		return 'dynamic';
	}

	/**
	 * Get the view name.
	 *
	 * @since  4.3.3
	 *
	 * @return string
	 */
	public function get_name()
	{
		return __('Dynamic', 'google-calendar-events');
	}

	/**
	 * Ajax navigation is registered by the grid and list views.
	 *
	 * @since 4.3.3
	 */
	public function add_ajax_actions() {}

	/**
	 * Scripts to load when this view is displayed.
	 *
	 * Grid registers qTip, which both grid views use. Every view shares the
	 * default calendar script.
	 *
	 * @since  4.3.3
	 *
	 * @return array
	 */
	public function scripts()
	{
		return (new Default_Calendar_Grid($this->calendar))->scripts();
	}

	/**
	 * Styles to load when this view is displayed.
	 *
	 * @since  4.3.3
	 *
	 * @return array
	 */
	public function styles()
	{
		$styles = [];
		$siblings = [
			new Default_Calendar_Grid($this->calendar),
			new Default_Calendar_Grid_Retro($this->calendar),
			new Default_Calendar_List($this->calendar),
			new Default_Calendar_List_Retro($this->calendar),
		];

		foreach ($siblings as $sibling) {
			$styles = array_merge($styles, $sibling->styles());
		}

		return $styles;
	}

	/**
	 * Markup is printed by Default_Calendar::html_dynamic().
	 *
	 * @since 4.3.3
	 */
	public function html() {}
}
