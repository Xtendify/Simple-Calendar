<?php
/**
 * Datetime Formatter Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Datetime formatter field.
 *
 * A special field to choose a format for date and time.
 */
class Datetime_Format extends Field
{
	/**
	 * Datetime controls.
	 *
	 * @access public
	 * @var string 'date' or 'time'
	 */
	public $subtype = '';

	/**
	 * Timestamp.
	 *
	 * @access private
	 * @var int
	 */
	private $timestamp = 0;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field
	 */
	public function __construct($field)
	{
		$this->subtype = isset($field['subtype']) ? $field['subtype'] : '';
		$this->type_class = 'simcal-field-datetime-format simcal-field-' . $this->subtype . '-format-field';
		$this->timestamp = mktime(13, 10, 00, 1, 1, intval(date('Y', time())) + 1);

		parent::__construct($field);

		if (empty($this->value)) {
			if ('date' == $this->subtype) {
				$this->value = 'l, d F Y';
			}
			if ('time' == $this->subtype) {
				$this->value = 'G:i a';
			}
		}
	}

	/**
	 * Output field markup.
	 *
	 * @since 3.0.0
	 */
	public function html()
	{
		$id = $this->id ? ' id="' . esc_attr($this->id) . '" ' : '';
		$class = $this->class ? ' class="' . esc_attr($this->class) . '" ' : '';
		$style = $this->style ? ' style="' . esc_attr($this->style) . '" ' : '';
		$attr = wp_kses_post($this->attributes);
		?>
		<div <?php echo wp_kses_post($id . $class . $style . $attr); ?>>
			<?php
   if (!empty($this->description)) {
   	echo '<p class="description">' . wp_kses_post($this->description) . '</p>';
   }

   $matches = array_unique(str_split($this->value));

   if ('date' == $this->subtype) {
   	$this->print_date($matches);
   }

   if ('time' == $this->subtype) {
   	$this->print_time($matches);
   }
   ?>
			<input type="hidden"
			       name="<?php echo esc_attr($this->name); ?>"
			       value="<?php echo esc_attr(trim($this->value)); ?>" />
			<span>
				<em><?php esc_html_e('Preview', 'google-calendar-events'); ?>:</em>&nbsp;&nbsp;
				<code><?php echo esc_html(date_i18n($this->value, $this->timestamp)); ?></code>
			</span>
		</div>
		<?php
	}

	/**
	 * Print date input.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  array $matches
	 */
	private function print_date($matches)
	{
		$date = ['weekday' => '', 'divider' => '', 'day' => '', 'month' => '', 'year' => ''];

		foreach ($matches as $match) {
			if (in_array($match, ['D', 'l'])) {
				$this->weekday();
				unset($date['weekday']);
			} elseif (in_array($match, ['d', 'j'])) {
				$this->day();
				unset($date['day']);
			} elseif (in_array($match, ['F', 'M', 'm', 'n'])) {
				$this->month();
				unset($date['month']);
			} elseif (in_array($match, ['y', 'Y'])) {
				$this->year();
				unset($date['year']);
			} elseif (in_array($match, ['.', ',', ':', '/', '-'])) {
				$this->divider();
				unset($date['divider']);
			}
		}

		$this->print_fields($date);
	}

	/**
	 * Print time input.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  array $matches
	 */
	private function print_time($matches)
	{
		$time = ['hours' => '', 'divider' => '', 'minutes' => '', 'meridiem' => ''];

		foreach ($matches as $match) {
			if (in_array($match, ['h', 'H', 'g', 'G'])) {
				$this->hours();
				unset($time['hours']);
			} elseif (in_array($match, ['i'])) {
				$this->minutes();
				unset($time['minutes']);
			} elseif (in_array($match, ['A', 'a'])) {
				$this->meridiem();
				unset($time['meridiem']);
			} elseif (in_array($match, ['.', ',', ':', '/', '-'])) {
				$this->divider();
				unset($time['divider']);
			}
		}

		$this->print_fields($time);
	}

	/**
	 * Print input fields.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  $fields
	 */
	private function print_fields($fields)
	{
		if (!empty($fields) && is_array($fields)) {
			foreach ($fields as $func => $v) {
				if (method_exists($this, $func)) {
					$this->$func();
				}
			}
		}
	}

