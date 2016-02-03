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
 *
 * @since 3.0.0
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
	 * @since 3.0.0
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
			$this->multiselect = true;
			$class .= ' simcal-field-multiselect';
		}

		if ( isset( $field['default'] ) ) {
			$this->default = $field['default'];
		}

		$this->type_class = $class;

		$allow_void = isset( $field['allow_void'] ) ? $field['allow_void'] : '';
		$this->allow_void = 'allow_void' == $allow_void ? true : false;

		parent::__construct( $field );
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		if ( $this->multiselect === true && ! is_array( $this->value ) ) {
			$this->value = explode( ',', $this->value );
		}

		if ( $this->default ) {
			if ( empty( $this->value ) || $this->value == '' ) {
				$this->value = $this->default;
			}
		}

		?>
		<select name="<?php echo $this->name; ?><?php if ( $this->multiselect === true ) { echo '[]'; } ?>"
		        id="<?php echo $this->id; ?>"
		        style="<?php echo $this->style; ?>"
		        class="<?php echo $this->class; ?>"
				<?php echo $this->attributes; ?>
				<?php echo ( $this->multiselect === true ) ? ' multiple="multiple"' : ''; ?>>
			<?php

			if ( $this->allow_void === true ) {
				echo '<option value=""' . selected( '', $this->value, false ) . '></option>';
			}

			foreach ( $this->options as $option => $name ) {
				if ( is_array( $this->value ) ) {
					$selected =	selected( in_array( $option, $this->value ), true, false );
				} else {
					$selected = selected( $this->value, trim( strval( $option ) ), false );
				}
				echo '<option value="' . $option . '" ' . $selected . '>' . esc_attr( $name ) . '</option>';
			}

			?>
		</select>
		<?php

		echo $this->tooltip;

		if ( ! empty( $this->description ) ) {
			echo '<p class="description">' . wp_kses_post( $this->description ) . '</p>';
		}

	}

}
