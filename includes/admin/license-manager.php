<?php
/**
 * Add-ons License Manager
 *
 * @package SimpleCalendar\Admin
 */

namespace SimpleCalendar\Admin;

/**
 * Add-ons license manager
 *
 * @since 3.0.0
 */
class License_Manager {

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'activate' ) );
		add_action( 'admin_init', array( $this, 'deactivate' ) );

		add_action( 'simcal_settings_page', function( $page, $tab ) {
			if ( 'settings' == $page && 'licenses' == $tab ) {
				wp_nonce_field( 'simcal_license_manager', 'simcal_license_manager' );
			}
		}, 10, 2 );
	}

	/**
	 * Activate license.
	 *
	 * @since  3.0.0
	 * @internal
	 *
	 * @return void
	 */
	public function activate() {

		// Run when activation button has been clicked.
		if ( isset( $_POST['simcal_license_activate'] ) ) {

			if ( empty( $_POST['simcal_license_manager'] ) || ! wp_verify_nonce( $_POST['simcal_license_manager'], 'simcal_license_manager' ) ) {
				return;
			}

			if ( empty( $_POST['simple-calendar_settings_licenses']['add-ons'] ) && ! is_array( $_POST['simple-calendar_settings_licenses']['add-ons'] ) ) {
				return;
			}

			$status = simcal_get_license_status();

			foreach ( $_POST['simple-calendar_settings_licenses']['add-ons'] as $addon ) {

				$license = trim( simcal_get_license_key( $addon ) );

				if ( empty( $license ) ) {
					return;
				}

				// Data to send in API request.
				$activation = array(
					'edd_action' => 'activate_license',
					'license'    => $license,
					'item_name'  => urlencode( $addon ),
					'url'        => home_url()
				);

				// Call the custom API.
				$response = wp_remote_post(
					simcal_get_url( 'home' ),
					array( 'timeout' => 15, 'sslverify' => false, 'body' => $activation )
				);

				// Make sure there is a response.
				if ( is_wp_error( $response ) ) {
					return;
				}

				// Decode the license data and save.
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				if ( in_array( $license_data->license, array( 'valid', 'invalid' ) ) ) {
					$status[ $addon ] = $license_data->license;
					update_option( 'simple-calendar_licenses_status', $status );
				}
			}
		}

	}

	/**
	 * Deactivate license.
	 *
	 * @since  3.0.0
	 * @internal
	 *
	 * @return void
	 */
	public function deactivate() {

		// Run when activation button has been clicked.
		if ( isset( $_POST['simcal_license_deactivate'] ) ) {

			if ( empty( $_POST['simcal_license_manager'] ) || ! wp_verify_nonce( $_POST['simcal_license_manager'], 'simcal_license_manager' ) ) {
				return;
			}

			if ( empty( $_POST['simple-calendar_settings_licenses']['add-ons'] ) && ! is_array( $_POST['simple-calendar_settings_licenses']['add-ons'] ) ) {
				return;
			}

			$status = simcal_get_license_status();

			foreach ( $_POST['simple-calendar_settings_licenses']['add-ons'] as $addon ) {

				$license = trim( simcal_get_license_key( $addon ) );

				if ( empty( $license ) ) {
					return;
				}

				// Data to send in API request.
				$deactivation = array(
					'edd_action'=> 'deactivate_license',
					'license' 	=> $license,
					'item_name' => urlencode( $addon ),
					'url'       => home_url()
				);

				// Call the custom API.
				$response = wp_remote_post(
					simcal_get_url( 'home' ),
					array( 'timeout' => 15, 'sslverify' => false, 'body' => $deactivation )
				);

				// Make sure there is a response.
				if ( is_wp_error( $response ) ) {
					return;
				}

				// Decode the license data.
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				// $license_data->license will be either "deactivated" or "failed"
				if ( 'deactivated' == $license_data->license  ) {
					unset( $status[ $addon ] );
					update_option( 'simple-calendar_licenses_status', $status );
				}
			}
		}

	}

}
