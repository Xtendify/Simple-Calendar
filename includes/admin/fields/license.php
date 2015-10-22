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
		parent::__construct( $field );
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		if ( ! empty( $this->addon ) ) {

			echo '<div class="simcal-addon-manage-license-field" data-addon="' . $this->addon . '">';

			?>
			<input type="text"
			       name="<?php echo $this->name; ?>"
			       id="<?php echo $this->id; ?>"
			       value="<?php echo $this->value; ?>"
			       class="<?php echo $this->class; ?>" />
			<?php

			$status  = simcal_get_license_status( $this->addon );

			echo '<span class="simcal-addon-manage-license-buttons">';

			if ( false !== $status && 'valid' == $status ) {
				$display_activate = 'display: inline-block';
				$display_deactivate = 'display: none';
			} else {
				$display_activate = 'display: none';
				$display_deactivate = 'display: inline-block';
			}

			echo '<strong class="label" style="color:green; ' . $display_activate . ' ">' . __( '(active)', 'google-calendar-events' ) . '</strong> ';

			echo '<button class="button-secondary simcal-addon-manage-license deactivate" data-add-on="' . $this->addon . '" style="' . $display_activate . '">' .
			     '<i class="simcal-icon-spinner simcal-icon-spin" style="display: none;"></i> ' .
			     __( 'Deactivate', 'google-calendar-events' ) .
			     '</button>';

			echo '<button class="button-secondary simcal-addon-manage-license activate" data-add-on="' . $this->addon . '" style="' . $display_deactivate . '">' .
			     '<i class="simcal-icon-spinner simcal-icon-spin" style="display: none;"></i> ' .
			     __( 'Activate', 'google-calendar-events' ) .
		         '</button>';

			echo ' <span class="error" style="color: red; display: none"> </span>';

			echo '</span>';

			echo '</div>';

		}

	}

}
