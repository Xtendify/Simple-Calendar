<?php
/**
 * Custom Post Types and Taxonomies
 *
 * @package SimpleCalendar
 */
namespace SimpleCalendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Post Types and Taxonomies.
 *
 * Register and initialize custom post types and custom taxonomies.
 */
class Post_Types {

	/**
	 * Hook in WordPress init to register custom content.
	 */
	public function __construct() {
		// Register custom taxonomies.
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
		// Register custom post types.
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		// Filter the calendar feed post content to display a calendar view.
		add_filter( 'the_content', array( $this, 'filter_post_content' ), 100 );
		// Delete calendar transients and notices upon post deletion.
		add_action( 'before_delete_post', array( $this, 'upon_deletion' ), 10, 1 );
	}

	/**
	 * Register custom taxonomies.
	 */
	public static function register_taxonomies() {

		do_action( 'simcal_register_taxonomies' );

		if ( ! taxonomy_exists( 'calendar_feed' ) ) {

			// Feed Type.
			$labels = array(
				'name'                       => __( 'Events Source Types', 'google-calendar-events' ),
				'singular_name'              => __( 'Events Source Type', 'google-calendar-events' ),
				'menu_name'                  => __( 'Events Source Type', 'google-calendar-events' ),
				'all_items'                  => __( 'All Events Source Types', 'google-calendar-events' ),
				'parent_item'                => __( 'Parent Events Source Type', 'google-calendar-events' ),
				'parent_item_colon'          => __( 'Parent Events Source Type:', 'google-calendar-events' ),
				'new_item_name'              => __( 'New Events Source Type', 'google-calendar-events' ),
				'add_new_item'               => __( 'Add New Events Source Type', 'google-calendar-events' ),
				'edit_item'                  => __( 'Edit Events Source Type', 'google-calendar-events' ),
				'update_item'                => __( 'Update Events Source Type', 'google-calendar-events' ),
				'view_item'                  => __( 'View Events Source Type', 'google-calendar-events' ),
				'separate_items_with_commas' => __( 'Separate events source types with commas', 'google-calendar-events' ),
				'add_or_remove_items'        => __( 'Add or remove events source types', 'google-calendar-events' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'google-calendar-events' ),
				'popular_items'              => __( 'Popular events source types', 'google-calendar-events' ),
				'search_items'               => __( 'Search Events Source Types', 'google-calendar-events' ),
				'not_found'                  => __( 'Not Found', 'google-calendar-events' ),
			);

			$args   = array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'public'            => false,
				'show_admin_column' => false,
				'show_in_nav_menus' => false,
				'show_tagcloud'     => false,
				'show_ui'           => false
			);
			register_taxonomy( 'calendar_feed', array( 'calendar' ), $args );

		}

