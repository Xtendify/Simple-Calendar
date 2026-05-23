<?php
/**
 * License Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * License input field.
 *
 * @since 3.0.0
 */
class License extends Field
{
	/**
	 * Add-on.
	 *
	 * @access private
	 * @var string
	 */
	private $addon = '';

	/**
	 * Optional UI variant for this field.
	 *
	 * @access private
	 * @var string
	 */
	private $ui = '';

	/**
	 * Optional icon src used by card UI.
	 *
	 * @access private
	 * @var string
	 */
	private $icon_src = '';

	/**
	 * Optional card description (card UI only).
	 *
	 * @access private
	 * @var string
	 */
	private $card_description = '';

	/**
	 * Construct.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field
	 */
	public function __construct($field)
	{
		$this->addon = isset($field['addon']) ? esc_attr($field['addon']) : '';
		$this->ui = isset($field['ui']) ? esc_attr($field['ui']) : '';
		$this->icon_src = isset($field['icon_src']) ? esc_url($field['icon_src']) : '';
		$this->card_description = isset($field['card_description']) ? wp_kses_post($field['card_description']) : '';
		$this->type_class = 'simcal-field-license';
		parent::__construct($field);
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since 3.0.0
	 */
	public function html()
	{
		if (!empty($this->addon)) {

			$status = apply_filters('simcal_addon_status_' . $this->addon, simcal_get_license_status($this->addon));

			if ($status !== 'valid') {
				$show_activate = true;
				$show_deactivate = false;
				$show_active_label = false;
				$disabled = '';
			} else {
				$show_activate = false;
				$show_deactivate = true;
				$show_active_label = true;
				$disabled = empty($this->value) ? '' : 'disabled="disabled"';
			}
			$is_card_ui = $this->ui === 'card';
			?>
			<?php if ($is_card_ui) { ?>
				<div class="sc_setup_card sc_addon_item">
					<div class="sc_addon_item_header">
						<?php if (!empty($this->icon_src)) { ?>
							<img src="<?php echo esc_url($this->icon_src); ?>" alt="" width="18" height="18" />
						<?php } ?>
						<h3 class="sc_h5 sc_addon_item_title"><?php echo esc_html($this->title); ?></h3>
					</div>
					<?php if (!empty($this->card_description)) { ?>
						<p class="sc_text--body_b3 sc_addon_item_desc"><?php echo $this->card_description; ?></p>
					<?php } ?>
			<?php } ?>

			<div class="simcal-addon-manage-license-field" data-addon="<?php echo $this->addon; ?>">
				<input
					type="text"
					<?php echo $disabled; ?>
					name="<?php echo $this->name; ?>"
					id="<?php echo $this->id; ?>"
					value="<?php echo $this->value; ?>"
					class="<?php echo trim('sc_input sc_input_muted sc_input_full ' . $this->class); ?>"
				/>

				<div class="simcal-addon-manage-license-buttons">
					<button
						class="sc_btn sc_btn--blue-loading simcal-addon-manage-license deactivate<?php echo $show_deactivate
      	? ''
      	: ' is_hidden'; ?>"
						data-add-on="<?php echo $this->addon; ?>"
					>
						<span class="sc_btn_submit"><?php esc_html_e('Deactivate', 'google-calendar-events'); ?></span>
						<span class="sc_btn_loading" aria-hidden="true">
							<span class="sc_btn--icons sc_btn--loading-icon">autorenew</span>
						</span>
						<span class="sc_btn_check" aria-hidden="true">
							<span class="sc_btn--icons sc_btn--check-icon">check</span>
						</span>
					</button>

					<button
						class="sc_btn sc_btn--blue-loading simcal-addon-manage-license activate<?php echo $show_activate
      	? ''
      	: ' is_hidden'; ?>"
						data-add-on="<?php echo $this->addon; ?>"
					>
						<span class="sc_btn_submit"><?php esc_html_e('Activate', 'google-calendar-events'); ?></span>
						<span class="sc_btn_loading" aria-hidden="true">
							<span class="sc_btn--icons sc_btn--loading-icon">autorenew</span>
						</span>
						<span class="sc_btn_check" aria-hidden="true">
							<span class="sc_btn--icons sc_btn--check-icon">check</span>
						</span>
					</button>

					<span class="error sc-error-notice is_hidden"></span>
					<strong class="label sc_license_active_label<?php echo $show_active_label ? '' : ' is_hidden'; ?>">
						<?php _e('(active)', 'google-calendar-events'); ?>
					</strong>
				</div>
			</div>

			<?php if ($is_card_ui) { ?>
				</div>
			<?php } ?>
			<?php
		}
	}
}
