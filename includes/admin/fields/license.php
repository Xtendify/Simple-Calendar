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
		$this->addon      = isset( $field['addon'] ) ? esc_attr( $field['addon'] ) : '';
		$this->type_class = 'simcal-field-license';
		parent::__construct( $field );
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		if ( ! empty( $this->addon ) ) {

			$status = apply_filters( 'simcal_addon_status_' . $this->addon, simcal_get_license_status( $this->addon ) );

			if ( $status !== 'valid' ) {
				$display_activate   = 'display: inline-block';
				$display_deactivate = 'display: none';
				$active             = 'valid' == $status ? 'display: block' : 'display: none';
				$disabled           = '';
			} else {
				$display_activate   = $active = 'display: none';
				$display_deactivate = 'display: inline-block';
				$disabled           = empty( $this->value ) ? '' : 'disabled="disabled"';
			}

			?>
			<div class="simcal-addon-manage-license-field" data-addon="<?php echo $this->addon; ?>">

				<input type="text" <?php echo $disabled; ?>
				       name="<?php echo $this->name; ?>"
				       id="<?php echo $this->id; ?>"
				       value="<?php echo $this->value; ?>"
				       class="<?php echo $this->class; ?>" />

				<span class="simcal-addon-manage-license-buttons">

					<button class="button-secondary simcal-addon-manage-license deactivate" data-add-on="<?php echo $this->addon; ?>" style="<?php echo $display_deactivate; ?>">
				        <i class="simcal-icon-spinner simcal-icon-spin" style="display: none;"></i><?php _e( 'Deactivate', 'google-calendar-events' ); ?>
					</button>

					<button class="button-secondary simcal-addon-manage-license activate" data-add-on="<?php echo $this->addon; ?>" style="<?php echo $display_activate; ?>">
						<i class="simcal-icon-spinner simcal-icon-spin" style="display: none;"></i><?php _e( 'Activate', 'google-calendar-events' ); ?>
					</button>

					<span class="error" style="color: red; display: none"> </span>

					<strong class="label" style="color:green; <?php echo $active; ?>"> <?php _e( '(active)', 'google-calendar-events' ); ?></strong>

				</span>

			</div>
			<?php

		}

	}

}
