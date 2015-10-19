<?php
/**
 * Objects Factory
 *
 * @package SimpleCalendar
 */
namespace SimpleCalendar;

use SimpleCalendar\Abstracts as Object;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Objects factory.
 *
 * Helper class to get the right type of object used across the plugin.
 *
 * @since 3.0.0
 */
class Objects {

	/**
	 * Constructor.
	 *
	 * Add default objects.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		// Add default feed type.
		add_filter( 'simcal_get_feed_types', function( $feed_types ) {
			return array_merge( $feed_types, array(
				'google',
				'grouped-calendars',
			) );
		}, 10, 1 );

		// Add default calendar type.
		add_filter( 'simcal_get_calendar_types', function( $calendar_types ) {
			return array_merge( $calendar_types, array(
				'default-calendar' => array(
					'grid',
					'list',
				),
			) );
		}, 10, 1 );

		// Add default admin objects.
		if ( $is_admin = is_admin() ) {
			add_filter( 'simcal_get_admin_pages', function( $admin_pages ) {
				return array_merge( $admin_pages, array(
					'add-ons' => array(
						'add-ons',
					),
					'settings' => array(
						'feeds',
						'calendars',
						'advanced',
					),
					'tools' => array(
						'system-status',
					),
				) );
			}, 10, 1 );
		}

		do_action( 'simcal_load_objects', $is_admin );
	}

	/**
	 * Get feed types.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function get_feed_types() {
		$array = apply_filters( 'simcal_get_feed_types', array() );
		ksort( $array );
		return $array;
	}

	/**
	 * Get calendar types.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function get_calendar_types() {
		$array = apply_filters( 'simcal_get_calendar_types', array() );
		ksort( $array );
		return $array;
	}

	/**
	 * Get admin pages.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function get_admin_pages() {
		return apply_filters( 'simcal_get_admin_pages', array() );
	}

	/**
	 * Get a calendar.
	 *
	 * Returns the right type of calendar.
	 *
	 * @since  3.0.0
	 *
	 * @param  int|string|object|\WP_Post|Object\Calendar $object
	 *
	 * @return null|Object\Calendar
	 */
	public function get_calendar( $object ) {

		if ( is_string( $object ) ) {
			return ! empty( $object ) ? $this->get_object( $object, 'calendar', '' ) : null;
		}

		if ( is_object( $object ) ) {
			if ( $object instanceof Object\Calendar ) {
				return $this->get_object( $object->type, 'feed', $object );
			} elseif ( $object instanceof \WP_Post ) {
				if ( $type = wp_get_object_terms( $object->ID, 'calendar_type' ) ) {
					$name = sanitize_title( current( $type )->name );
					return $this->get_object( $name, 'calendar', $object );
				}
			} elseif ( isset( $object->type ) && isset( $object->id ) ) {
				return $this->get_object( $object->type, 'calendar', $object->id );
			}
		}

		if ( is_int( $object ) ) {
			$post = get_post( $object );
			if ( $post && ( $type = wp_get_object_terms( $post->ID, 'calendar_type' ) ) ) {
				$name = sanitize_title( current( $type )->name );
				return $this->get_object( $name, 'calendar', $post );
			}
		}

		return null;
	}

	/**
	 * Get a calendar view.
	 *
	 * @since  3.0.0
	 *
	 * @param  int    $id   Feed post id.
	 * @param  string $name (optional) Name of calendar view.
	 *
	 * @return null|Object\Calendar_View
	 */
	public function get_calendar_view( $id = 0, $name = '' ) {

		if ( ! $name && $id > 0 ) {

			$calendar_view = get_post_meta( $id, '_calendar_view', true );

			if ( $terms = wp_get_object_terms( $id, 'calendar_type' ) ) {
				$calendar_type = sanitize_title( current( $terms )->name );
				$name = isset( $calendar_view[ $calendar_type ] ) ? $calendar_type . '-' . $calendar_view[ $calendar_type ] : '';
			}

		}

		return $name ? $this->get_object( $name, 'calendar-view', '' ) : null;
	}

