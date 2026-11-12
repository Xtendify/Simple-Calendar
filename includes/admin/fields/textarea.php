<?php
/**
 * Textarea Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Textarea input field.
 *
 * @since 3.0.0
 */
class Textarea extends Field
{
	/**
	 * Construct.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field
	 */
	public function __construct($field)
	{
		$this->type_class = 'simcal-field-textarea';

		parent::__construct($field);

		if (!empty($field['value'])) {
			$this->value = esc_textarea($field['value']);
		}
		if (!empty($field['default'])) {
			$this->default = esc_textarea($field['default']);
		}
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since 3.0.0
	 */
	public function html()
	{
		?>
		<textarea
			name="<?php echo esc_attr($this->name); ?>"
			id="<?php echo esc_attr($this->id); ?>"
			<?php
   echo $this->class ? 'class="' . esc_attr($this->class) . '" ' : '';
   echo $this->placeholder ? 'placeholder="' . esc_attr($this->placeholder) . '" ' : '';
   echo $this->style ? 'style="' . esc_attr($this->style) . '" ' : '';
   echo wp_kses_post($this->attributes);
   ?>><?php echo esc_textarea($this->value); ?></textarea>
		<?php
  $allowed_tooltip = ['i' => ['class' => [], 'data-tip' => []]];
  echo wp_kses($this->tooltip, $allowed_tooltip);

  if (!empty($this->description)) {
  	echo '<p class="description">' . wp_kses_post($this->description) . '</p>';
  }
	}
}
