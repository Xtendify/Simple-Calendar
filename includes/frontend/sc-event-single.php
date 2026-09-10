<?php
/**
 * SC Event single page layout
 *
 * @package SimpleCalendar/Frontend
 */
namespace SimpleCalendar\Frontend;

use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Events\Event;
use SimpleCalendar\Feeds\Sc_Event;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Single SC Event page.
 *
 * Wraps the event content with an optional featured image, start/end/address
 * meta, a description block, and a sidebar with category, tags, and Add to GCal.
 *
 * @since 4.2.0
 */
class Sc_Event_Single
{
	/**
	 * Prevent nested the_content filtering.
	 *
	 * @var bool
	 */
	private static $filtering = false;

	/**
	 * Hook frontend filters.
	 *
	 * @since 4.2.0
	 */
	public function __construct()
	{
		add_filter('the_content', [$this, 'filter_content'], 20);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
		add_filter('pre_render_block', [$this, 'pre_render_theme_meta_blocks'], 10, 2);
		add_filter('render_block', [$this, 'filter_theme_meta_blocks'], 10, 2);
	}

	/**
	 * Skip theme author/category meta blocks before they render.
	 *
	 * Prefer this for the TT5 "Written by" pattern so static labels never
	 * reach the page when dynamic author/terms blocks are suppressed.
	 *
	 * @since 4.2.0
	 *
	 * @param string|null $pre_render   Pre-rendered content.
	 * @param array       $parsed_block Parsed block.
	 *
	 * @return string|null
	 */
	public function pre_render_theme_meta_blocks($pre_render, $parsed_block)
	{
		if (is_admin() || !is_singular('sc-event') || !is_array($parsed_block)) {
			return $pre_render;
		}

		$name = isset($parsed_block['blockName']) ? (string) $parsed_block['blockName'] : '';

		if (
			'core/pattern' === $name &&
			isset($parsed_block['attrs']['slug']) &&
			'twentytwentyfive/hidden-written-by' === $parsed_block['attrs']['slug']
		) {
			return '';
		}

		if (
			in_array(
				$name,
				[
					'core/post-author',
					'core/post-author-name',
					'core/post-author-biography',
					'core/post-terms',
					'core/post-date',
				],
				true,
			)
		) {
			return '';
		}

		return $pre_render;
	}

