<?php
/**
 * Render Connect fields using design-system markup.
 *
 * Variables expected:
 * - array  $field_defs  Field definitions (subset of simcal_connect_settings_fields()).
 * - array  $values      Values to prefill (key => value).
 * - string $assets_base Assets base URL for icons.
 */
if (!defined('ABSPATH')) {
	exit();
}

$field_defs = isset($field_defs) && is_array($field_defs) ? $field_defs : [];
$values = isset($values) && is_array($values) ? $values : [];

foreach ($field_defs as $key => $def) {

	if (!is_array($def)) {
		continue;
	}

	$id = isset($def['id']) ? (string) $def['id'] : '';
	$name = isset($def['name']) ? (string) $def['name'] : '';
	$type = isset($def['type']) ? (string) $def['type'] : 'text';
	$label = isset($def['label']) ? (string) $def['label'] : '';
	$placeholder = isset($def['placeholder']) ? (string) $def['placeholder'] : '';
	$readonly = !empty($def['readonly']);
	$copy = !empty($def['copy']);

	$value = isset($values[$key]) ? (string) $values[$key] : '';

	if (!$id || !$name) {
		continue;
	}
	?>
	<div class="sc_item_spaced">
		<label class="sc_h6" for="<?php echo esc_attr($id); ?>">
			<?php echo esc_html($label); ?>
		</label>

		<div class="sc_input_wrapper sc_input_wrapper--icons-outside sc_input_wrapper--square sc_input_full<?php echo $readonly
  	? ' sc_input_wrapper--readonly'
  	: ''; ?>">
			<input
				id="<?php echo esc_attr($id); ?>"
				type="<?php echo esc_attr($type); ?>"
				class="sc_input"
				name="<?php echo esc_attr($name); ?>"
				value="<?php echo esc_attr($value); ?>"
				<?php echo $placeholder ? 'placeholder="' . esc_attr($placeholder) . '"' : ''; ?>
				<?php echo $readonly ? 'readonly="readonly"' : ''; ?>
				autocomplete="off"
			/>

			<?php if (!$readonly && 'password' === $type) { ?>
				<button
					type="button"
					class="sc_icon--square"
					data-sc-password-toggle
					aria-controls="<?php echo esc_attr($id); ?>"
					aria-label="<?php esc_attr_e('Show', 'google-calendar-events'); ?>"
					title="<?php esc_attr_e('Show', 'google-calendar-events'); ?>"
				>
					<img src="<?php echo esc_url($assets_base . 'eye.svg'); ?>" alt="" class="sc_input_square_show" />
					<img src="<?php echo esc_url($assets_base . 'eye-white.svg'); ?>" alt="" class="sc_input_square_show_white" />
					<img src="<?php echo esc_url($assets_base . 'eye-hide.svg'); ?>" alt="" class="sc_input_square_hide" />
					<img src="<?php echo esc_url($assets_base . 'eye-hide-white.svg'); ?>" alt="" class="sc_input_square_hide_white" />
				</button>
			<?php } ?>

			<?php if ($copy) { ?>
				<button
					type="button"
					class="sc_icon--square"
					data-sc-copy-target-field="<?php echo esc_attr($id); ?>"
					aria-label="<?php esc_attr_e('Copy', 'google-calendar-events'); ?>"
					title="<?php esc_attr_e('Copy', 'google-calendar-events'); ?>"
				>
					<img src="<?php echo esc_url($assets_base . 'copy.svg'); ?>" alt="" class="sc_icon_copy" />
					<img src="<?php echo esc_url($assets_base . 'copy-white.svg'); ?>" alt="" class="sc_icon_copy_white" />
				</button>
			<?php } ?>
		</div>
	</div>
	<?php
}
