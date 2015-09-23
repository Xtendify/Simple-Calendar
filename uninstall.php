<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package SimpleCalendar
 */

// Exit if not uninstalling from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Get user options whether to delete settings and/or data.
$settings = get_option( 'simple-calendar_settings_advanced' );

if ( isset( $settings['installation']['delete_settings'] ) ) {
	$delete_settings = 'yes' == $settings['installation']['delete_settings'] ? true : false;
} else {
	$delete_settings = false;
}

if ( isset( $settings['installation']['erase_data'] ) ) {
	$erase_data = 'yes' == $settings['installation']['erase_data'] ? true : false;
} else {
	$erase_data = false;
}

global $wpdb;

// Delete settings.
if ( ( $delete_settings === true ) || ( $erase_data === true ) ) {
	$wpdb->query(
		"
DELETE FROM $wpdb->options WHERE option_name LIKE '%simple-calendar%';
"
	);
}

// Delete calendar data.
if ( $erase_data === true ) {

	// Delete calendar posts.
	$wpdb->query(
		"
DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'calendar' );
"
	);

	// Delete calendar postmeta.
	$wpdb->query( "
DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;
"
	);

	// Delete calendar terms.
	$terms = get_terms( array(
		'calendar_category',
		'calendar_feed',
		'calendar_type',
	) );
	if ( ! empty( $terms ) && is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			wp_delete_term( $term->term_id, $term->taxonomy );
		}
	}

}
