<?php
/**
 * Select Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Select input field.
 *
 * A standard dropdown or a multiselect field.
 */
class Select extends Field {

	/**
	 * Enhanced select.
	 *
	 * @access public
	 * @var bool
	 */
	public $enhanced = false;

	/**
	 * Multiselect.
	 *
	 * @var bool
	 */
	public $multiselect = false;

	/**
	 * Allow void option.
	 *
	 * @access private
	 * @var bool
	 */
	private $allow_void = false;

	/**
	 * Construct.
	 *
	 * @param array $field
	 */
	public function __construct( $field ) {

		$class = 'simcal-field-select';

		$enhanced = isset( $field['enhanced'] ) ? $field['enhanced'] : '';
		if ( 'enhanced' == $enhanced )  {
			$this->enhanced = true;
			$class .= ' simcal-field-select-enhanced';
		}

		$multiselect = isset( $field['multiselect'] ) ? $field['multiselect'] : '';
		if ( 'multiselect' == $multiselect ) {
			$this->multiselect  = true;
			$class .= ' simcal-field-multiselect';
		}

		$this->type_class = $class;

		$allow_void = isset( $field['allow_void'] ) ? $field['allow_void'] : '';
		$this->allow_void = 'allow_void' == $allow_void ? true : false;

		parent::__construct( $field );
	}

	/**
	 * Outputs the field markup.
	 */
	public function html() {

		if ( $this->multiselect === true && ! is_array( $this->value ) ) {
			$this->value = explode( ',', $this->value );
		}

		echo 'metabox' != $this->context ? $this->tooltip : '';

		?>
		<select name="<?php echo $this->name; ?><?php if ( $this->multiselect === true ) echo '[]'; ?>"
		        id="<?php echo $this->id; ?>"
		        style="<?php echo $this->style; ?>"
		        class="<?php echo $this->class; ?>"
				<?php echo $this->attributes; ?>
				<?php echo ( $this->multiselect === true ) ? 'multiple="multiple"' : ''; ?>>
			<?php if ( $this->allow_void === true ) : ?>
				<option value="" <?php selected( '', $this->value, true ); ?>></option>
			<?php endif; ?>
			<?php foreach ( $this->options as $option => $name ) : ?>
				<option value="<?php echo $option; ?>"
					<?php if ( is_array( $this->value ) ) {
						selected( in_array( $option, $this->value ), true, true );
					} else {
						selected( $this->value, trim( strval( $option ) ), true );
					} ?>><?php echo esc_attr( $name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php

		echo 'metabox' == $this->context ? $this->tooltip : '';

		echo $this->description ? '<p class="description">' . wp_kses_post( $this->description ) . '</p>' : '';

	}

}