	/**
	 * Print weekday input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function weekday()
	{
		?>
		<div>
			<label for="<?php echo esc_attr($this->id); ?>-weekday">
				<?php esc_html_e('Weekday', 'google-calendar-events'); ?>
				<select name="" id="<?php echo esc_attr($this->id); ?>-weekday">
					<option value="" data-preview=""></option>
					<option value="D" <?php selected('D', strpbrk('D', $this->value)); ?> data-preview="<?php echo esc_attr(date_i18n(
 	'D',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date_i18n('D', $this->timestamp)); ?></option>
					<option value="l" <?php selected('l', strpbrk('l', $this->value)); ?> data-preview="<?php echo esc_attr(date_i18n(
 	'l',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date_i18n('l', $this->timestamp)); ?></option>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Print day input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function day()
	{
		?>
		<div>
			<label for="<?php echo esc_attr($this->id); ?>-day">
				<?php esc_html_e('Day', 'google-calendar-events'); ?>
				<select name="" id="<?php echo esc_attr($this->id); ?>-day">
					<option value="" data-preview=""></option>
					<option value="j" <?php selected('j', strpbrk('j', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'j',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('j', $this->timestamp)); ?></option>
					<option value="d" <?php selected('d', strpbrk('d', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'd',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('d', $this->timestamp)); ?></option>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Print month input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function month()
	{
		?>
		<div>
			<label for="<?php echo esc_attr($this->id); ?>-month">
				<?php esc_html_e('Month', 'google-calendar-events'); ?>
				<select name="" id="<?php echo esc_attr($this->id); ?>-month">
					<option value="" data-preview=""></option>
					<option value="F" <?php selected('F', strpbrk('F', $this->value)); ?> data-preview="<?php echo esc_attr(date_i18n(
 	'F',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date_i18n('F', $this->timestamp)); ?></option>
					<option value="M" <?php selected('M', strpbrk('M', $this->value)); ?> data-preview="<?php echo esc_attr(date_i18n(
 	'M',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date_i18n('M', $this->timestamp)); ?></option>
					<option value="m" <?php selected('m', strpbrk('m', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'm',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('m', $this->timestamp)); ?></option>
					<option value="n" <?php selected('n', strpbrk('n', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'n',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('n', $this->timestamp)); ?></option>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Print year input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function year()
	{
		?>
		<div>
			<label for="<?php echo esc_attr($this->id); ?>-year">
				<?php esc_html_e('Year', 'google-calendar-events'); ?>
				<select name="" id="<?php echo esc_attr($this->id); ?>-year">
					<option value="" data-preview=""></option>
					<option value="Y" <?php selected('Y', strpbrk('Y', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'Y',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('Y', $this->timestamp)); ?></option>
					<option value="y" <?php selected('y', strpbrk('y', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'y',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('y', $this->timestamp)); ?></option>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Print hours input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function hours()
	{
		?>
		<div>
			<label for="<?php echo esc_attr($this->id); ?>-hours">
				<?php esc_html_e('Hours', 'google-calendar-events'); ?>
				<select name="" id="<?php echo esc_attr($this->id); ?>-hours">
					<option value="" data-preview=""></option>
					<option value="g" <?php selected('g', strpbrk('g', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'g',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('g', $this->timestamp) . ' (12h)'); ?></option>
					<option value="G" <?php selected('G', strpbrk('G', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'G',
 	$this->timestamp - 43200,
 )); ?>"><?php echo esc_html(date('G', $this->timestamp - 43200) . ' (24h)'); ?></option>
					<option value="h" <?php selected('h', strpbrk('h', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'h',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('h', $this->timestamp) . ' (12h)'); ?></option>
					<option value="H" <?php selected('H', strpbrk('H', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'H',
 	$this->timestamp - 43200,
 )); ?>"><?php echo esc_html(date('H', $this->timestamp - 43200) . ' (24h)'); ?></option>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Print minutes input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function minutes()
	{
		?>
		<div>
			<label for="<?php echo esc_attr($this->id); ?>-minutes">
				<?php esc_html_e('Minutes', 'google-calendar-events'); ?>
				<select name="" id="<?php echo esc_attr($this->id); ?>-minutes">
					<option value="" data-preview=""></option>
					<option value="i" <?php selected('i', strpbrk('i', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'i',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('i', $this->timestamp)); ?></option>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Print meridiem input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function meridiem()
	{
		?>
		<div>
			<label for="<?php echo esc_attr($this->id); ?>-meridiem">
				<?php esc_html_e('Meridiem', 'google-calendar-events'); ?>
				<select name="" id="<?php echo esc_attr($this->id); ?>-meridiem">
					<option value="" data-preview=""></option>
					<option value="a" <?php selected('a', strpbrk('a', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'a',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('a', $this->timestamp)); ?></option>
					<option value="A" <?php selected('A', strpbrk('A', $this->value)); ?> data-preview="<?php echo esc_attr(date(
 	'A',
 	$this->timestamp,
 )); ?>"><?php echo esc_html(date('A', $this->timestamp)); ?></option>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Print divider input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function divider()
	{
		?>
		<div>
			<label for="<?php echo esc_attr($this->id); ?>-divider">
				<?php echo esc_html_x('Divider', 'A character to separate two elements', 'google-calendar-events'); ?>
				<select name="" id="<?php echo esc_attr($this->id); ?>-divider">
					<option value="" data-preview=""></option>
					<option value="."  <?php selected('.', strpbrk('.', $this->value)); ?> data-preview="."  data-trim="true">.</option>
					<option value=", " <?php selected(',', strpbrk(',', $this->value)); ?> data-preview=", " data-trim="true">,</option>
					<option value=":"  <?php selected(':', strpbrk(':', $this->value)); ?> data-preview=":"  data-trim="true">:</option>
					<option value="-"  <?php selected('-', strpbrk('-', $this->value)); ?> data-preview="-"  data-trim="true">-</option>
					<option value="/"  <?php selected('/', strpbrk('/', $this->value)); ?> data-preview="/"  data-trim="true">/</option>
				</select>
			</label>
		</div>
		<?php
	}
}
