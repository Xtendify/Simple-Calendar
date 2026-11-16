<?php
/**
 * Date Picker Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Date Picker input field.
 *
 * A special field to choose dates or date ranges.
 * Holds a date value in 'yy-mm-dd' format.
 *
 * @since 3.0.0
 */
class Date_Picker extends Field
{
	/**
	 * Select a date range.
	 *
	 * @access public
	 * @var bool
	 */
	public $range = false;

	/**
	 * Use an inline picker.
	 *
	 * @access public
	 * @var bool
	 */
	public $inline = true;

	/**
	 * Construct.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field
	 */
	public function __construct($field)
	{
		$this->range = isset($field['range']) ? ($field['range'] === true ? true : false) : false;
		$this->inline = isset($field['inline']) ? ($field['inline'] === true ? true : false) : true;

		$subtype = $this->range === true ? 'simcal-field-date-picker-range ' : '';
		$this->type_class = 'simcal-field-date-picker ' . $subtype;

		$data = [
			'data-inline' => $this->inline === true ? 'true' : 'false',
		];
		$field['attributes'] = isset($field['attributes']) ? array_merge($field['attributes'], $data) : $data;

		parent::__construct($field);
	}

	/**
	 * Output the field markup
	 *
	 * @since 3.0.0
	 */
	public function html()
	{
		if (!empty($this->description)) {
			echo '<p class="description">' . wp_kses_post($this->description) . '</p>';
		} ?>
		<div id="<?php echo esc_attr($this->id); ?>"
		     class="<?php echo esc_attr($this->class); ?>"
		     <?php echo $this->style ? 'style="' . esc_attr($this->style) . '"' : ''; ?>
			 <?php echo wp_kses_post($this->attributes); ?>>
			<?php if (false === $this->range) { ?>
				<i class="simcal-icon-calendar"></i>
				<input type="<?php echo $this->inline === true ? 'text' : 'hidden'; ?>"
				       name="<?php echo esc_attr($this->name); ?>"
				       value="<?php echo esc_attr($this->value); ?>"
				       placeholder="..."
				       readonly="readonly" />
				<?php if (true === $this->inline) {
    	$allowed_tooltip = ['i' => ['class' => [], 'data-tip' => []]];
    	echo wp_kses($this->tooltip, $allowed_tooltip);
    }} ?>
		</div>
		<?php
	}
}
