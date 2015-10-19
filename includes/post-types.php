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
 *
 * @since 3.0.0
 */
class Post_Types {

	/**
	 * Hook in WordPress init to register custom content.
	 *
	 * @since 3.0.0
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
	 *
	 * @since 3.0.0
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
				'show_ui'           => false,
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
				'show_ui'           => false,
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
				'show_ui'           => true,
			);

			register_taxonomy( 'calendar_category', array( 'calendar' ), $args );
		}

	}

	/**
	 * Register custom post types.
	 *
	 * @since 3.0.0
	 */
	public static function register_post_types() {

		do_action( 'simcal_register_post_types' );

		if ( ! post_type_exists( 'calendar' ) ) {

			// Calendar feed post type.
			$labels        = array(
				'name'               => _x( 'Calendars', 'Post Type General Name', 'google-calendar-events' ),
				'singular_name'      => _x( 'Calendar', 'Post Type Singular Name', 'google-calendar-events' ),
				'menu_name'          => __( 'Calendars', 'google-calendar-events' ),
				'name_admin_bar'     => __( 'Calendar', 'google-calendar-events' ),
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
				'with_front' => false,
				'slug'       => 'calendar',
			);

			$svg_icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iU2hhcGVzX3hBMF9JbWFnZV8xXyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHZpZXdCb3g9IjAgMCAxMDI0IDEwMjQiIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDEwMjQgMTAyNCIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PHBhdGggZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMDAwMDAwIiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIGQ9Ik01MTMuNCwxMDEzLjNjLTE2My44LDAtMzI3LjcsMC00OTEuNSwwYy05LjcsMC04LjgsMC45LTguOC04LjljMC0yNDMuNywwLjItNDg3LjMtMC4zLTczMWMtMC4xLTM5LjYsMjcuNy03Ni40LDY5LjYtODIuM2MzLTAuNCw2LTAuNSw5LTAuNWMzNSwwLDcwLDAuMSwxMDUtMC4xYzUuMSwwLDYuNSwxLjQsNi41LDYuNWMtMC4yLDI2LjgsMC4xLDUzLjctMC4yLDgwLjVjLTAuMiwxNS4yLDMuOSwyOC4yLDE1LjksMzguMmMyLjcsMi4yLDUsNC44LDcuNCw3LjRjOCw4LjYsMTguMSwxMi40LDI5LjYsMTIuNGMzMC43LDAuMiw2MS4zLDAuMiw5MiwwYzExLjItMC4xLDIxLjItMy45LDI5LjEtMTIuMmMzLjQtMy42LDctNy4yLDEwLjYtMTAuNmM5LTguNCwxMi43LTE4LjksMTIuNy0zMWMwLjEtMjguMiwwLTU2LjMsMC04NC41YzAtNi42LDAtNi42LDYuNi02LjZjNzEuNSwwLDE0MywwLDIxNC41LDBjNi42LDAsNi42LDAsNi42LDYuN2MwLDI2LjgsMC4yLDUzLjctMC4xLDgwLjVjLTAuMiwxNSw1LjEsMjYuOCwxNS40LDM4YzEzLjYsMTQuNywyOC45LDIwLjgsNDguNywyMC4xYzI1LjYtMSw1MS4zLTAuNCw3Ny0wLjJjMTMuOSwwLjEsMjQuOC01LjEsMzUuNC0xNC40YzE1LjktMTQsMjIuMS0zMC40LDIxLjEtNTEuM2MtMS4xLTI0LjMtMC4xLTQ4LjctMC40LTczYzAtNS4xLDEuNC02LjUsNi41LTYuNWMzNC43LDAuMiw2OS4zLDAsMTA0LDAuMWM0Mi4zLDAuMiw3OC4zLDM2LDc4LjMsNzguM2MwLjEsMjQ2LDAsNDkyLDAsNzM4YzAsNi40LDAsNi40LTcuMyw2LjRDODQyLDEwMTMuMyw2NzcuNywxMDEzLjMsNTEzLjQsMTAxMy4zeiBNNDM5LjUsNjc4LjljMS42LTEsMi4yLTEuNSwzLTEuOGMxMS44LTUuOCwyMS41LTE0LjIsMjktMjQuOGMyNC4zLTM0LjEsMjQuMy03MC45LDguNi0xMDcuOGMtMTMuMy0zMS4zLTM5LjMtNDkuMy03MS01OS40Yy0yNy42LTguOC01Ni05LjQtODQuNS01LjZjLTIwLjMsMi43LTM5LjIsOS42LTU1LjUsMjIuMmMtMzUuNSwyNy4yLTQ4LjQsNjUuMS01MC40LDEwOGMtMC4yLDMuNywyLjEsMy45LDQuOCwzLjljMjIuMiwwLDQ0LjMtMC4xLDY2LjUsMGMzLjgsMCw1LjQtMS4xLDUuMy01LjFjLTAuMS03LjcsMS0xNS4zLDMtMjIuN2M1LjQtMjAsMTYuNS0zNC43LDM3LjgtMzkuN2M4LjEtMS45LDE2LjMtMi4xLDI0LjQtMS4zYzIxLjQsMi4xLDM2LjMsMTMuMSw0My41LDMzLjdjMy41LDEwLjEsMy45LDIwLjQsMi45LDMxYy0yLjIsMjMuNC0xNi4yLDM5LjctMzkuMSw0NC42Yy0xMy4yLDIuOC0yNi42LDQuMS00MC4yLDRjLTMuNywwLTUsMS4yLTQuOSw0LjljMC4xLDE2LjUsMC4yLDMzLDAsNDkuNWMwLDQsMS41LDUuMiw1LjMsNS4xYzEyLjktMC4zLDI1LjYsMC45LDM4LjMsM2MyNi4zLDQuMiw0My41LDE4LjgsNDkuMiw0MmMzLjMsMTMuNSwzLDI3LjEtMC4yLDQwLjZjLTIuMyw5LjctNi44LDE4LjQtMTMuOCwyNS43Yy0xNS40LDE2LjEtMzQuNSwyMS42LTU2LDE4LjljLTI3LjUtMy40LTQzLjUtMTgtNTAuNS00NC44Yy0xLjktNy4zLTMuMS0xNC43LTIuOC0yMi4yYzAuMi00LTEuMS01LjYtNS40LTUuNmMtMjMuNywwLjItNDcuMywwLjItNzEsMGMtNCwwLTUuNCwxLjItNC45LDUuMmMxLjQsMTMuMiwyLjcsMjYuNSw1LjksMzkuNWMxMS4xLDQ1LjIsMzguMiw3NS42LDgzLjQsODguMmMzNi43LDEwLjMsNzMuOCwxMC4xLDExMC41LDAuMWMyNC41LTYuNiw0NC43LTIwLjEsNjEtMzkuOGMyNC41LTI5LjcsMzQuNC02My45LDMxLjQtMTAxLjljLTIuMi0yNy40LTEyLjUtNTEuMy0zMy42LTY5LjhDNDYwLjcsNjg5LjMsNDUxLjksNjgyLDQzOS41LDY3OC45eiBNNzU1LDY5Mi41YzAtNjguNi0wLjEtMTM3LjMsMC4xLTIwNS45YzAtNS4xLTEuNC02LjUtNi41LTYuNGMtMTguMywwLjMtMzYuNywwLjQtNTUsMGMtNS43LTAuMS03LjIsMS45LTguMiw3Yy01LjksMzIuMS0yNC40LDUzLTU2LjMsNjEuMWMtMTcuNSw0LjUtMzUuNiw1LTUzLjUsNS44Yy0zLjksMC4yLTUuMiwxLjQtNS4yLDUuM2MwLjIsMTUuMywwLjIsMzAuNywwLDQ2Yy0wLjEsNC4zLDEuNiw1LjQsNS42LDUuM2MxNC4yLTAuMiwyOC4zLTAuMSw0Mi41LTAuMWMxNS41LDAsMzEsMC4xLDQ2LjUtMC4xYzQuMSwwLDUuOSwxLjMsNS41LDUuNGMtMC4xLDEuNywwLDMuMywwLDVjMCw5Mi4xLDAsMTg0LjMsMCwyNzYuNGMwLDcuMiwwLDcuMiw3LDcuMmMyMy41LDAsNDcsMCw3MC41LDBjNywwLDcsMCw3LTcuMkM3NTUsODI5LjEsNzU1LDc2MC44LDc1NSw2OTIuNXoiLz48cGF0aCBmaWxsPSJub25lIiBzdHJva2U9IiMwMDAwMDAiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgZD0iTTM3Ni43LDE5OS4yYzAsMjQuMiwwLDQ4LjMsMCw3Mi41Yy0wLjEsMjMuMS0xNy40LDQwLjUtNDAuNCw0MC42Yy0yMy4zLDAuMS00Ni42LDAuMS03MCwwYy0yMi44LTAuMS00MC4zLTE3LjQtNDAuMy00MC4xYy0wLjEtNDguNS0wLjEtOTYuOSwwLTE0NS40YzAtMjIuNCwxNy41LTQwLDM5LjktNDAuMWMyMy43LTAuMSw0Ny4zLTAuMSw3MSwwYzIyLjQsMC4xLDM5LjgsMTcuNywzOS44LDQwLjFDMzc2LjgsMTUwLjksMzc2LjcsMTc1LjEsMzc2LjcsMTk5LjJ6Ii8+PHBhdGggZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMDAwMDAwIiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIGQ9Ik04MDEuNywxOTkuNmMwLDI0LDAsNDgsMCw3MmMwLDIzLjQtMTcuMyw0MC43LTQwLjcsNDAuOGMtMjMuMiwwLjEtNDYuMywwLjEtNjkuNSwwYy0yMy4xLTAuMS00MC41LTE3LjQtNDAuNS00MC41Yy0wLjEtNDguMy0wLjEtOTYuNiwwLTE0NC45YzAtMjIuNywxNy41LTQwLjIsNDAuMi00MC4zYzIzLjMtMC4xLDQ2LjYtMC4xLDcwLDBjMjMuMSwwLjEsNDAuNCwxNy40LDQwLjUsNDAuNUM4MDEuOCwxNTEuMyw4MDEuNywxNzUuNCw4MDEuNywxOTkuNnoiLz48L3N2Zz4=';

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
				'menu_icon'           => $svg_icon,
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
	 * @since  3.0.0
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
	 * @since 3.0.0
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
