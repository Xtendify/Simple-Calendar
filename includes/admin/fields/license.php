<?php
/**
 * License Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License input field.
 *
 * @since 3.0.0
 */
class License extends Field {

	/**
	 * Add-on.
	 *
	 * @access private
	 * @var string
	 */
	private $addon = '';

	/**
	 * Construct.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field
	 */
	public function __construct( $field ) {
		$this->addon = isset( $field['addon'] ) ? esc_attr( $field['addon'] ) : '';
		$this->type_class = 'simcal-field-license';
		$field['validation'] = array( $this, 'update' );
		parent::__construct( $field );
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		if ( ! empty( $this->addon ) ) {

			?>
			<input type="text"
			       name="<?php echo $this->name; ?>"
			       id="<?php echo $this->id; ?>"
			       value="<?php echo $this->value; ?>"
			       class="<?php echo $this->class; ?>" />
			<?php

			$license = simcal_get_license_key( $this->addon );

			if ( ! is_null( $license ) ) {

				$status  = simcal_get_license_status( $this->addon );

				if ( false !== $status && 'valid' == $status ) {
					echo '<span style="color:green;">' . __( '(active)', 'google-calendar-events' ) . '</span>';
					echo '<input type="submit" class="button-secondary simcal-addon-manage-license deactivate" data-add-on="' . $this->addon . '" name="simcal_license_deactivate" value="' . __( 'Deactivate', 'google-calendar-events' ) . '" />';
				} else {
					echo '<input type="submit" class="button-secondary simcal-addon-manage-license activate" data-add-on="' . $this->addon . '" name="simcal_license_activate" value="' . __( 'Activate', 'google-calendar-events' ) . '" />';
				}

			}
		}

	}

	/**
	 * Update license (validation callback).
	 *
	 * @param  string $new_key
	 *
	 * @return bool
	 */
	public function update( $new_key ) {

		$old_key = simcal_get_license_key( $this->addon );

		// If there is a new license key, must activate again.
		if ( $old_key && $old_key != $new_key ) {
			$status = simcal_get_license_status();
			unset( $status[ $this->addon ] );
			update_option( 'simple-calendar_licenses_status', $status );
		}

		return true;
	}

}
