<?php
/**
 * Admin Notice
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin notice.
 *
 * An admin notice as an object.
 *
 * @since 3.0.0
 */
class Notice {

	/**
	 * Notice id.
	 * Will be the notice key in saved notices option.
	 *
	 * @access public
	 * @var string|array
	 */
	public $id = '';

	/**
	 * Notice type
	 * Gives the notice a CSS class.
	 *
	 * @access public
	 * @var string notice|error|updated|update-nag
	 */
	public $type = '';

	/**
	 * Additional classes.
	 *
	 * @access public
	 * @var string
	 */
	public $class = '';

	/**
	 * To which users the notice should be shown.
	 * If not set, will be visible to all users.
	 *
	 * @access public
	 * @var string
	 */
	public $capability = '';

	/**
	 * In which screen the notice should appear.
	 * If not set, will appear in every dashboard page/screen.
	 *
	 * @access public
	 * @var array
	 */
	public $screen = array();

	/**
	 * For which posts edit screens the notice should appear.
	 * If not set, will fallback on $screen rule only.
	 *
	 * @access public
	 * @var array
	 */
	public $post = array();

	/**
	 * Can the notice be dismissed by the user?
	 * If false, you need to set up a dismissal event.
	 *
	 * @access public
	 * @var bool
	 */
	public $dismissible = true;

	/**
	 * Whether to hide notice while keeping it stored.
	 * If false, will keep the notice in option without showing it.
	 *
	 * @access public
	 * @var bool
	 */
	public $visible = true;

	/**
	 * The notice content.
	 * Supports html. You would normally wrap this in paragraph tags.
	 *
	 * @access public
	 * @var string
	 */
	public $content = '';

	/**
	 * Make a notice.
	 *
	 * @since 3.0.0
	 *
	 * @param array $notice
	 */
	public function __construct( $notice ) {

		if ( ! empty( $notice['id'] ) && ! empty( $notice['content'] ) ) {

			// Content.
			$this->id = is_array( $notice['id'] ) ? array_map( 'sanitize_key', $notice['id'] ) : sanitize_key( $notice['id'] );
			$this->content = wp_kses_post( $notice['content'] );
			if ( ! empty( $notice['class'] ) ) {
				$this->class = is_array( $notice['class'] ) ? join( ' ', array_map( 'esc_attr', $notice['class'] ) ) : esc_attr( $notice['class'] );
			}

			// Type.
			$default = 'notice';
			$type = isset( $notice['type'] ) ? esc_attr( $notice['type'] ) : $default;
			$types = array(
				'error',
				'notice',
				'updated',
				'update-nag',
			);
			$this->type = in_array( $type, $types ) ? $type : $default;

			// Visibility.
			if ( ! empty( $notice['capability'] ) ) {
				$this->capability = esc_attr( $notice['capability'] );
			}
			if ( ! empty( $notice['screen'] ) ) {
				$this->screen = is_array( $notice['screen'] ) ? array_map( 'esc_attr', $notice['screens'] ) : array( esc_attr( $notice['screen'] ) );
			}
			if ( ! empty( $notice['post'] ) ) {
				$this->post = is_array( $notice['post'] ) ? array_map( 'intval', $notice['post'] ) : array( intval( $notice['post'] ) );
			}
			if ( ! empty( $notice['dismissible'] ) ) {
				$this->dismissible = $notice['dismissible'] === false ? false: true;
			}
			if ( ! empty( $notice['visible'] ) ) {
				$this->visible = $notice['visible'] === false ? false: true;
			}
		}

	}

	/**
	 * Add the notice.
	 *
	 * @since 3.0.0
	 */
	public function add() {
		if ( ! empty( $this->id ) && ! empty( $this->content ) ) {
			$notices = get_option( 'simple-calendar_admin_notices', array() );
			if ( is_array( $this->id ) ) {
				foreach ( $this->id as $k => $v ) {
					$notices[ $k ][ $v ] = $this;
				}
			} else {
				$notices[ $this->id ][] = $this;
			}
			update_option( 'simple-calendar_admin_notices', $notices );
		}
	}

	/**
	 * Remove the notice.
	 *
	 * @since 3.0.0
	 */
	public function remove() {
		if ( ! empty( $this->id ) && ! empty( $this->content ) ) {
			$notices = get_option( 'simple-calendar_admin_notices', array() );
			if ( is_array( $this->id ) ) {
				foreach ( $this->id as $k => $v ) {
					unset( $notices[ $k ] );
				}
			} else {
				unset( $notices[ $this->id ] );
			}
			update_option( 'simple-calendar_admin_notices', $notices );
		}
	}

}
