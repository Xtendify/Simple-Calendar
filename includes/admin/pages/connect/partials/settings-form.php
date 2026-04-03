<?php
/**
 * Shared Connect form wrapper.
 *
 * Variables expected:
 * - string $form_id
 * - string $form_action
 * - array  $form_attrs (key => value)
 * - string $settings_group
 *
 * This partial prints the opening <form> tag and `settings_fields(...)`.
 * The caller is responsible for printing the closing </form>.
 */
if (!defined('ABSPATH')) {
	exit();
}

$form_id = isset($form_id) && is_string($form_id) && $form_id ? $form_id : 'simcal-settings-page-form';
$form_action = isset($form_action) && is_string($form_action) && $form_action ? $form_action : 'options.php';
$settings_group = isset($settings_group) && is_string($settings_group) && $settings_group
	? $settings_group
	: 'simple-calendar_settings_feeds';
$form_attrs = isset($form_attrs) && is_array($form_attrs) ? $form_attrs : [];
?>
<form id="<?php echo esc_attr($form_id); ?>" method="post" action="<?php echo esc_attr($form_action); ?>"
	<?php foreach ($form_attrs as $k => $v) { ?>
		<?php if ($v === true) { ?>
			<?php echo esc_attr($k); ?>
		<?php } elseif ($v !== false && $v !== null && $v !== '') { ?>
			<?php echo esc_attr($k); ?>="<?php echo esc_attr((string) $v); ?>"
		<?php } ?>
	<?php } ?>
>
	<?php settings_fields($settings_group); ?>

