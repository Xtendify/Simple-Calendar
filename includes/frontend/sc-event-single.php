<?php
/**
 * SC Event single page layout
 *
 * @package SimpleCalendar/Frontend
 */
namespace SimpleCalendar\Frontend;

use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Events\Event;
use SimpleCalendar\Events\Event_Schema;
use SimpleCalendar\Feeds\Sc_Event;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Single SC Event page.
 *
 * Wraps the event content with an optional featured image, start/end meta,
 * a description block, and a sidebar with address, category, tags, and Add to GCal.
 * Sections are pluggable via actions/filters (similar to WooCommerce product pages).
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
		add_action('wp_head', [$this, 'print_schema_json_ld'], 5);
		add_filter('pre_render_block', [$this, 'pre_render_theme_meta_blocks'], 10, 2);
		add_filter('render_block', [$this, 'filter_theme_meta_blocks'], 10, 2);

		$this->register_template_hooks();
	}

	/**
	 * Register default event detail template parts (WooCommerce-style).
	 *
	 * Themes/plugins can remove or reorder these with remove_action()/add_action().
	 *
	 * @since 4.2.0
	 */
	protected function register_template_hooks()
	{
		/**
		 * Before main column / featured image area.
		 *
		 * Default priorities:
		 * - 10 featured image
		 */
		add_action('simcal_before_event_detail_summary', [$this, 'template_featured_image'], 10, 2);

		/**
		 * Main column (summary).
		 *
		 * Default priorities:
		 * - 10 start/end meta
		 * - 20 description
		 */
		add_action('simcal_event_detail_summary', [$this, 'template_meta'], 10, 2);
		add_action('simcal_event_detail_summary', [$this, 'template_description'], 20, 2);

		/**
		 * Sidebar.
		 *
		 * Default priorities:
		 * - 10 address / map
		 * - 20 details (category, tags, add to gcal)
		 */
		add_action('simcal_event_detail_sidebar', [$this, 'template_location'], 10, 2);
		add_action('simcal_event_detail_sidebar', [$this, 'template_details'], 20, 2);
	}

	/**
	 * Output schema.org Event JSON-LD on SC Event single pages.
	 *
	 * @since 4.2.0
	 */
	public function print_schema_json_ld()
	{
		if (is_admin() || !is_singular('sc-event')) {
			return;
		}

		$post = get_queried_object();

		if (!($post instanceof \WP_Post) || 'sc-event' !== $post->post_type) {
			return;
		}

		$event = $this->build_event_from_post($post);

		if (!($event instanceof Event)) {
			return;
		}

		$schema = new Event_Schema($event);
		$script = $schema->get_json_ld_script();

		if ('' !== $script) {
			echo $script;
		}
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

			if (in_array($inner_name, ['core/post-author', 'core/post-author-name', 'core/post-terms'], true)) {
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

		if (!($post instanceof \WP_Post) || 'sc-event' !== $post->post_type) {
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

		$lat = get_post_meta($post->ID, '_sc_event_lat', true);
		$lng = get_post_meta($post->ID, '_sc_event_lng', true);
		$lat = is_numeric($lat) ? (float) $lat : 0;
		$lng = is_numeric($lng) ? (float) $lng : 0;

		$context = [
			'post' => $post,
			'timezone' => $timezone,
			'start' => $start,
			'end' => $end,
			'location' => $location,
			'address' => $location ? $location : '—',
			'lat' => $lat,
			'lng' => $lng,
			'start_label' => $this->format_meta_datetime($start, $end, $timezone, true),
			'end_label' => $this->format_meta_datetime($end, $start, $timezone, false),
			'categories' => $this->get_term_links($post->ID, 'sc-event-category'),
			'tags' => $this->get_term_links($post->ID, 'sc-event-tag'),
			'featured_image' => $this->get_featured_image_html($post->ID),
			'gcal_url' => $this->get_add_to_gcal_url($post, $start, $end, $timezone, $location),
			'content' => $content,
		];

		/**
		 * Filter event detail template context.
		 *
		 * @since 4.2.0
		 *
		 * @param array    $context Template data.
		 * @param \WP_Post $post    Event post.
		 */
		$context = apply_filters('simcal_event_detail_context', $context, $post);

		/**
		 * Filter event detail wrapper CSS classes.
		 *
		 * @since 4.2.0
		 *
		 * @param string[] $classes CSS classes.
		 * @param \WP_Post $post    Event post.
		 * @param array    $context Template data.
		 */
		$classes = apply_filters('simcal_event_detail_classes', ['simcal-event-detail'], $post, $context);
		$classes = array_map('sanitize_html_class', array_filter((array) $classes));

		ob_start();

		/**
		 * Before the event detail wrapper.
		 *
		 * @since 4.2.0
		 *
		 * @param \WP_Post $post    Event post.
		 * @param array    $context Template data.
		 */
		do_action('simcal_before_event_detail', $post, $context);
		?>
		<div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
			<?php /**
    * Before the main/summary column.
    *
    * @since 4.2.0
    *
    * @param \WP_Post $post    Event post.
    * @param array    $context Template data.
    */
   do_action('simcal_before_event_detail_summary', $post, $context); ?>
			<div class="simcal-event-detail__body">
				<div class="simcal-event-detail__main">
					<?php /**
      * Event detail main/summary column.
      *
      * Default callbacks: featured image (5), meta (10), description (20).
      *
      * @since 4.2.0
      *
      * @param \WP_Post $post    Event post.
      * @param array    $context Template data.
      */
     do_action('simcal_event_detail_summary', $post, $context); ?>
				</div>
				<?php /**
     * Before the sidebar.
     *
     * @since 4.2.0
     *
     * @param \WP_Post $post    Event post.
     * @param array    $context Template data.
     */
    do_action('simcal_before_event_detail_sidebar', $post, $context); ?>
				<aside class="simcal-event-detail__sidebar" aria-label="<?php esc_attr_e(
    	'Event sidebar',
    	'google-calendar-events',
    ); ?>">
					<?php /**
      * Event detail sidebar.
      *
      * Default callbacks: location (10), details (20).
      *
      * @since 4.2.0
      *
      * @param \WP_Post $post    Event post.
      * @param array    $context Template data.
      */
     do_action('simcal_event_detail_sidebar', $post, $context); ?>
				</aside>
				<?php /**
     * After the sidebar.
     *
     * @since 4.2.0
     *
     * @param \WP_Post $post    Event post.
     * @param array    $context Template data.
     */
    do_action('simcal_after_event_detail_sidebar', $post, $context); ?>
			</div>
		</div>
		<?php
  /**
   * After the event detail wrapper.
   *
   * @since 4.2.0
   *
   * @param \WP_Post $post    Event post.
   * @param array    $context Template data.
   */
  do_action('simcal_after_event_detail', $post, $context);

  $html = ob_get_clean();

  /**
   * Filter the full event detail HTML.
   *
   * @since 4.2.0
   *
   * @param string   $html    Rendered HTML.
   * @param \WP_Post $post    Event post.
   * @param array    $context Template data.
   */
  return apply_filters('simcal_event_detail_html', $html, $post, $context);
	}

	/**
	 * Template: featured image.
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post $post    Event post.
	 * @param array    $context Template data.
	 */
	public function template_featured_image($post, $context)
	{
		$featured_image = isset($context['featured_image']) ? $context['featured_image'] : '';

		if (!$featured_image) {
			return;
		}
		?>
		<figure class="simcal-event-detail__featured">
			<?php echo $featured_image; ?>
		</figure>
		<?php
	}

	/**
	 * Template: start/end meta row.
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post $post    Event post.
	 * @param array    $context Template data.
	 */
	public function template_meta($post, $context)
	{
		$start_label = isset($context['start_label']) ? $context['start_label'] : '—';
		$end_label = isset($context['end_label']) ? $context['end_label'] : '—';

		/**
		 * Before the start/end meta section.
		 *
		 * @since 4.2.0
		 *
		 * @param \WP_Post $post    Event post.
		 * @param array    $context Template data.
		 */
		do_action('simcal_before_event_detail_meta', $post, $context);
		?>
		<div class="simcal-event-detail__meta">
			<div class="simcal-event-detail__meta-item">
				<h3 class="simcal-event-detail__meta-label"><?php esc_html_e('Start', 'google-calendar-events'); ?></h3>
				<p class="simcal-event-detail__meta-value">
					<span class="simcal-event-detail__icon" aria-hidden="true"><?php echo $this->calendar_icon(); ?></span>
					<span><?php echo esc_html($start_label); ?></span>
				</p>
			</div>
			<div class="simcal-event-detail__meta-item">
				<h3 class="simcal-event-detail__meta-label"><?php esc_html_e('End', 'google-calendar-events'); ?></h3>
				<p class="simcal-event-detail__meta-value">
					<span class="simcal-event-detail__icon" aria-hidden="true"><?php echo $this->calendar_icon(); ?></span>
					<span><?php echo esc_html($end_label); ?></span>
				</p>
			</div>
			<?php /**
    * Inside the start/end meta section (after defaults).
    *
    * @since 4.2.0
    *
    * @param \WP_Post $post    Event post.
    * @param array    $context Template data.
    */
   do_action('simcal_event_detail_meta', $post, $context); ?>
		</div>
		<?php /**
   * After the start/end meta section.
   *
   * @since 4.2.0
   *
   * @param \WP_Post $post    Event post.
   * @param array    $context Template data.
   */
  do_action('simcal_after_event_detail_meta', $post, $context);
	}

	/**
	 * Template: description block.
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post $post    Event post.
	 * @param array    $context Template data.
	 */
	public function template_description($post, $context)
	{
		$content = isset($context['content']) ? $context['content'] : '';

		/**
		 * Before the description section.
		 *
		 * @since 4.2.0
		 *
		 * @param \WP_Post $post    Event post.
		 * @param array    $context Template data.
		 */
		do_action('simcal_before_event_detail_description', $post, $context);
		?>
		<div class="simcal-event-detail__description">
			<h2 class="simcal-event-detail__description-title"><?php esc_html_e('Description', 'google-calendar-events'); ?></h2>
			<div class="simcal-event-detail__description-content">
				<?php /**
     * Filter description HTML before output.
     *
     * @since 4.2.0
     *
     * @param string   $content Description HTML.
     * @param \WP_Post $post    Event post.
     * @param array    $context Template data.
     */
    echo apply_filters('simcal_event_detail_description_html', $content, $post, $context); ?>
			</div>
		</div>
		<?php /**
   * After the description section.
   *
   * @since 4.2.0
   *
   * @param \WP_Post $post    Event post.
   * @param array    $context Template data.
   */
  do_action('simcal_after_event_detail_description', $post, $context);
	}

	/**
	 * Template: address / map sidebar card.
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post $post    Event post.
	 * @param array    $context Template data.
	 */
	public function template_location($post, $context)
	{
		$address = isset($context['address']) ? $context['address'] : '—';
		$location = isset($context['location']) ? $context['location'] : '';
		$lat = !empty($context['lat']) ? (float) $context['lat'] : 0;
		$lng = !empty($context['lng']) ? (float) $context['lng'] : 0;

		/**
		 * Before the location sidebar card.
		 *
		 * @since 4.2.0
		 *
		 * @param \WP_Post $post    Event post.
		 * @param array    $context Template data.
		 */
		do_action('simcal_before_event_detail_location', $post, $context);
		?>
		<div class="simcal-event-detail__sidebar-card simcal-event-detail__location">
			<h2 class="simcal-event-detail__sidebar-title"><?php esc_html_e('Address', 'google-calendar-events'); ?></h2>
			<p class="simcal-event-detail__location-address">
				<span class="simcal-event-detail__icon" aria-hidden="true"><?php echo $this->pin_icon(); ?></span>
				<span><?php echo esc_html($address); ?></span>
			</p>
			<?php
   $map_html = sprintf(
   	'<div class="simcal-event-detail__map" data-simcal-event-map%s></div>',
   	$lat && $lng
   		? sprintf(
   			' data-lat="%1$s" data-lng="%2$s" data-address="%3$s"',
   			esc_attr((string) $lat),
   			esc_attr((string) $lng),
   			esc_attr($location),
   		)
   		: '',
   );

   /**
    * Filter the map placeholder HTML.
    *
    * @since 4.2.0
    *
    * @param string   $map_html Map markup.
    * @param \WP_Post $post     Event post.
    * @param array    $context  Template data.
    */
   echo apply_filters('simcal_event_detail_map_html', $map_html, $post, $context);

   /**
    * Inside the location card (after address/map).
    *
    * @since 4.2.0
    *
    * @param \WP_Post $post    Event post.
    * @param array    $context Template data.
    */
   do_action('simcal_event_detail_location', $post, $context);
   ?>
		</div>
		<?php /**
   * After the location sidebar card.
   *
   * @since 4.2.0
   *
   * @param \WP_Post $post    Event post.
   * @param array    $context Template data.
   */
  do_action('simcal_after_event_detail_location', $post, $context);
	}

	/**
	 * Template: details sidebar card (category, tags, add to gcal).
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post $post    Event post.
	 * @param array    $context Template data.
	 */
	public function template_details($post, $context)
	{
		$categories = isset($context['categories']) ? $context['categories'] : '';
		$tags = isset($context['tags']) ? $context['tags'] : '';
		$gcal_url = isset($context['gcal_url']) ? $context['gcal_url'] : '';

		/**
		 * Before the details sidebar card.
		 *
		 * @since 4.2.0
		 *
		 * @param \WP_Post $post    Event post.
		 * @param array    $context Template data.
		 */
		do_action('simcal_before_event_detail_details', $post, $context);
		?>
		<div class="simcal-event-detail__sidebar-card">
			<h2 class="simcal-event-detail__sidebar-title"><?php esc_html_e('Details', 'google-calendar-events'); ?></h2>
			<dl class="simcal-event-detail__rows">
				<?php if ($categories): ?>
					<div class="simcal-event-detail__row">
						<dt><?php esc_html_e('Category', 'google-calendar-events'); ?></dt>
						<dd><?php echo $categories; ?></dd>
					</div>
				<?php endif; ?>
				<?php if ($tags): ?>
					<div class="simcal-event-detail__row">
						<dt><?php esc_html_e('Tag', 'google-calendar-events'); ?></dt>
						<dd><?php echo $tags; ?></dd>
					</div>
				<?php endif; ?>
				<?php /**
     * Extra detail rows inside the Details card.
     *
     * @since 4.2.0
     *
     * @param \WP_Post $post    Event post.
     * @param array    $context Template data.
     */
    do_action('simcal_event_detail_details_rows', $post, $context); ?>
			</dl>
			<?php if ($gcal_url): ?>
				<p class="simcal-event-detail__gcal">
					<a
						class="simcal-event-detail__gcal-link"
						href="<?php echo esc_attr($gcal_url); ?>"
						target="_blank"
						rel="noopener noreferrer"
					><?php /**
      * Filter Add to Google Calendar link text.
      *
      * @since 4.2.0
      *
      * @param string   $text    Link text.
      * @param \WP_Post $post    Event post.
      * @param array    $context Template data.
      */
     echo esc_html(
     	apply_filters('simcal_event_detail_gcal_text', __('Add to GCal', 'google-calendar-events'), $post, $context),
     ); ?></a>
				</p>
			<?php endif; ?>
			<?php /**
    * Inside the details card (after rows / gcal).
    *
    * @since 4.2.0
    *
    * @param \WP_Post $post    Event post.
    * @param array    $context Template data.
    */
   do_action('simcal_event_detail_details', $post, $context); ?>
		</div>
		<?php /**
   * After the details sidebar card.
   *
   * @since 4.2.0
   *
   * @param \WP_Post $post    Event post.
   * @param array    $context Template data.
   */
  do_action('simcal_after_event_detail_details', $post, $context);
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

		return get_the_post_thumbnail($post_id, 'large', [
			'class' => 'simcal-event-detail__featured-image',
			'loading' => 'eager',
			'decoding' => 'async',
		]);
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
		$event = $this->build_event_from_post($post, $start, $end, $timezone, $location);

		if (!($event instanceof Event)) {
			return '';
		}

		return Calendar::get_add_to_gcal_url($event);
	}

	/**
	 * Convert an SC Event post into an Event object for shared helpers.
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post    $post     Event post.
	 * @param int|null    $start    Optional start timestamp.
	 * @param int|null    $end      Optional end timestamp.
	 * @param string|null $timezone Optional timezone.
	 * @param string|null $location Optional location.
	 *
	 * @return Event|null
	 */
	protected function build_event_from_post($post, $start = null, $end = null, $timezone = null, $location = null)
	{
		if (!($post instanceof \WP_Post) || 'sc-event' !== $post->post_type) {
			return null;
		}

		$timezone = null !== $timezone ? $timezone : Sc_Event::esc_timezone_string(simcal_get_wp_timezone());
		$start = null !== $start ? absint($start) : absint(get_post_meta($post->ID, '_sc_event_start', true));
		$end = null !== $end ? absint($end) : absint(get_post_meta($post->ID, '_sc_event_end', true));
		$location =
			null !== $location
				? sanitize_text_field((string) $location)
				: sanitize_text_field((string) get_post_meta($post->ID, '_sc_event_location', true));
		$lat = get_post_meta($post->ID, '_sc_event_lat', true);
		$lng = get_post_meta($post->ID, '_sc_event_lng', true);

		if ($start <= 0) {
			return null;
		}

		if ($end <= 0) {
			$end = $start;
		}

		$cover_image = '';
		if (has_post_thumbnail($post->ID)) {
			$cover_image = (string) get_the_post_thumbnail_url($post->ID, 'full');
		}

		$location_data = [
			'name' => $location,
			'address' => $location,
			'lat' => is_numeric($lat) ? (float) $lat : 0,
			'lng' => is_numeric($lng) ? (float) $lng : 0,
		];

		$event = new Event([
			'uid' => (string) $post->ID,
			'type' => 'sc-event',
			'title' => get_the_title($post),
			'description' => $post->post_content,
			'link' => get_permalink($post),
			'timezone' => $timezone,
			'start' => $start,
			'start_timezone' => $timezone,
			'start_location' => $location_data,
			'end' => $end,
			'end_timezone' => $timezone,
			'end_location' => $location_data,
			'whole_day' => false,
			'meta' => [
				'cover_image' => $cover_image,
			],
		]);

		if (!$event->start_dt || !$event->end_dt) {
			return null;
		}

		return $event;
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
