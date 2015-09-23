<?php
/**
 * Settings Page
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Abstracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Settings Page.
 *
 * Handles a collection of settings fields and a settings page markup.
 */
abstract class Settings_Page {

	/**
	 * Settings page ID.
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
	 * Settings Page label.
	 *
	 * @access public
	 * @var string
	 */
	public $label = '';

	/**
	 * Settings Page description.
	 *
	 * @access public
	 * @var string
	 */
	public $description = '';

	/**
	 * Settings Page sections.
	 *
	 * @access public
	 * @var array Associative array with section id (key) and section name (value)
	 */
	public $sections;

	/**
	 * Settings Page fields.
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
	protected $values = array();

	/**
	 * Get page settings.
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = array();

		$settings[ $this->id ] = array(
			'label'         => $this->label,
			'description'   => $this->description
		);

		if ( ! empty( $this->sections ) && is_array( $this->sections ) ) {

			foreach ( $this->sections as $section => $content ) {

				$settings[ $this->id ]['sections'][ $section ] = array(
					'title'         => isset( $content['title'] ) ? $content['title'] : '',
					'description'   => isset( $content['description'] ) ? $content['description'] : '',
					'callback'      => array( $this, 'add_settings_section_callback' ),
					'fields'        => isset( $this->fields[ $section ] ) ? $this->fields[ $section ] : ''
				);

			}

		}

		return apply_filters( 'simcal_get_' . $this->option_group . '_' . $this->id , $settings );
	}

	/**
	 * Get option value.
	 *
	 * @access protected
	 *
	 * @param  string $section
	 * @param  string $setting
	 *
	 * @return string
	 */
	protected function get_option_value( $section, $setting ) {

		$option = $this->values;

		if ( ! empty( $option ) && is_array( $option ) ) {
			return isset( $option[ $section ][ $setting ] ) ? $option[ $section ][ $setting ] : '';
		}

		return '';
	}

	/**
	 * Add sections for this page.
	 *
	 * @return array
	 */
	abstract public function add_sections();

	/**
	 * Get settings fields.
	 *
	 * @return array
	 */
	abstract public function add_fields();

	/**
	 * Default basic callback for page sections.
	 *
	 * @param  array $section
	 * @return string
	 */
	public function add_settings_section_callback( $section ) {

		$callback    = isset( $section['callback'][0] ) ? $section['callback'][0] : '';
		$sections    = isset( $callback->sections ) ? $callback->sections : '';
		$description = isset( $sections[ $section['id'] ]['description'] ) ? $sections[ $section['id'] ]['description'] : '';
		$default     = $description ? '<p>' . $description . '</p>' : '';

		echo apply_filters( 'simcal_' . $this->option_group . '_' . $this->id . '_sections_callback', $default );
	}

	/**
	 * Register setting callback.
	 *
	 * Callback function for sanitizing and validating options before they are updated.
	 *
	 * @param  array $settings Settings inputs.
	 * @return array Sanitized settings.
	 */
	public function validate( $settings ) {

		$sanitized = '';

		if ( is_array( $settings ) ) {
			foreach ( $settings as $k => $v ) {
				$sanitized[ $k ] = simcal_sanitize_input( $v );
			}
		} else {
			$sanitized = simcal_sanitize_input( $settings );
		}

		return $sanitized;
	}

}
