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
 *
 * @since 3.0.0
 */
class Textarea extends Field {

	/**
	 * Construct.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field
	 */
	public function __construct( $field ) {

		$this->type_class = 'simcal-field-textarea';

		parent::__construct( $field );

		if ( ! empty( $field['value'] ) ) {
			$this->value = esc_textarea( $field['value'] );
		}
		if ( ! empty( $field['default'] ) ) {
			$this->default = esc_textarea( $field['default'] );
		}
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		?>
		<textarea
			name="<?php echo $this->name; ?>"
			id="<?php echo $this->id; ?>"
			<?php
			echo $this->class ? 'class="'  . $this->class . '" ' : '';
			echo $this->placeholder ? 'placeholder="'  . $this->placeholder . '" ' : '';
			echo $this->style ? 'style="'  . $this->style . '" ' : '';
			echo $this->attributes;
			?>><?php echo $this->value;  ?></textarea>
		<?php

		echo $this->tooltip;

		if ( ! empty( $this->description ) ) {
			echo '<p class="description">' . wp_kses_post( $this->description ) . '</p>';
		}

	}

}
