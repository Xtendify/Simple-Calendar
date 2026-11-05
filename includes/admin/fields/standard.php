<?php
/**
 * Standard Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Standard input field.
 *
 * For standard text inputs and subtypes (e.g. number, password, email...).
 *
 * @since 3.0.0
 */
class Standard extends Field
{
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
	public function __construct($field)
	{
		$this->subtype = isset($field['subtype']) ? esc_attr($field['subtype']) : 'text';
		$this->type_class = 'simcal-field-' . $this->subtype;

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
		<input type="<?php echo esc_attr($this->subtype); ?>"
		       name="<?php echo esc_attr($this->name); ?>"
		       id="<?php echo esc_attr($this->id); ?>"
		       value="<?php echo esc_attr($this->value); ?>"
		       class="<?php echo esc_attr($this->class); ?>"<?php
echo $this->style ? 'style="' . esc_attr($this->style) . '" ' : ' ';
echo $this->placeholder ? 'placeholder="' . esc_attr($this->placeholder) . '"' : ' ';
echo wp_kses_post($this->attributes);
?>/>
		<?php
  $allowed_tooltip = ['i' => ['class' => [], 'data-tip' => []]];
  echo wp_kses($this->tooltip, $allowed_tooltip);

  if (!empty($this->description)) {
  	echo '<p class="description">' . wp_kses_post($this->description) . '</p>';
  }

  if (is_string($this->validation) && !empty($this->validation)) {
  	echo wp_kses_post($this->validation);
  }
	}
}
