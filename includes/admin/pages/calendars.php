<?php
/**
 * Calendar Settings Page
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Pages;

use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Admin_Page;
use SimpleCalendar\Feeds\Admin\Sc_Event_Admin;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Calendar settings.
 *
 * Handles settings for specific calendar types and outputs the markup the settings page. a settings page.
 *
 * @since 3.0.0
 */
class Calendars extends Admin_Page
{
	/**
	 * Calendar Types.
	 *
	 * @access private
	 * @var array
	 */
	private $calendar_types = [];

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		$this->id = 'calendars';
		$this->option_group = 'settings';
		$this->label = __('Calendars', 'google-calendar-events');
		//$this->description      = __( 'Manage calendar preferences and calendar types settings and options.', 'google-calendar-events' );

		$calendars = simcal_get_calendar_types();
		$calendar_settings = [];
		if (!empty($calendars) && is_array($calendars)) {
			foreach ($calendars as $calendar => $views) {
				$calendar_type = simcal_get_calendar($calendar);

				if ($calendar_type instanceof Calendar) {
					$settings = $calendar_type->settings_fields();
					if (!empty($settings)) {
						$calendar_settings[$calendar] = $settings;
					}
				}
			}
		}

		$this->calendar_types = $calendar_settings;
		$this->sections = $this->add_sections();
		$this->fields = $this->add_fields();
	}

	/**
	 * Add sections.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_sections()
	{
		$sections = [
			'general' => [
				'title' => __('General', 'google-calendar-events'),
				'description' => '',
			],
		];

		$calendar_types = $this->calendar_types;

		if (!empty($calendar_types) && is_array($calendar_types)) {
			foreach ($calendar_types as $calendar_type => $type) {
				$sections[$calendar_type] = [
					'title' => $type['name'],
					'description' => $type['description'],
				];
			}
		}

		arsort($calendar_types);

		return apply_filters('simcal_add_' . $this->option_group . '_' . $this->id . '_sections', $sections);
	}

	/**
	 * Add fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_fields()
	{
		$fields = [];
		$feed_types = $this->calendar_types;
		$this->values = get_option('simple-calendar_' . $this->option_group . '_' . $this->id);

		foreach ($this->sections as $section => $contents):
			if ('general' == $section) {
				$options = [];
				$post_types = get_post_types(
					[
						'public' => false,
						'publicly_queriable' => false,
						'show_ui' => false,
					],
					'objects',
					'not',
				);
				unset($post_types['attachment']);
				unset($post_types['calendar']);
				unset($post_types['gce_feed']);
				foreach ($post_types as $slug => $post_type) {
					$options[$slug] = $post_type->label;
				}

				// Post types hidden from the admin (e.g. SC Event when "Hide Events" is ticked) drop out of
				// the list above. Keep any already-saved selection in the list, otherwise saving this form
				// would silently remove it from attach_calendars_posts.
				$saved_post_types = $this->get_option_value($section, 'attach_calendars_posts');
				$saved_post_types = is_array($saved_post_types) ? $saved_post_types : explode(',', (string) $saved_post_types);
				foreach ($saved_post_types as $saved_slug) {
					$saved_slug = sanitize_key(trim((string) $saved_slug));
					if ('' === $saved_slug || isset($options[$saved_slug])) {
						continue;
					}
					$saved_post_type = get_post_type_object($saved_slug);
					if ($saved_post_type) {
						$options[$saved_slug] = $saved_post_type->label;
					}
				}

				asort($options);

				$fields[$section][] = [
					'type' => 'select',
					'multiselect' => 'multiselect',
					'enhanced' => 'enhanced',
					'title' => __('Attach Calendars', 'google-calendar-events'),
					'tooltip' => __(
						'You can choose on which content types to add the ability to attach calendars.',
						'google-calendar-events',
					),
					'name' =>
						'simple-calendar_' . $this->option_group . '_' . $this->id . '[' . $section . '][attach_calendars_posts]',
					'id' => 'simple-calendar-' . $this->option_group . '-' . $this->id . '-attach-calendars-posts',
					'value' => $this->get_option_value($section, 'attach_calendars_posts'),
					'default' => 'post,page',
					'options' => $options,
				];

				$hide_events_value = $this->get_option_value($section, 'hide_sc_event_post_type');

				// The setting cannot be turned on while a calendar uses the SC Event source. Render the
				// checkbox natively disabled and print the reason next to it; the save is validated
				// server side as well (see validate()).
				$hide_events_locked = 'yes' !== $hide_events_value && simcal_has_calendars_using_feed_type('sc-event');

				$fields[$section][] = [
					'type' => 'checkbox',
					'title' => __('Hide Events', 'google-calendar-events'),
					'attributes' => $hide_events_locked
						? [
							'disabled' => 'disabled',
							'data-sc-locked' => 'yes',
						]
						: [],
					'description' => $hide_events_locked
						? __(
							'Events cannot be hidden while one or more calendars still use the SC Event source. Change those calendars to another event source first.',
							'google-calendar-events',
						)
						: '',
					'tooltip' => __(
						'If ticked, the Events post type is hidden from the admin: its menu item is removed and "SC Event" is no longer offered as a calendar event source. Existing events stay in the database and their pages remain publicly accessible. This cannot be ticked while a calendar still uses the SC Event source.',
						'google-calendar-events',
					),
					'name' =>
						'simple-calendar_' . $this->option_group . '_' . $this->id . '[' . $section . '][hide_sc_event_post_type]',
					'id' => 'simple-calendar-' . $this->option_group . '-' . $this->id . '-hide-sc-event-post-type',
					'value' => $hide_events_value,
				];
			} elseif (isset($feed_types[$section]['fields'])) {
				foreach ($feed_types[$section]['fields'] as $key => $args) {
					$fields[$section][] = array_merge($args, [
						'name' => 'simple-calendar_' . $this->option_group . '_' . $this->id . '[' . $section . '][' . $key . ']',
						'id' => 'simple-calendar-' . $this->option_group . '-' . $this->id . '-' . $key,
						'value' => $this->get_option_value($section, $key),
					]);
				}
			}
		endforeach;

		return apply_filters('simcal_add_' . $this->option_group . '_' . $this->id . '_fields', $fields);
	}

	/**
	 * Register setting callback.
	 *
	 * @since  4.3.3
	 *
	 * @param  array $settings Settings inputs.
	 *
	 * @return array Sanitized settings.
	 */
	public function validate($settings)
	{
		// Remember whether this request actually submitted the setting: a partial
		// update hands the whole stored option back, which must not be re-validated.
		$submitted = is_array($settings) && isset($settings['general']['hide_sc_event_post_type']);

		$settings = parent::validate($settings);

		if ($submitted && is_array($settings) && isset($settings['general'])) {
			$settings['general'] = Sc_Event_Admin::validate_settings($settings['general']);
		}

		return $settings;
	}
}