		if ( ! taxonomy_exists( 'calendar_type' ) ) {

			// Calendar Type.
			$labels = array(
				'name'                       => __( 'Calendar Types', 'google-calendar-events' ),
				'singular_name'              => __( 'Calendar Type', 'google-calendar-events' ),
				'menu_name'                  => __( 'Calendar Type', 'google-calendar-events' ),
				'all_items'                  => __( 'All Calendar Types', 'google-calendar-events' ),
				'parent_item'                => __( 'Parent Calendar Type', 'google-calendar-events' ),
				'parent_item_colon'          => __( 'Parent Calendar Type:', 'google-calendar-events' ),
				'new_item_name'              => __( 'New Calendar Type', 'google-calendar-events' ),
				'add_new_item'               => __( 'Add New Calendar Type', 'google-calendar-events' ),
				'edit_item'                  => __( 'Edit Calendar Type', 'google-calendar-events' ),
				'update_item'                => __( 'Update Calendar Type', 'google-calendar-events' ),
				'view_item'                  => __( 'View Calendar Type', 'google-calendar-events' ),
				'separate_items_with_commas' => __( 'Separate calendar types with commas', 'google-calendar-events' ),
				'add_or_remove_items'        => __( 'Add or remove calendar types', 'google-calendar-events' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'google-calendar-events' ),
				'popular_items'              => __( 'Popular calendar types', 'google-calendar-events' ),
				'search_items'               => __( 'Search Calendar Types', 'google-calendar-events' ),
				'not_found'                  => __( 'Not Found', 'google-calendar-events' ),
			);

			$args   = array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'public'            => false,
				'show_admin_column' => false,
				'show_in_nav_menus' => false,
				'show_tagcloud'     => false,
				'show_ui'           => false
			);
			register_taxonomy( 'calendar_type', array( 'calendar' ), $args );

		}

		if ( ! taxonomy_exists( 'calendar_category' ) ) {

			// Feed Category.
			$labels = array(
				'name'                       => __( 'Categories', 'google-calendar-events' ),
				'singular_name'              => __( 'Category', 'google-calendar-events' ),
				'menu_name'                  => __( 'Categories', 'google-calendar-events' ),
				'all_items'                  => __( 'All Categories', 'google-calendar-events' ),
				'parent_item'                => __( 'Parent Category', 'google-calendar-events' ),
				'parent_item_colon'          => __( 'Parent Category:', 'google-calendar-events' ),
				'new_item_name'              => __( 'New Category', 'google-calendar-events' ),
				'add_new_item'               => __( 'Add New Category', 'google-calendar-events' ),
				'edit_item'                  => __( 'Edit Category', 'google-calendar-events' ),
				'update_item'                => __( 'Update Category', 'google-calendar-events' ),
				'view_item'                  => __( 'View Category', 'google-calendar-events' ),
				'separate_items_with_commas' => __( 'Separate categories with commas', 'google-calendar-events' ),
				'add_or_remove_items'        => __( 'Add or remove categories', 'google-calendar-events' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'google-calendar-events' ),
				'popular_items'              => __( 'Popular Categories', 'google-calendar-events' ),
				'search_items'               => __( 'Search Categories', 'google-calendar-events' ),
				'not_found'                  => __( 'Not Found', 'google-calendar-events' ),
			);

			$args   = array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_tagcloud'     => false,
				'show_ui'           => true
			);

			register_taxonomy( 'calendar_category', array( 'calendar' ), $args );
		}

	}

	/**
	 * Register custom post types.
	 */
	public static function register_post_types() {

		do_action( 'simcal_register_post_types' );

		if ( ! post_type_exists( 'calendar' ) ) {

			// Calendar feed post type.
			$labels        = array(
				'name'               => _x( 'Calendars', 'Post Type General Name', 'google-calendar-events' ),
				'singular_name'      => _x( 'Calendar', 'Post Type Singular Name', 'google-calendar-events' ),
				'menu_name'          => __( 'Calendars', 'google-calendar-events' ),
				'name_admin_bar'     => __( 'Calendars', 'google-calendar-events' ),
				'parent_item_colon'  => __( 'Parent Calendar:', 'google-calendar-events' ),
				'all_items'          => __( 'All Calendars', 'google-calendar-events' ),
				'add_new_item'       => __( 'Add New Calendar', 'google-calendar-events' ),
				'add_new'            => __( 'Add New', 'google-calendar-events' ),
				'new_item'           => __( 'New Calendar', 'google-calendar-events' ),
				'edit_item'          => __( 'Edit Calendar', 'google-calendar-events' ),
				'update_item'        => __( 'Update Calendar', 'google-calendar-events' ),
				'view_item'          => __( 'View Calendar', 'google-calendar-events' ),
				'search_items'       => __( 'Search Calendar', 'google-calendar-events' ),
				'not_found'          => __( 'Calendars not found', 'google-calendar-events' ),
				'not_found_in_trash' => __( 'Calendars not found in Trash', 'google-calendar-events' ),
			);

			$rewrite_rules = array(
				'feeds'      => false,
				'pages'      => false,
				'slug'       => 'calendar',
				'with_front' => true,
			);

			$args          = array(
				'capability_type'     => 'post',
				'exclude_from_search' => false,
				'has_archive'         => false,
				'hierarchical'        => false,
				'label'               => __( 'Calendar', 'google-calendar-events' ),
				'labels'              => $labels,
				'query_var'           => true,
				'public'              => true,
				'publicly_queryable'  => true,
				'menu_icon'           => 'dashicons-calendar-alt',
				'menu_position'       => 26.8,
				'rewrite'             => $rewrite_rules,
				'show_in_admin_bar'   => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_ui'             => true,
				'supports'            => array( 'title', 'editor' ),
				'taxonomies'          => array(
					'calendar_category',
					'calendar_feed',
					'calendar_type',
				),
			);

			register_post_type( 'calendar', $args );
		}

	}

	/**
	 * Filter post content to output a calendar.
	 *
	 * @param  string $the_content
	 *
	 * @return string
	 */
	public function filter_post_content( $the_content ) {

		if ( is_singular() ) {

			global $post;

			if ( 'calendar' == $post->post_type ) {

				if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
					return '';
				} else {
					ob_start();
					simcal_print_calendar( $post );
					return ob_get_clean();
				}

			} else {

				$post_types = array();
				$settings   = get_option( 'simple-calendar_settings_calendars' );
				if ( isset( $settings['general']['attach_calendars_posts'] ) ) {
					$post_types = $settings['general']['attach_calendars_posts'];
				}

				if ( empty( $post_types ) ) {
					return $the_content;
				}

				if ( in_array( $post->post_type, (array) $post_types ) ) {

					$id = absint( get_post_meta( $post->ID, '_simcal_attach_calendar_id', true ) );

					if ( $id > 0 ) {

						$pos = esc_attr( get_post_meta( $post->ID, '_simcal_attach_calendar_position', true ) );

						ob_start();

						if ( 'after' == $pos ) {
							echo $the_content;
							simcal_print_calendar( $id );
						} elseif ( 'before' == $pos ) {
							simcal_print_calendar( $id );
							echo $the_content;
						} else {
							echo $the_content;
						}

						return ob_get_clean();
					}
				}

			}

		}

		return $the_content;
	}

	/**
	 * Upon posts deletion.
	 *
	 * Delete transients and notices when a calendar is deleted.
	 *
	 * @param $post_id
	 */
	public function upon_deletion( $post_id ) {

		$post_type = get_post_type( $post_id );

		if ( 'calendar' == $post_type ) {

			$notices = get_option( 'simple-calendar_admin_notices', array() );

			if ( ! empty( $notices ) && isset( $notices[ 'calendar_' . strval( $post_id ) ] ) ) {
				unset( $notices[ 'calendar_' . strval( $post_id ) ] );
				update_option( 'simple-calendar_admin_notices', $notices );
			}

			simcal_delete_feed_transients( $post_id );
		}
	}

}
