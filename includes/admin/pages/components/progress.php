<?php
/**
 * Progress component (reusable).
 *
 * Variables expected:
 * - array  $progress { percent:int, label:string, items:array<array{ id?:string, text:string, completed:bool, icon_src?:string }> }
 * - string $assets_base
 * - bool   $force_complete_styles
 */
if (!defined('ABSPATH')) {
	exit();
}

$percent = isset($progress['percent']) ? (int) $progress['percent'] : 0;
$label = isset($progress['label']) ? (string) $progress['label'] : '';
$items = isset($progress['items']) && is_array($progress['items']) ? $progress['items'] : [];

$circle_class = $percent >= 100 ? '100' : ($percent >= 67 ? '67' : '33');
?>
<div class="sc_setup_card">
	<h3 class="sc_connect_helpful_links_title">
		<?php esc_html_e('Setup progress', 'google-calendar-events'); ?>
	</h3>
	<p class="sc_text--body_b2 sc_text--dark">
		<?php esc_html_e(
  	'Complete the steps below to sync your Google Calendar with your website in real time.',
  	'google-calendar-events',
  ); ?>
	</p>

	<div class="sc_row sc_row_align_start <?php echo esc_attr(
 	$force_complete_styles ? 'sc_connect_progress_is_complete' : '',
 ); ?>">
		<div class="sc_item">
			<div
				id="sc_connect_progress_circle"
				class="sc_progress_circle sc_progress_circle--<?php echo esc_attr($circle_class); ?>"
			>
				<div class="sc_progress_circle_ring"></div>
				<div class="sc_progress_circle_fill"></div>
				<div class="sc_progress_circle_inner"></div>
				<span id="sc_connect_progress_text" class="sc_progress_circle_text">
					<?php echo esc_html($label); ?>
				</span>
			</div>

			<ul class="sc_checklist">
				<?php foreach ($items as $item) {

    	$item_id = isset($item['id']) ? (string) $item['id'] : '';
    	$item_text = isset($item['text']) ? (string) $item['text'] : '';
    	$item_completed = !empty($item['completed']);
    	$icon_src = isset($item['icon_src']) ? (string) $item['icon_src'] : $assets_base . 'check.svg';

    	$li_attrs = $item_id ? ' id="' . esc_attr($item_id) . '"' : '';
    	$li_class = 'sc_checklist_item' . ($item_completed ? ' is_completed' : '');
    	?>
					<li<?php echo $li_attrs; ?> class="<?php echo esc_attr($li_class); ?>">
						<span class="sc_checklist_checkbox">
							<?php if ($item_completed) { ?>
								<img src="<?php echo esc_url($icon_src); ?>" alt="" class="sc_checklist_icon" />
							<?php } ?>
						</span>
						<span class="sc_checklist_text"><?php echo esc_html($item_text); ?></span>
					</li>
				<?php
    } ?>
			</ul>
		</div>
	</div>
</div>

