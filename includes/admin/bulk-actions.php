<?php
/**
 * Admin Bulk Actions
 *
 * Add bulk actions to post types edit screens.
 * Based on https://github.com/Seravo/wp-custom-bulk-actions
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin bulk actions.
 */
class Bulk_Actions {

	/**
	 * Target post type.
	 *
	 * @access public
	 * @var string
	 */
	public $bulk_action_post_type = '';

	/**
	 * Bulk actions.
	 *
	 * @access private
	 * @var array
	 */
	private $actions = array();

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $post_type
	 */
	public function __construct( $post_type ) {
		$this->bulk_action_post_type = post_type_exists( $post_type ) ? $post_type : '';
	}

	/**
	 * Define all your custom bulk actions and corresponding callbacks
	 * Define at least $menu_text and $callback parameters
	 *
	 * @since 3.0.0
	 *
	 * @param array $args
	 */
	public function register_bulk_action( $args ) {

		$func = array();
		$func['action_name']  = isset( $args['action_name'] )  ? sanitize_key( $args['action_name'] ) : '';
		$func['callback']     = isset( $args['callback'] )     ? $args['callback'] : '';
		$func['menu_text']    = isset( $args['menu_text'] )    ? esc_attr( $args['menu_text'] ) : '';
		$func['admin_notice'] = isset( $args['admin_notice'] ) ? esc_attr( $args['admin_notice'] ) : '';

		if ( $func['action_name'] && $func['callback'] ) {
			$this->actions[ $func['action_name'] ] = $func;
		}
	}

	/**
	 * Init.
	 *
	 * Callbacks need to be registered before add_actions.
	 *
	 * @since 3.0.0
	 */
	public function init() {
		if ( is_admin() ) {
			add_action( 'admin_footer-edit.php', array( $this, 'custom_bulk_admin_footer' ) );
			add_action( 'load-edit.php',         array( $this, 'custom_bulk_action' ) );
			add_action( 'admin_notices',         array( $this, 'custom_bulk_admin_notices' ) );
		}
	}

	/**
	 * Step 1: add the custom Bulk Action to the select menus.
	 *
	 * @since 3.0.0
	 */
	public function custom_bulk_admin_footer() {

		global $post_type;

		// Only permit actions with defined post type.
		if ( $post_type == $this->bulk_action_post_type ) {

			?>
			<script type="text/javascript">
				jQuery( document ).ready( function() {
					<?php foreach ( $this->actions as $action_name => $action ) : ?>
						jQuery( '<option>' ).val( '<?php echo $action_name ?>' ).text( '<?php echo $action['menu_text'] ?>').appendTo( 'select[name="action"]'  );
						jQuery( '<option>' ).val( '<?php echo $action_name ?>' ).text( '<?php echo $action['menu_text'] ?>').appendTo( 'select[name="action2"]' );
					<?php endforeach; ?>
				} );
			</script>
			<?php

		}

	}

	/**
	 * Step 2: handle the custom Bulk Action.
	 *
	 * Based on the post http://wordpress.stackexchange.com/questions/29822/custom-bulk-action
	 *
	 * @since 3.0.0
	 */
	public function custom_bulk_action() {

		global $typenow;
		$post_type = $typenow;

		if ( $post_type == $this->bulk_action_post_type ) {

			// Get the action.
			// Depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc.
			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action = $wp_list_table->current_action();

			// Allow only defined actions.
			$allowed_actions = array_keys( $this->actions );
			if ( ! in_array( $action, $allowed_actions ) ) {
				return;
			}

			// Security check.
			check_admin_referer( 'bulk-posts' );

			// Make sure ids are submitted.
			// Depending on the resource type, this may be 'media' or 'ids'.
			if ( isset( $_REQUEST['post'] ) ) {
				$post_ids = array_map( 'intval', $_REQUEST['post'] );
			}

			if ( empty( $post_ids ) ) {
				return;
			}

			// This is based on wp-admin/edit.php.
			$sendback = remove_query_arg(
				array( 'exported', 'untrashed', 'deleted', 'ids' ),
				wp_get_referer()
			);
			if ( ! $sendback ) {
				$sendback = admin_url( "edit.php?post_type=$post_type" );
			}

			$pagenum  = $wp_list_table->get_pagenum();
			$sendback = add_query_arg( 'paged', $pagenum, $sendback );

			// Check that we have anonymous function as a callback.
			$anon_fns = array_filter( $this->actions[ $action ], function( $el ) {
				return $el instanceof \Closure;
			} );

			if ( count( $anon_fns ) > 0 ) {
				$this->actions[ $action ]['callback']( $post_ids );
			} else {
				call_user_func( $this->actions[ $action ]['callback'], $post_ids );
			}

			$sendback = add_query_arg(
				array( 'success_action' => $action, 'ids' => join( ',', $post_ids ) ),
				$sendback
			);
			$sendback = remove_query_arg(
				array( 'action', 'paged', 'mode', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view' ),
				$sendback
			);

			wp_redirect( $sendback );

			exit;
		}
	}

	/**
	 * Step 3: display an admin notice after action.
	 *
	 * @since 3.0.0
	 */
	public function custom_bulk_admin_notices() {

		global $post_type, $pagenow;

		if ( isset( $_REQUEST['ids'] ) ) {
			$post_ids = explode( ',', $_REQUEST['ids'] );
		}

		// Make sure ids are submitted.
		// Depending on the resource type, this may be 'media' or 'ids'.
		if ( empty( $post_ids ) ) {
			return;
		}

		$post_ids_count = is_array( $post_ids ) ? count( $post_ids ) : 1;

		if ( $pagenow == 'edit.php' && $post_type == $this->bulk_action_post_type ) {

			if ( isset( $_REQUEST['success_action'] ) ) {

				// Print notice in admin bar.
				$message = $this->actions[ $_REQUEST['success_action'] ]['admin_notice'];

				if ( is_array( $message ) ) {
					$message = sprintf( _n( $message['single'], $message['plural'], $post_ids_count, 'google-calendar-events' ), $post_ids_count );
				}

				$class = 'updated notice is-dismissible above-h2';
				if ( ! empty( $message ) ) {
					echo "<div class=\"{$class}\"><p>{$message}</p></div>";
				}
			}
		}
	}

}
