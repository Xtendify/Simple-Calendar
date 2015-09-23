<?php
/**
 * Deprecated functions
 *
 * Functions that are kept for backward support but should not be used anymore.
 *
 * @package SimpleCalendar/Deprecated
 * @deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print a calendar.
 * @deprecated Use simcal_print_calendar()
 */
function gce_print_calendar( $feed_ids, $display, $args  ) {

	$id = 0;

	if ( is_numeric( $feed_ids ) ) {
		$id = intval( $feed_ids );
	} elseif ( is_array( $feed_ids ) ) {
		$id = isset( $feed_ids[0] ) ? intval( $feed_ids[0] ) : '';
	}

	if ( $id > 0 ) {
		simcal_print_calendar( $id );
	}
}

/**
 * Convert date from mm/dd/yyyy to unix timestamp.
 * @deprecated Assumes a US-only time format.
 */
function gce_date_unix( $date = '' ) {
	if ( empty( $date ) ) {
		$current_time = current_time( 'timestamp' );
		$timestamp = mktime( 0, 0, 0, date( 'm', $current_time ), date( 'd', $current_time ), date( 'Y', $current_time ) );
	} else {
		$date = explode( '/', $date );
		$month = $date[0];
		$day   = $date[1];
		$year  = $date[2];
		$timestamp = mktime( 0, 0, 0, $month, $day, $year );
	}
	return $timestamp;
}
