<?php
/**
 * Admin notices
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin notices class.
 *
 * Handles and displays notices in admin dashboard pages.
 *
 * @since 3.0.0
 */
class Notices {

	/**
	 * Get notices.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'remove_notice' ), 10 );
		add_action( 'admin_init', array( $this, 'process_notices' ), 40 );
	}

	/**
	 * Process notices.
	 *
	 * @since 3.0.0
	 */
	public function process_notices() {

		$notices = $this->get_notices();

		if ( ! empty( $notices ) && is_array( $notices ) ) {

			foreach ( $notices as $group ) {
				foreach ( $group as $notice ) {

					if ( $notice instanceof Notice ) {

						if ( $notice->visible === false ) {
							continue;
						}

						if ( ! empty( $notice->capability ) ) {
							if ( ! current_user_can( $notice->capability ) ) {
								continue;
							}
						}

						if ( ! empty( $notice->screen ) && is_array( $notice->screen ) && function_exists( 'get_current_screen' ) ) {
							$screen = get_current_screen();
							if ( isset( $screen->id ) ) {
								if ( ! in_array( $screen->id, $notice->screen ) ) {
									continue;
								}
							}
						}

						if ( ! empty( $notice->post ) && is_array( $notice->post ) ) {
							if ( isset( $_GET['post'] ) ) {
								if ( ! in_array( intval( $_GET['post'] ), $notice->post ) ) {
									continue;
								}
							} else {
								continue;
							}
						}

						$this->add_notice( $notice );
					}
				}
			}
		}
	}

	/**
	 * Add notice.
	 *
	 * @TODO Improve notice dismissal with ajax.
	 *
	 * @since  3.0.0
	 *
	 * @param  Notice $notice
	 *
	 * @return void
	 */
	public function add_notice( $notice ) {

		if ( $notice instanceof Notice ) {

			add_action( 'admin_notices', $print_notice = function() use ( $notice ) {

				$name         = is_array( $notice->id ) ? key( $notice->id ) : $notice->id;
				$url          = add_query_arg( array( 'dismiss_simcal_notice' => $name ) );
				$dismiss_link = $notice->dismissible === true
					? sprintf( '<a class="dashicons-before dashicons-dismiss simcal-dismiss-notice" href="%1$s"></a>', $url )
					: '';

				echo '<div class="' . $notice->type . ' ' . $notice->class . ' simcal-admin-notice" data-notice-id="' . $name . '">' . $dismiss_link . $notice->content . '</div>';
			} );

		}
	}

	/**
	 * Dismiss a notice.
	 *
	 * @TODO  Improve notice dismissal with ajax.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $notice (optional) The notice id.
	 *
	 * @return void
	 */
	public function remove_notice( $notice = '' ) {

		$notices = $this->get_notices();
		$update = false;

		if ( ! empty( $notice ) ) {
			if ( isset( $notices[ $notice ] ) ) {
				unset( $notices[ $notice ] );
				$update = true;
			}
		}

		if ( isset( $_GET['dismiss_simcal_notice'] ) ) {
			if ( isset( $notices[ $_GET['dismiss_simcal_notice'] ] ) ) {
				unset( $notices[ esc_attr( $_GET['dismiss_simcal_notice'] ) ] );
				$update = true;
			}
		}

		if ( $update === true ) {
			update_option( 'simple-calendar_admin_notices', $notices );
		}
	}

	/**
	 * Show a notice.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $notice
	 *
	 * @return void
	 */
	public function show_notice( $notice ) {

		$notices = $this->get_notices();

		if ( isset( $notices[ $notice ]->visible ) ) {
			$notices[ $notice ]->visible = true;
			update_option( 'simple-calendar_admin_notices', $notices );
		}
	}

	/**
	 * Hide a notice.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $notice
	 *
	 * @return void
	 */
	public function hide_notice( $notice ) {

		$notices = $this->get_notices();

		if ( isset( $notices[ $notice ]->visible ) ) {
			$notices[ $notice ]->visible = false;
			update_option( 'simple-calendar_admin_notices', $notices );
		}
	}

	/**
	 * Get current notices.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function get_notices() {
		return apply_filters(
			'simcal_admin_notices',
			get_option( 'simple-calendar_admin_notices', array() )
		);
	}

}
