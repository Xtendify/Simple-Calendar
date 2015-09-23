<?php
/**
 * Textarea Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Textarea input field.
 */
class Textarea extends Field {

	/**
	 * Construct.
	 *
	 * @param array $field
	 */
	public function __construct( $field ) {
		$this->type_class = 'simcal-field-textarea';
		parent::__construct( $field );
		$this->value      = isset( $field['value'] )   ? esc_textarea( $field['value'] )   : '';
		$this->default    = isset( $field['default'] ) ? esc_textarea( $field['default'] ) : '';
	}

	/**
	 * Outputs the field markup.
	 */
	public function html() {

		echo 'metabox' != $this->context ? $this->tooltip : '';

		?>
		<textarea
			name="<?php echo $this->name; ?>"
			id="<?php echo $this->id; ?>"
			<?php echo $this->class ? 'class="'  . $this->class . '" ' : ''; ?>
			<?php echo $this->placeholder ? 'placeholder="'  . $this->placeholder . '" ' : ''; ?>
			<?php echo $this->style ? 'style="'  . $this->style . '" ' : ''; ?>
			<?php echo $this->attributes; ?>
			><?php echo $this->value;  ?></textarea>
		<?php

		echo 'metabox' == $this->context ? $this->tooltip : '';

		echo $this->description ? '<p class="description">' . wp_kses_post( $this->description ) . '</p>' : '';

	}

}
