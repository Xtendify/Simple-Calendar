<?php
/**
 * Shared functions
 *
 * Functions shared by both back end and front end components.
 *
 * @package SimpleCalendar/Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get events feed types.
 *
 * @return array
 */
function simcal_get_feed_types() {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_feed_types() : array();
}

/**
 * Get an events feed.
 *
 * @param  string|int|object $object
 *
 * @return null|false|\SimpleCalendar\Abstracts\Feed
 */
function simcal_get_feed( $object ) {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_feed( $object ) : null;
}

/**
 * Get calendar types.
 *
 * @return array
 */
function simcal_get_calendar_types() {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_calendar_types() : array();
}

/**
 * Get a calendar.
 *
 * @param  string|int|object|WP_Post $object
 *
 * @return null|\SimpleCalendar\Abstracts\Calendar
 */
function simcal_get_calendar( $object ) {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_calendar( $object ) : null;
}

/**
 * Get a calendar view.
 *
 * @param  int    $id
 * @param  string $name
 *
 * @return mixed
 */
function simcal_get_calendar_view( $id = 0, $name = '' ) {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_calendar_view( $id, $name ) : false;
}

/**
 * Print a calendar.
 *
 * @param  int|object|WP_Post $object
 *
 * @return void
 */
function simcal_print_calendar( $object ) {

	$calendar = simcal_get_calendar( $object );

	if ( $calendar instanceof \SimpleCalendar\Abstracts\Calendar ) {
		$calendar->html();
	}
}

/**
 * Common scripts variables.
 *
 * Variables to print in scripts localization
 *
 * @return array
 */
function simcal_common_scripts_variables() {

	$vars = array(
		'ajax_url'  => \SimpleCalendar\plugin()->ajax_url(),
		'nonce'     => wp_create_nonce( 'simcal' ),
		'locale'    => \SimpleCalendar\plugin()->locale,
		'text_dir'  => is_rtl() ? 'rtl' : 'ltr',
		'months'    => array(
			'full'  => simcal_get_calendar_names_i18n( 'month', 'full' ),
			'short' => simcal_get_calendar_names_i18n( 'month', 'short' ),
		),
		'days'      => array(
			'full'  => simcal_get_calendar_names_i18n( 'day', 'full' ),
			'short' => simcal_get_calendar_names_i18n( 'day', 'short' ),
		),
		'meridiem' => simcal_get_calendar_names_i18n( 'meridiem' ),
	);

	return array_merge( $vars, apply_filters( 'simcal_common_scripts_variables', array() ) );
}

/**
 * Get feed IDs and names.
 *
 * @param  string|int|array $exclude Id or array of ids to drop from results.
 * @param  bool $cached Use cached query.
 *
 * @return array Associative array with ids as keys and feed titles as values.
 */
function simcal_get_calendars( $exclude = '', $cached = true ) {

	$calendars = get_transient( '_simple-calendar_feed_ids' );

	if ( ! $calendars || $cached === false ) {

		$posts = get_posts( array(
			'post_type' => 'calendar',
			'nopaging'  => true
		) );

		$calendars = array();
		foreach ( $posts as $post ) {
			$calendars[ $post->ID ] = $post->post_title;
		}
		asort( $calendars );

		set_transient( '_simple-calendar_feed_ids', $calendars, 604800 );
	}

	if ( ! empty( $exclude ) ) {
		if ( is_numeric( $exclude ) ) {
			unset( $calendars[ intval( $exclude ) ] );
		} elseif ( is_array( $exclude ) ) {
			array_diff_key( $calendars, array_map( 'intval', array_keys( $exclude ) ) );
		}
	}

	return $calendars;
}

/**
 * Get localized list of months or day names.
 *
 * Each day or month matches the array index (0-11 for months or 0-6 for days).
 *
 * @param  string $group Either 'month', 'day' or 'meridiem' names to localize.
 * @param  string $style Return names in 'short' or 'full' form (default full long form).
 *
 * @return array
 */
