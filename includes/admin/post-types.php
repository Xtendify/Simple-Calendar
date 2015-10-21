<?php
/**
 * Admin Post Types
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

use SimpleCalendar\Abstracts\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin post types.
 *
 * Handles admin views and custom content for post types and taxonomies in admin dashboard screens.
 *
 * @since 3.0.0
 */
class Post_Types {

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		// Add meta boxes to custom content.
		new Meta_Boxes();

		// Add column headers in calendar feeds admin archives.
		add_filter( 'manage_calendar_posts_columns', array( $this, 'add_calendar_feed_column_headers' ) );
		// Process column contents for calendar feeds.
		add_action( 'manage_calendar_posts_custom_column', array( $this, 'calendar_feed_column_content' ), 10, 2 );

		// Add actions in calendar feed rows.
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		// Add bulk actions.
		add_action( 'admin_init', array( $this, 'bulk_actions' ) );
		// Add content to edit calendars page.
		add_action( 'load-edit.php', array( $this, 'edit_table_hooks' ) );

		// Default calendar post type content (default event template).
		add_filter( 'default_content', array( $this, 'default_event_template' ), 10, 2 );

		// Add a clear cache link in submit post box.
		add_action( 'post_submitbox_misc_actions', array( $this, 'clear_cache_button' ) );

