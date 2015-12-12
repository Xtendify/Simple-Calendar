<?php
/**
 * Checkbox Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Checkbox input field.
 *
 * Outputs one single checkbox or a fieldset of checkboxes for multiple choices.
 *
 * @since 3.0.0
 */
class Checkbox extends Field {

	/**
	 * Construct.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field
	 */
	public function __construct( $field ) {
		$this->type_class = 'simcal-field-checkboxes';
		parent::__construct( $field );
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		if ( ! empty( $this->options ) && count( (array) $this->options ) > 1 ) {

			if ( ! empty( $this->description ) ) {
				echo '<p class="description">' . wp_kses_post( $this->description ) . ' ' . $this->tooltip . '</p>';
			}

			?>
			<fieldset class="<?php echo $this->class; ?>" <?php echo ! empty( $this->style ) ? 'style="' . $this->style . '"' : ''; ?>>
				<?php

				if ( ! empty( $this->title ) ) {
					echo '<legend class="screen-reader-text"><span>' . $this->title . '</span></legend>';
				}

				?>
				<ul>
					<?php foreach ( $this->options as $option => $name ) : ?>
						<li>
							<label for="<?php echo $this->id . '-' . trim( strval( $option ) ); ?>">
								<input name="<?php echo $this->name; ?>"
								       id="<?php echo $this->id . '-' . trim( strval( $option ) ); ?>"
								       class="simcal-field simcal-field-checkbox"
								       type="checkbox"
								       value="<?php echo trim( strval( $option ) ); ?>"
										<?php checked( $this->value, 'yes', true ); ?>
									<?php echo $this->attributes; ?>
									/><?php echo esc_attr( $name ); ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</fieldset>
			<?php

		} else {

			?>
			<span class="simcal-field-bool" <?php echo $this->style ? 'style="' . $this->style . '"' : ''; ?>>
				<?php if ( ! empty( $this->title ) ) : ?>
					<span class="screen-reader-text"><?php echo $this->title; ?></span>
				<?php endif; ?>
				<input name="<?php echo $this->name; ?>"
				       type="checkbox"
				       id="<?php echo $this->id; ?>"
				       class="simcal-field simcal-field-checkbox <?php echo $this->class; ?>"
				       value="yes"
					<?php checked( $this->value, 'yes', true ); ?>
					<?php echo $this->attributes; ?>/><?php _e( 'Yes', 'google-calendar-events' ); ?>
			</span>
			<?php

			echo $this->tooltip;

			if ( ! empty( $this->description ) ) {
				echo '<p class="description">' . wp_kses_post( $this->description ) . '</p>';
			}

		}

	}

}
