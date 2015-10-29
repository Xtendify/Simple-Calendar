<?php
/**
 * Feeds Settings Page
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Pages;

use SimpleCalendar\Abstracts\Feed;
use SimpleCalendar\Abstracts\Admin_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feeds settings.
 *
 * Handles calendar feeds settings and outputs the settings page markup.
 *
 * @since 3.0.0
 */
class Feeds extends Admin_Page {

	/**
	 * Feed types.
	 *
	 * @access private
	 * @var array
	 */
	private $feed_types = array();

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->id           = 'feeds';
		$this->option_group = 'settings';
		$this->label        = __( 'Event Sources', 'google-calendar-events' );
		//$this->description  = __( 'Manage calendar event sources settings.', 'google-calendar-events' );

		$feeds_settings = array();
		$feeds = simcal_get_feed_types();
		if ( ! empty( $feeds ) && is_array( $feeds ) ) {
			foreach ( $feeds as $feed ) {

				$feed_type = simcal_get_feed( $feed );

				if ( $feed_type instanceof Feed ) {
					$settings = $feed_type->settings_fields();
					if ( ! empty( $settings ) ) {
						$feeds_settings[ $feed ] = $settings;
					}
				}
			}
		}

		$this->feed_types = $feeds_settings;
		$this->sections   = $this->add_sections();
		$this->fields     = $this->add_fields();
	}

	/**
	 * Add sections.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_sections() {

		$sections = array();

		foreach ( $this->feed_types as $feed_type => $type ) {

			$sections[ $feed_type ] = array(
				'title'       => $type['name'],
				'description' => $type['description'],
			);

		}

		arsort( $sections );

		return apply_filters( 'simcal_add_' . $this->option_group . '_' . $this->id .'_sections', $sections );
	}

	/**
	 * Add fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_fields() {

		$fields       = array();
		$feed_types   = $this->feed_types;
		$this->values = get_option( 'simple-calendar_' . $this->option_group . '_' . $this->id );

		foreach ( $this->sections as $type => $contents ) :

			if ( isset( $feed_types[ $type ]['fields'] ) ) {
				foreach ( $feed_types[ $type ]['fields'] as $key => $args ) {

					$fields[ $type ][] = array_merge( $args, array(
						'name'  => 'simple-calendar_' . $this->option_group . '_' . $this->id . '[' . $type . '][' . $key . ']',
						'id'    => 'simple-calendar-' . $this->option_group . '-' . $this->id . '-' . $type . '-' . $key,
						'value' => $this->get_option_value( $type, $key )
					) );

				}
			}

		endforeach;

		return apply_filters( 'simcal_add_' . $this->option_group . '_' . $this->id . '_fields', $fields );
	}

}