		// Add media button to post editor for adding a shortcode.
		add_action( 'media_buttons', array( $this, 'add_shortcode_button' ), 100 );
		add_action( 'edit_form_after_editor', array( $this, 'add_shortcode_panel' ), 100 );
	}

	/**
	 * Add column headers to Calendar feeds custom post type page.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $columns Default columns.
	 *
	 * @return array Filtered output.
	 */
	public function add_calendar_feed_column_headers( $columns ) {

		// New columns.
		$feed_info = array( 'feed' => __( 'Events Source', 'google-calendar-events' ) );
		$calendar_info = array( 'calendar' => __( 'Calendar Type', 'google-calendar-events' ) );
		$shortcode = array( 'shortcode' => __( 'Shortcode', 'google-calendar-events' ) );

		// Merge with existing columns and rearrange.
		$columns = array_slice( $columns, 0, 2, true ) + $feed_info + $calendar_info + $shortcode + array_slice( $columns, 2, null, true );

		return $columns;
	}

	/**
	 * Fill out the Calendar feed post type columns.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $column_name Column identifier.
	 * @param  int    $post_id     The calendar feed post id.
	 *
	 * @return void
	 */
	public function calendar_feed_column_content( $column_name, $post_id ) {

		switch ( $column_name ) {

			case 'feed':

				$feed = simcal_get_feed( $post_id );
				echo isset( $feed->name ) ? $feed->name : '&mdash;';
				break;

			case 'calendar':

				$info = '&mdash;';

				if ( $terms = wp_get_object_terms( $post_id, 'calendar_type' ) ) {

					$calendar_type  = sanitize_title( current( $terms )->name );
					$calendar       = simcal_get_calendar( $calendar_type );

					if ( $calendar instanceof Calendar ) {
						$info = $calendar->name;
						$views = get_post_meta( $post_id, '_calendar_view', true );;
						$view = isset( $views[ $calendar->type ] ) ? $views[ $calendar->type ] : '';

						if ( isset( $calendar->views[ $view ] ) ) {
							$info .= ' &rarr; ' . $calendar->views[ $view ];
						}
					}
				}

				echo $info;
				break;

			case 'shortcode' :

				simcal_print_shortcode_tip( $post_id );
				break;
		}
	}

	/**
	 * Add actions to Calendar feed post type row view.
	 *
	 * @since  3.0.0
	 *
	 * @param  array    $actions Default actions
	 * @param  \WP_Post $post    Post object.
	 *
	 * @return array Filtered output.
	 */
	public function row_actions( $actions, $post ) {

		// Add a clear feed cache action link.
		if ( $post->post_type == 'calendar' ) {
			$actions['duplicate_feed'] = '<a href="' . esc_url( add_query_arg( array( 'duplicate_feed' => $post->ID ) ) ) . '">' . __( 'Clone', 'google-calendar-events' )       . '</a>';
			$actions['clear_cache']    = '<a href="' . esc_url( add_query_arg( array( 'clear_cache' => $post->ID ) ) ) . '">'    . __( 'Clear Cache', 'google-calendar-events' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Bulk actions.
	 *
	 * @since 3.0.0
	 */
	public function bulk_actions() {

		// Clear an individual feed cache.
		// @todo Convert the clear cache request to ajax.
		if ( isset( $_REQUEST['clear_cache'] ) ) {

			$id = intval( $_REQUEST['clear_cache'] );

			if ( $id > 0 ) {
				simcal_delete_feed_transients( $id );
			}

			wp_redirect( remove_query_arg( 'clear_cache' ) );
		}

		// Duplicate a feed post type.
		if ( isset( $_REQUEST['duplicate_feed'] ) ) {

			$id = intval( $_REQUEST['duplicate_feed'] );

			if ( $id > 0 ) {
				$this->duplicate_feed( $id );
			}

			wp_redirect( remove_query_arg( 'duplicate_feed' ) );
		}

		$bulk_actions = new Bulk_Actions( 'calendar' );

		$bulk_actions->register_bulk_action( array(
			'menu_text'     => __( 'Clear cache', 'google-calendar-events' ),
			'action_name'   => 'clear_calendars_cache',
			'callback'      => function( $post_ids ) {
				simcal_delete_feed_transients( $post_ids );
			},
			'admin_notice'  => __( 'Cache cleared.', 'google-calendar-events' ),
			)
		);

		$bulk_actions->init();
	}

	/**
	 * Edit calendars table hooks.
	 *
	 * @since 3.0.0
	 * @internal
	 */
	public function edit_table_hooks() {

		$screen = simcal_is_admin_screen();

		if ( 'edit-calendar' == $screen ) {
			add_action( 'in_admin_footer', function() {

			} );
		}
	}

	/**
	 * Clone a feed post type.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id
	 */
	private function duplicate_feed( $post_id ) {

		if ( $duplicate = get_post( intval( $post_id ), 'ARRAY_A' ) ) {

			if ( 'calendar' == $duplicate['post_type'] ) {

				$duplicate['post_title'] = $duplicate['post_title'] . ' (' . __( 'Copy', 'google-calendar-events' ) . ')';

				unset( $duplicate['ID'] );
				unset( $duplicate['guid'] );
				unset( $duplicate['comment_count'] );

				$duplicate_id = wp_insert_post( $duplicate );

				$taxonomies = get_object_taxonomies( $duplicate['post_type'] );
				foreach ( $taxonomies as $taxonomy ) {
					$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
					wp_set_object_terms( $duplicate_id, $terms, $taxonomy );
				}

				$custom_fields = get_post_custom( $post_id );
				foreach ( $custom_fields as $key => $value ) {
					add_post_meta( $duplicate_id, $key, maybe_unserialize( $value[0] ) );
				}
			}

		}
	}

	/**
	 * Default event template builder content.
	 *
	 * @since  3.0.0
	 *
	 * @param  string   $content
	 * @param  \WP_Post $post
	 *
	 * @return string
	 */
	public function default_event_template( $content, $post ) {
		return 'calendar' == $post->post_type ? simcal_default_event_template() : $content;
	}

	/**
	 * Clear cache button.
	 *
	 * @since 3.0.0
	 */
	public function clear_cache_button() {

		global $post, $post_type;

		if ( $post_type == 'calendar' && isset( $post->ID ) ) {
			echo '<a id="simcal-clear-cache" class="button" data-id="' . $post->ID . ' ">' .
			     '<i class="simcal-icon-spinner simcal-icon-spin" style="display: none;"></i> ' .
			     __( 'Clear cache', 'google-calendar-events' ) .
			     '</a>';
		}
	}

	/**
	 * Add a shortcode button.
	 *
	 * Adds a button to add a calendar shortcode in WordPress content editor.
	 * Uses Thickbox. http://codex.wordpress.org/ThickBox
	 *
	 * @since 3.0.0
	 */
	public function add_shortcode_button() {

		$post_types = array();

		$settings = get_option( 'simple-calendar_settings_calendars' );
		if ( isset( $settings['general']['attach_calendars_posts'] ) ) {
			$post_types = $settings['general']['attach_calendars_posts'];
		}

		global $post_type;

		if ( in_array( $post_type, $post_types ) ) {

			// Thickbox will ignore height and width, will adjust these in js.
			// @see https://core.trac.wordpress.org/ticket/17249
			?>
			<a href="#TB_inline?height=250&width=500&inlineId=simcal-insert-shortcode-panel" id="simcal-insert-shortcode-button" class="thickbox button insert-calendar add_calendar">
				<span class="wp-media-buttons-icon dashicons-before dashicons-calendar-alt"></span> <?php _e( 'Add Calendar', 'google-calendar-events' ); ?>
			</a>
			<?php

		}

	}

	/**
	 * Panel for the add shortcode media button.
	 *
	 * Prints the panel for choosing a calendar to insert as a shortcode in a page or post.
	 *
	 * @since 3.0.0
	 */
	public function add_shortcode_panel() {

		$calendars = simcal_get_calendars();

		?>
		<div id="simcal-insert-shortcode-panel" style="display:none;">
			<div class="simcal-insert-shortcode-panel">
				<h1><?php _e( 'Add Calendar', 'google-calendar-events' ); ?></h1>
				<?php _e( 'Add a calendar to your post.', 'google-calendar-events' ); ?>
				<?php if ( ! empty( $calendars ) && is_array( $calendars ) ) : ?>
					<p>
						<label for="simcal-choose-calendar">
							<?php $multiselect = count( $calendars ) > 15 ? ' simcal-field-select-enhanced' : ''; ?>
							<select id="simcal-choose-calendar"
							        class="simcal-field simcal-field-select<?php echo $multiselect; ?>"
							        name="">
								<?php foreach ( $calendars as $id => $title ) : ?>
									<option value="<?php echo $id ?>"><?php echo $title ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					</p>
					<p><input type="button" value="<?php _e( 'Insert Calendar', 'google-calendar-events' ); ?>" id="simcal-insert-shortcode" class="button button-primary button-large" name="" /></p>
				<?php else : ?>
					<p><em><?php _e( 'Could not find any calendars to add to this post.', 'google-calendar-events' ); ?></em></p>
					<strong><a href="post-new.php?post_type=calendar"><?php _e( 'Please add and configure new calendar first.', 'google-calendar-events' ); ?></a></strong>
				<?php endif; ?>
			</div>
		</div>
		<?php

	}

}
