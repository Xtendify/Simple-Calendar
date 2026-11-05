<?php
/**
 * Radio Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Radio input field.
 *
 * Outputs a fieldset of radio inputs for multiple choices.
 *
 * @since 3.0.0
 */
class Radio extends Field
{
	/**
	 * Inline radios.
	 *
	 * @access private
	 * @var bool
	 */
	private $inline = false;

	/**
	 * Construct.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field
	 */
	public function __construct($field)
	{
		$this->type_class = 'simcal-field-radios';
		$this->inline = isset($field['inline']) ? ('inline' == $field['inline'] ? true : false) : false;

		parent::__construct($field);
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since 3.0.0
	 */
	public function html()
	{
		?>
		<fieldset id="<?php echo esc_attr($this->id); ?>"
		          class="<?php echo esc_attr($this->class); ?>"
			<?php echo $this->style ? 'style="' . esc_attr($this->style) . '"' : ''; ?>>
			<?php
   echo $this->description ? '<p class="description">' . wp_kses_post($this->description) . '</p>' : '';

   if (!empty($this->title)): ?>
				<legend class="screen-reader-text">
					<span><?php echo esc_html($this->title); ?></span>
				</legend>
				<?php endif;
   ?>
			<ul <?php echo $this->inline === true ? 'class="simcal-field-radios-inline"' : ''; ?>>
				<?php foreach ($this->options as $option => $name): ?>
					<li>
						<label for="<?php echo esc_attr($this->id . '-' . trim(strval($option))); ?>">
							<input name="<?php echo esc_attr($this->name); ?>"
							       id="<?php echo esc_attr($this->id . '-' . trim(strval($option))); ?>"
							       class="simcal-field simcal-field-radio"
							       type="radio"
							       value="<?php echo esc_attr(trim(strval($option))); ?>"
								<?php echo wp_kses_post($this->attributes); ?>
								<?php checked($option, $this->value, true); ?>
								/>
							<?php echo esc_attr($name); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php
   $allowed_tooltip = ['i' => ['class' => [], 'data-tip' => []]];
   echo wp_kses($this->tooltip, $allowed_tooltip);
   ?>

		</fieldset>
		<?php
	}
}
