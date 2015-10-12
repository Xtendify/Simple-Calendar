<?php
/**
 * Standard Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Standard input field.
 *
 * For standard text inputs and subtypes (e.g. number, password, email...).
 *
 * @since 3.0.0
 */
class Standard extends Field {

	/**
	 * Field subtype.
	 *
	 * @var string
	 */
	public $subtype = '';

	/**
	 * Construct.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field
	 */
	public function __construct( $field ) {

		$this->subtype = isset( $field['subtype'] ) ? esc_attr( $field['subtype'] ) : 'text';
		$this->type_class = 'simcal-field-' . $this->subtype;

		parent::__construct( $field );
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		?>
		<input type="<?php echo $this->subtype; ?>"
		       name="<?php echo $this->name; ?>"
		       id="<?php echo $this->id; ?>"
		       value="<?php echo $this->value; ?>"
		       class="<?php echo $this->class; ?>"<?php
				echo $this->style ? 'style="' . $this->style . '" ' : ' ';
		        echo $this->placeholder ? 'placeholder="' . $this->placeholder . '"' : ' ';
				echo $this->attributes; ?>/>
		<?php

		echo $this->tooltip;

		if ( ! empty( $this->description ) ) {
			echo '<p class="description">' . wp_kses_post( $this->description ) . '</p>';
		}

		if ( is_string( $this->validation ) && ! empty ( $this->validation ) ) {
			echo $this->validation;
		}

	}

}
