<?php
/**
 * Settings Page
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Abstracts;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * The Admin Page.
 *
 * @since 3.0.0
 */
abstract class Admin_Page
{
	/**
	 * Admin page ID.
	 *
	 * @access public
	 * @var string
	 */
	public $id = '';

	/**
	 * Option group.
	 *
	 * @access public
	 * @var string
	 */
	public $option_group = '';

	/**
	 * Admin Page label.
	 *
	 * @access public
	 * @var string
	 */
	public $label = '';

	/**
	 * Admin Page description.
	 *
	 * @access public
	 * @var string
	 */
	public $description = '';

	/**
	 * Amdin Page settings sections.
	 *
	 * @access public
	 * @var array Associative array with section id (key) and section name (value)
	 */
	public $sections;

	/**
	 * Admin Page settings fields.
	 *
	 * @access public
	 * @var array
	 */
	public $fields;

	/**
	 * Saved values.
	 *
	 * @access protected
	 * @var array
	 */
	protected $values = [];

	/**
	 * Get admin page settings.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function get_settings()
	{
		$settings = [];

		$settings[$this->id] = [
			'label' => $this->label,
			'description' => $this->description,
		];

		if (!empty($this->sections) && is_array($this->sections)) {
			foreach ($this->sections as $section => $content) {
				$settings[$this->id]['sections'][$section] = [
					'title' => isset($content['title']) ? $content['title'] : '',
					'description' => isset($content['description']) ? $content['description'] : '',
					'callback' => [$this, 'add_settings_section_callback'],
					'fields' => isset($this->fields[$section]) ? $this->fields[$section] : '',
				];
			}
		}

		return apply_filters('simcal_get_' . $this->option_group . '_' . $this->id, $settings);
	}

	/**
	 * Get option value.
	 *
	 * @since  3.0.0
	 * @access protected
	 *
	 * @param  string $section
	 * @param  string $setting
	 *
	 * @return string
	 */
	protected function get_option_value($section, $setting)
	{
		$option = $this->values;

		if (!empty($option) && is_array($option)) {
			return isset($option[$section][$setting]) ? $option[$section][$setting] : '';
		}

		return '';
	}

	/**
	 * Add sections for this page.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	abstract public function add_sections();

	/**
	 * Get settings fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	abstract public function add_fields();

	/**
	 * Default basic callback for page sections.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $section
	 *
	 * @return string
	 */
	public function add_settings_section_callback($section)
	{
		$callback = isset($section['callback'][0]) ? $section['callback'][0] : '';
		$sections = isset($callback->sections) ? $callback->sections : '';
		$description = isset($sections[$section['id']]['description']) ? $sections[$section['id']]['description'] : '';
		$default = $description ? '<p>' . $description . '</p>' : '';

		echo apply_filters('simcal_' . $this->option_group . '_' . $this->id . '_sections_callback', $default);
	}

	/**
	 * Register setting callback.
	 *
	 * Callback function for sanitizing and validating options before they are updated.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $settings Settings inputs.
	 *
	 * @return array Sanitized settings.
	 */
	public function validate($settings)
	{
		$sanitized = [];

		if (is_array($settings)) {
			foreach ($settings as $k => $v) {
				$sanitized[$k] = simcal_sanitize_input($v);
			}
		} else {
			$sanitized = simcal_sanitize_input($settings);
		}

		// Allow partial updates for Connect page forms that only submit one section.
		// Without this, posting a subset of keys would overwrite the whole option.
		if (is_array($sanitized) && isset($sanitized['__sc_partial_update'])) {
			$option_name = 'simple-calendar_' . $this->option_group . '_' . $this->id;
			$existing = get_option($option_name, []);
			$existing = is_array($existing) ? $existing : [];

			$sections_map =
				isset($sanitized['__sc_partial_sections']) && is_array($sanitized['__sc_partial_sections'])
					? $sanitized['__sc_partial_sections']
					: [];
			$had_partial_section_keys = !empty($sections_map);

			$allowed_section_ids = [];
			if (!empty($this->sections) && is_array($this->sections)) {
				foreach (array_keys($this->sections) as $section_id) {
					if (is_string($section_id) && $section_id !== '') {
						$allowed_section_ids[$section_id] = true;
					}
				}
			}

			$filtered_sections_map = [];
			foreach ($sections_map as $map_key => $map_value) {
				if (!is_string($map_key) || $map_key === '') {
					continue;
				}
				if (!isset($allowed_section_ids[$map_key])) {
					continue;
				}
				$filtered_sections_map[$map_key] = $map_value;
			}
			$sections = array_keys($filtered_sections_map);

			unset($sanitized['__sc_partial_update'], $sanitized['__sc_partial_sections']);

			if (!empty($sections)) {
				foreach ($sections as $section) {
					if (!is_string($section) || $section === '') {
						continue;
					}
					if (!isset($sanitized[$section])) {
						continue;
					}
					$existing_section = isset($existing[$section]) && is_array($existing[$section]) ? $existing[$section] : [];
					$new_section = is_array($sanitized[$section]) ? $sanitized[$section] : [];
					$existing[$section] = array_replace_recursive($existing_section, $new_section);
				}
				return $existing;
			}

			// Client sent partial section names but none match declared sections — do not merge arbitrary POST keys.
			if ($had_partial_section_keys) {
				return $existing;
			}

			// If no sections declared, fall back to merging everything we got.
			return array_replace_recursive($existing, $sanitized);
		}

		return $sanitized;
	}
}