	/**
	 * Get a feed.
	 *
	 * Returns the right type of feed.
	 *
	 * @since  3.0.0
	 *
	 * @param  int|string|object|\WP_Post|Object\Calendar $object
	 *
	 * @return null|Object\Feed
	 */
	public function get_feed( $object ) {

		if ( is_string( $object ) ) {
			return ! empty( $object ) ? $this->get_object( $object, 'feed', '' ) : null;
		}

		if ( is_object( $object ) ) {
			if ( $object instanceof Object\Calendar ) {
				$feed_name = '';
				if ( empty( $object->feed ) ) {
					if ( $feed_type = wp_get_object_terms( $object->id, 'feed_type' ) ) {
						$feed_name = sanitize_title( current( $feed_type )->name );
					}
				} else {
					$feed_name = $object->feed;
				}
				return $this->get_object( $feed_name, 'feed', $object );
			} elseif ( $object instanceof \WP_Post ) {
				$calendar = $this->get_calendar( $object );
				return $this->get_object( $calendar->feed, 'feed', $calendar );
			} elseif ( isset( $object->feed ) && isset( $object->id ) ) {
				return $this->get_object( $object->feed, 'feed', $object );
			}
		}

		if ( is_int( $object ) ) {
			$calendar = $this->get_calendar( $object );
			return isset( $calendar->feed ) ? $this->get_object( $calendar->feed, 'feed', $calendar ) : null;
		}

		return null;
	}

	/**
	 * Get a field.
	 *
	 * @since  3.0.0
	 *
	 * @param  array  $args Field args.
	 * @param  string $name Field type.
	 *
	 * @return null|Object\Field
	 */
	public function get_field( $args, $name = '' ) {

		if ( empty( $name ) ) {
			$name = isset( $args['type'] ) ? $args['type'] : false;
		}

		return $name ? $this->get_object( $name, 'field', $args ) : null;
	}

	/**
	 * Get a settings page.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $name
	 *
	 * @return null|Object\Admin_Page
	 */
	public function get_admin_page( $name ) {
		return $name ? $this->get_object( $name, 'admin-page' ) : null;
	}

	/**
	 * Get a plugin object.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  string $name Object name.
	 * @param  string $type Object type.
	 * @param  mixed  $args (optional) arguments for the class constructor.
	 *
	 * @return null|Object
	 */
	private function get_object( $name, $type, $args = '' ) {

		$types = array(
			'admin-page',
			'calendar',
			'calendar-view',
			'feed',
			'field',
		);

		if ( in_array( $type, $types ) ) {

			$class_name = $this->make_class_name( $name, $type );
			$parent     = '\\' . __NAMESPACE__ . '\Abstracts\\' . implode( '_', array_map( 'ucfirst', explode( '-', $type ) ) );
			$class      = class_exists( $class_name ) ? new $class_name( $args ) : false;

			return $class instanceof $parent ? $class : null;
		}

		return null;
	}

	/**
	 * Make class name from slug.
	 *
	 * Standardizes object naming and class names: <object-name> becomes <Class_Name>.
	 * The plugin autoloader uses a similar pattern.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  string $name Object name.
	 * @param  string $type Object type.
	 *
	 * @return string The class name complete with its full namespace.
	 */
	private function make_class_name( $name, $type ) {

		if ( 'calendar' == $type ) {
			$namespace = '\\' . __NAMESPACE__ . '\Calendars\\';
		} elseif ( 'calendar-view' == $type ) {
			$namespace = '\\' . __NAMESPACE__ . '\Calendars\Views\\';
		} elseif ( 'feed' == $type ) {
			$namespace = '\\' . __NAMESPACE__ . '\Feeds\\';
		} elseif ( 'field' == $type ) {
			$namespace = '\\' . __NAMESPACE__ . '\Admin\Fields\\';
		} elseif ( 'admin-page' == $type ) {
			$namespace = '\\' . __NAMESPACE__ . '\Admin\Pages\\';
		} else {
			return '';
		}

		$class_name = implode( '_', array_map( 'ucfirst', explode( '-', $name ) ) );

		return $namespace . $class_name;
	}

}