	/**
	 * Remove theme author/category meta from the SC Event single template.
	 *
	 * Block themes (e.g. Twenty Twenty-Five) wrap author/category in a group
	 * with static "Written by" / "in" labels. Those labels are often raw HTML
	 * inside the group (not separate paragraph blocks), so removing only the
	 * dynamic author/terms blocks leaves the labels on screen. Strip the
	 * whole meta group instead.
	 *
	 * @since 4.2.0
	 *
	 * @param string $block_content Block HTML.
	 * @param array  $block         Parsed block.
	 *
	 * @return string
	 */
	public function filter_theme_meta_blocks($block_content, $block)
	{
		if (is_admin() || !is_singular('sc-event')) {
			return $block_content;
		}

		$name = isset($block['blockName']) ? (string) $block['blockName'] : '';

		// Twenty Twenty-Five single template embeds this pattern as the meta row.
		if (
			'core/pattern' === $name &&
			isset($block['attrs']['slug']) &&
			'twentytwentyfive/hidden-written-by' === $block['attrs']['slug']
		) {
			return '';
		}

		if (
			in_array(
				$name,
				[
					'core/post-author',
					'core/post-author-name',
					'core/post-author-biography',
					'core/post-terms',
					'core/post-date',
				],
				true,
			)
		) {
			return '';
		}

		if (!is_string($block_content) || '' === $block_content) {
			return $block_content;
		}

		$text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($block_content)));

		// Static label paragraphs left behind after author/terms are removed.
		if ('core/paragraph' === $name && preg_match('/^(Written by|in)$/i', $text)) {
			return '';
		}

		// Entire author/category meta row (labels + dynamic blocks).
		if ('core/group' === $name && $this->is_theme_author_meta_group($text, $block)) {
			return '';
		}

		return $block_content;
	}

	/**
	 * Whether a group is the theme author/category meta row.
	 *
	 * @since 4.2.0
	 *
	 * @param string $text  Flattened visible text.
	 * @param array  $block Parsed block.
	 *
	 * @return bool
	 */
	protected function is_theme_author_meta_group($text, $block)
	{
		// Leftover labels only (dynamic blocks already emptied by this filter).
		if (preg_match('/^Written by(\s+in)?$/i', $text)) {
			return true;
		}

		// "Written by … in …" with or without author/category names still present.
		if (preg_match('/^Written by\b.*\bin\b/i', $text) && mb_strlen($text) < 120) {
			return true;
		}

		$inner = isset($block['innerBlocks']) && is_array($block['innerBlocks']) ? $block['innerBlocks'] : [];

		if (empty($inner)) {
			// Labels may be raw HTML in the group, not inner blocks.
			return (bool) preg_match('/Written by/i', $text);
		}

		$has_author_or_terms = false;

		foreach ($inner as $inner_block) {
			$inner_name = isset($inner_block['blockName']) ? (string) $inner_block['blockName'] : '';

			if (
				in_array(
					$inner_name,
					['core/post-author', 'core/post-author-name', 'core/post-terms'],
					true,
				)
			) {
				$has_author_or_terms = true;
				break;
			}
		}

		// Any group that contains author/terms is the theme meta row — remove
		// the whole group so raw "Written by" / "in" HTML cannot remain.
		if ($has_author_or_terms) {
			return true;
		}

		return (bool) preg_match('/Written by/i', $text);
	}

	/**
	 * Enqueue single-event styles.
	 *
	 * @since 4.2.0
	 */
	public function enqueue_styles()
	{
		if (!is_singular('sc-event')) {
			return;
		}

		$settings = get_option('simple-calendar_settings_advanced');

		if (isset($settings['optimisation']['disable_css']) && 'yes' === $settings['optimisation']['disable_css']) {
			return;
		}

		wp_enqueue_style(
			'simcal-sc-event-single',
			SIMPLE_CALENDAR_ASSETS . 'css/sc-event-single.css',
			[],
			SIMPLE_CALENDAR_VERSION,
		);
	}

	/**
	 * Replace the event post content with the detail layout.
	 *
	 * @since 4.2.0
	 *
	 * @param string $content Post content.
	 *
	 * @return string
	 */
	public function filter_content($content)
	{
		if (self::$filtering) {
			return $content;
		}

		if (is_admin() || !is_singular('sc-event') || !in_the_loop() || !is_main_query()) {
			return $content;
		}

		global $post;

		if (!$post instanceof \WP_Post || 'sc-event' !== $post->post_type) {
			return $content;
		}

		self::$filtering = true;
		$html = $this->render($post, $content);
		self::$filtering = false;

		return $html;
	}

	/**
	 * Build the event detail markup.
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post $post    Event post.
	 * @param string   $content Existing post content.
	 *
	 * @return string
	 */
	protected function render($post, $content)
	{
		$timezone = Sc_Event::esc_timezone_string(simcal_get_wp_timezone());
		$start = absint(get_post_meta($post->ID, '_sc_event_start', true));
		$end = absint(get_post_meta($post->ID, '_sc_event_end', true));
		$location = sanitize_text_field((string) get_post_meta($post->ID, '_sc_event_location', true));

		if ($start > 0 && $end <= 0) {
			$end = $start;
		}

		$start_label = $this->format_meta_datetime($start, $end, $timezone, true);
		$end_label = $this->format_meta_datetime($end, $start, $timezone, false);
		$address = $location ? $location : '—';
		$categories = $this->get_term_links($post->ID, 'sc-event-category');
		$tags = $this->get_term_links($post->ID, 'sc-event-tag');
		$featured_image = $this->get_featured_image_html($post->ID);
		$gcal_url = $this->get_add_to_gcal_url($post, $start, $end, $timezone, $location);

		ob_start();
		?>
		<div class="simcal-event-detail">
			<?php if ($featured_image) : ?>
				<figure class="simcal-event-detail__featured">
					<?php echo $featured_image; ?>
				</figure>
			<?php endif; ?>
			<div class="simcal-event-detail__body">
				<div class="simcal-event-detail__main">
					<div class="simcal-event-detail__meta">
						<div class="simcal-event-detail__meta-item">
							<h3 class="simcal-event-detail__meta-label"><?php esc_html_e('Start Date', 'google-calendar-events'); ?></h3>
							<p class="simcal-event-detail__meta-value">
								<span class="simcal-event-detail__icon" aria-hidden="true"><?php echo $this->calendar_icon(); ?></span>
								<span><?php echo esc_html($start_label); ?></span>
							</p>
						</div>
						<div class="simcal-event-detail__meta-item">
							<h3 class="simcal-event-detail__meta-label"><?php esc_html_e('End Date', 'google-calendar-events'); ?></h3>
							<p class="simcal-event-detail__meta-value">
								<span class="simcal-event-detail__icon" aria-hidden="true"><?php echo $this->calendar_icon(); ?></span>
								<span><?php echo esc_html($end_label); ?></span>
							</p>
						</div>
						<div class="simcal-event-detail__meta-item">
							<h3 class="simcal-event-detail__meta-label"><?php esc_html_e('Address', 'google-calendar-events'); ?></h3>
							<p class="simcal-event-detail__meta-value">
								<span class="simcal-event-detail__icon" aria-hidden="true"><?php echo $this->pin_icon(); ?></span>
								<span><?php echo esc_html($address); ?></span>
							</p>
						</div>
					</div>
					<div class="simcal-event-detail__description">
						<h2 class="simcal-event-detail__description-title"><?php esc_html_e(
       	'Description',
       	'google-calendar-events',
       ); ?></h2>
						<div class="simcal-event-detail__description-content">
							<?php echo $content; ?>
						</div>
					</div>
				</div>
				<aside class="simcal-event-detail__sidebar" aria-label="<?php esc_attr_e(
    	'Details',
    	'google-calendar-events',
    ); ?>">
					<div class="simcal-event-detail__sidebar-card">
						<h2 class="simcal-event-detail__sidebar-title"><?php esc_html_e(
       	'Details',
       	'google-calendar-events',
       ); ?></h2>
						<dl class="simcal-event-detail__rows">
							<?php if ($categories) : ?>
								<div class="simcal-event-detail__row">
									<dt><?php esc_html_e('Category', 'google-calendar-events'); ?></dt>
									<dd><?php echo $categories; ?></dd>
								</div>
							<?php endif; ?>
							<?php if ($tags) : ?>
								<div class="simcal-event-detail__row">
									<dt><?php esc_html_e('Tag', 'google-calendar-events'); ?></dt>
									<dd><?php echo $tags; ?></dd>
								</div>
							<?php endif; ?>
						</dl>
						<?php if ($gcal_url) : ?>
							<p class="simcal-event-detail__gcal">
								<a
									class="simcal-event-detail__gcal-link"
									href="<?php echo esc_attr($gcal_url); ?>"
									target="_blank"
									rel="noopener noreferrer"
								><?php esc_html_e('Add to gcal', 'google-calendar-events'); ?></a>
							</p>
						<?php endif; ?>
					</div>
				</aside>
			</div>
		</div>
		<?php return ob_get_clean();
	}

	/**
	 * Featured image markup for the event detail page.
	 *
	 * @since 4.2.0
	 *
	 * @param int $post_id Event post ID.
	 *
	 * @return string
	 */
	protected function get_featured_image_html($post_id)
	{
		if (!has_post_thumbnail($post_id)) {
			return '';
		}

		return get_the_post_thumbnail(
			$post_id,
			'large',
			[
				'class' => 'simcal-event-detail__featured-image',
				'loading' => 'eager',
				'decoding' => 'async',
			],
		);
	}

	/**
	 * Format the horizontal start or end datetime.
	 *
	 * @since 4.2.0
	 *
	 * @param int    $timestamp Primary timestamp.
	 * @param int    $other     Other timestamp, used to decide whether to show the year.
	 * @param string $timezone  Site timezone.
	 * @param bool   $is_start  Whether this is the start field.
	 *
	 * @return string
	 */
	protected function format_meta_datetime($timestamp, $other, $timezone, $is_start)
	{
		if ($timestamp <= 0) {
			return '—';
		}

		$include_year = true;

		if ($other > 0) {
			$year = $this->format_in_timezone($timestamp, $timezone, 'Y');
			$other_year = $this->format_in_timezone($other, $timezone, 'Y');
			$current_year = $this->format_in_timezone(time(), $timezone, 'Y');

			// Match the reference: omit year on start when it is the current year
			// and the end is in a different year.
			if ($is_start && $year === $current_year && $year !== $other_year) {
				$include_year = false;
			} elseif (!$is_start && $year === $other_year && $year === $current_year) {
				$include_year = false;
			} elseif ($is_start && $year === $other_year && $year === $current_year) {
				$include_year = false;
			}
		}

		$format = $include_year ? 'F j, Y @ g:i a' : 'F j @ g:i a';

		return $this->format_in_timezone($timestamp, $timezone, $format);
	}

	/**
	 * Build the Add to Google Calendar URL for an SC Event post.
	 *
	 * Reuses Calendar::get_add_to_gcal_url() (same as the add-to-gcal-link
	 * template tag) with a temporary Event built from this post's meta.
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post $post     Event post.
	 * @param int      $start    Start timestamp.
	 * @param int      $end      End timestamp.
	 * @param string   $timezone Site timezone.
	 * @param string   $location Event location.
	 *
	 * @return string
	 */
	protected function get_add_to_gcal_url($post, $start, $end, $timezone, $location)
	{
		if ($start <= 0) {
			return '';
		}

		if ($end <= 0) {
			$end = $start;
		}

		$event = new Event([
			'title' => get_the_title($post),
			'description' => $post->post_content,
			'timezone' => $timezone,
			'start' => $start,
			'start_timezone' => $timezone,
			'start_location' => $location,
			'end' => $end,
			'end_timezone' => $timezone,
			'whole_day' => false,
		]);

		if (!$event->start_dt || !$event->end_dt) {
			return '';
		}

		return Calendar::get_add_to_gcal_url($event);
	}

	/**
	 * Format a Unix timestamp in a timezone.
	 *
	 * @since 4.2.0
	 *
	 * @param int    $timestamp Unix timestamp.
	 * @param string $timezone  Timezone identifier.
	 * @param string $format    PHP date format.
	 *
	 * @return string
	 */
	protected function format_in_timezone($timestamp, $timezone, $format)
	{
		if (function_exists('wp_date')) {
			try {
				return wp_date($format, $timestamp, new \DateTimeZone($timezone));
			} catch (\Exception $e) {
				return wp_date($format, $timestamp);
			}
		}

		$utc = gmdate('Y-m-d H:i:s', $timestamp);

		return get_date_from_gmt($utc, $format);
	}

	/**
	 * Linked term list, or an em dash when empty.
	 *
	 * @since 4.2.0
	 *
	 * @param int    $post_id  Event post ID.
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return string
	 */
	protected function get_term_links($post_id, $taxonomy)
	{
		$list = get_the_term_list($post_id, $taxonomy, '', ', ', '');

		if (empty($list) || is_wp_error($list)) {
			return '';
		}

		return $list;
	}

	/**
	 * Calendar outline icon.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	protected function calendar_icon()
	{
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
	}

	/**
	 * Map pin outline icon.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	protected function pin_icon()
	{
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6-5.3-6-10a6 6 0 1 1 12 0c0 4.7-6 10-6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>';
	}
}