function simcal_get_calendar_names_i18n( $group, $style = 'full' ) {

	$array = array();

	if ( in_array( $group, array( 'month', 'day', 'meridiem' ) ) ) {

		$format = '';
		$length = 0;

		$date = \Carbon\Carbon::now( 'UTC' );

		if ( 'month' == $group ) {
			$date->month( 0 )->startOfMonth();
			$format = 'short' == $style ? 'M' : 'F';
			$length = 11;
		} elseif ( 'day' == $group ) {
			$date->next( 6 );
			$format = 'short' == $style ? 'D' : 'l';
			$length = 6;
		} elseif ( 'meridiem' == $group ) {
			$date->startOfDay();
			$am = $date->addHour( 1 )->getTimestamp();
			$pm = $date->addHours( 13 )->getTimestamp();
			return array(
				'AM' => date_i18n( 'A', $am ),
				'am' => date_i18n( 'a', $am ),
				'PM' => date_i18n( 'A', $pm ),
				'pm' => date_i18n( 'a', $pm ),
			);
		}

		$i = 0;
		while ( $i <= $length ) {
			if ( 'month' == $group ) {
				$date->addMonths( 1 );
			} else {
				$date->addDays( 1 );
			}
			$array[ strval( $i ) ] = date_i18n( $format, $date->getTimestamp() );
			$i++;
		}

	}

	return $array;
}

/**
 * Default event template.
 *
 * @return string
 */
function simcal_default_event_template() {

	$content  = '<strong>' . '[title]' . '</strong>';
	$content .= '<p>';
	$content .= '[when]' . "\n";
	$content .= '[location]' . "\n";
	$content .= '[description]' . "\n";
	$content .= '[link newwindow="yes"]' . __( 'Click for more details.', 'google-calendar-events' ) . '[/link]';
	$content .= '</p>';

	return apply_filters( 'simcal_default_event_template', $content );
}

/**
 * Get day, month, year order in a datetime format string.
 *
 * Returns an array with d, m, y for keys and a order number.
 * If either d, m, y is not found in date format, order value is false.
 *
 * @param  string $date_format
 *
 * @return array
 */
function simcal_get_date_format_order( $date_format ) {

	$pos = array(
		'd' => strpos( $date_format, strpbrk( $date_format, 'Dj' ) ),
		'm' => strpos( $date_format, strpbrk( $date_format, 'FMmn' ) ),
		'y' => strpos( $date_format, strpbrk( $date_format, 'Yy' ) ),
	);

	// @todo When one date piece is not found, perhaps fallback to ISO standard position.

	$order = array();
	foreach ( $pos as $k => $v ) {
		$order[ $k ] = $v;
	}
	ksort( $order );

	return $order;
}

/**
 * Get WordPress timezone setting.
 *
 * Always returns a valid timezone string even when the setting is a GMT offset.
 *
 * @return null|string
 */
function simcal_get_wp_timezone() {

	$timezone = get_option( 'timezone_string' );

	if ( empty( $timezone ) ) {
		$gmt = get_option( 'gmt_offset' );
		$timezone = simcal_get_timezone_from_gmt_offset( $gmt );
	}

	return $timezone;
}

/**
 * Get a timezone from a GMT offset.
 *
 * Converts a numeric offset into a valid timezone string.
 *
 * @param  string|float $offset
 *
 * @return null|string
 */
function simcal_get_timezone_from_gmt_offset( $offset ) {

	if ( is_numeric( $offset ) ) {

		if ( 0 === intval( $offset ) ) {
			return 'UTC';
		} else {
			$offset = floatval( $offset ) * 3600;
		}

		$timezone = timezone_name_from_abbr( null, $offset, true );
		// This is buggy and might return false:
		// @see http://php.net/manual/en/function.timezone-name-from-abbr.php#86928
		// Therefore:
		if ( false == $timezone ) {

			$list = timezone_abbreviations_list();
			foreach ( $list as $abbr ) {
				foreach ( $abbr as $city ) {
					if ( $offset == $city['offset'] ) {
						return $city['timezone_id'];
					}
				}
			}

		}

		return $timezone;
	}

	return null;
}

/**
 * Convert a timezone string to a numeric offset.
 *
 * @param  $timezone
 *
 * @return int Unix time offset
 */
function simcal_get_timezone_offset( $timezone ) {
	return \Carbon\Carbon::now( $timezone )->offset;
}

/**
 * Escape timezone string.
 *
 * @param  string $tz
 * @param  mixed  $default
 *
 * @return mixed|string
 */
function simcal_esc_timezone( $tz, $default = 'UTC' ) {
	return in_array( $tz, timezone_identifiers_list() ) ? $tz : $default;
}
