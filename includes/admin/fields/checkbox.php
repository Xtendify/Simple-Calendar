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
 */
class Checkbox extends Field {

	/**
	 * Construct.
	 *
	 * @param array $field
	 */
	public function __construct( $field ) {
		$this->type_class = 'simcal-field-checkboxes';
		parent::__construct( $field );
	}

	/**
	 * Outputs the field markup.
	 */
	public function html() {

		if ( $this->options && count( (array) $this->options ) > 1 ) {

			echo $this->description ? '<p class="description">' . wp_kses_post( $this->description ) . ' ' . $this->tooltip . '</p>' : '';

			?>
			<fieldset class="<?php echo $this->class; ?>" style="<?php echo $this->style; ?>">

				<?php if ( ! empty( $this->title ) ) : ?>
					<legend class="screen-reader-text">
						<span><?php echo $this->title; ?></span>
					</legend>
				<?php endif; ?>

				<ul>
					<?php foreach ( $this->options as $option => $name ) : ?>
						<li>
							<label for="<?php echo $this->id . '-' . trim( strval( $option ) ); ?>">
								<input
									name="<?php echo $this->name; ?>"
									id="<?php echo $this->id . '-' . trim( strval( $option ) ); ?>"
									class="simcal-field simcal-field-checkbox"
									type="checkbox"
									value="<?php echo trim( strval( $option ) ); ?>"
									<?php checked( $this->value, $option, true ); ?>
									<?php echo $this->attributes; ?>
									/><?php echo esc_attr( $name ); ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</fieldset>
			<?php

		} else {

			echo 'metabox' != $this->context ? $this->tooltip : '';

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

			echo 'metabox' == $this->context ? $this->tooltip : '';

			echo $this->description ? '<p class="description">' . wp_kses_post( $this->description ) . '</p>' : '';

		}

	}

}
