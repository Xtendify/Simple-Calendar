<?php

/**
 * Disables Gutenberg/block editing on the calendar post type.
 *
 * Due to the settings needed, this should be sufficient for now,
 * but we can revisit block compatibility at a later date.
 *
 * @param bool   $can_edit  Gutenberg edit this post type.
 * @param string $post_type The post type.
 *
 * @return bool
 */
function simcal_gutenberg_can_edit_post_type( $can_edit, $post_type ) {
	return ( 'calendar' === $post_type ) ? false : $can_edit;
}

add_filter( 'use_block_editor_for_post_type', 'simcal_gutenberg_can_edit_post_type', 9999, 2 );
add_filter( 'gutenberg_can_edit_post_type', 'simcal_gutenberg_can_edit_post_type', 9999, 2 );
