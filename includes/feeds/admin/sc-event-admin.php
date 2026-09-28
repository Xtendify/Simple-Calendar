<?php
/**
 * SC Event Feed - Admin
 *
 * @package SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Feeds\Admin;

use SimpleCalendar\Admin\Metaboxes\Settings;
use SimpleCalendar\Feeds\Sc_Event;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * SC Event feed admin.
 *
 * @since 4.2.0
 */
class Sc_Event_Admin
{
	/**
	 * SC Event feed object.
	 *
	 * @access private
	 * @var Sc_Event
	 */
	private $feed = null;

	/**
	 * Register admin hooks for the calendar edit screen.
	 *
	 * @since 4.2.0
	 */
	public static function register_hooks()
	{
		static $registered = false;

		if ($registered) {
			return;
		}

		$registered = true;

		add_action('simcal_process_settings_meta', [__CLASS__, 'process_meta'], 10, 1);
	}

	/**
	 * Hook in tabs.
	 *
	 * @since 4.2.0
	 *
	 * @param Sc_Event $feed
	 */
	public function __construct(Sc_Event $feed)
	{
		$this->feed = $feed;

		self::register_hooks();

		if ('calendar' !== simcal_is_admin_screen()) {
			return;
		}

		add_filter('simcal_settings_meta_tabs_li', [$this, 'add_settings_meta_tab_li'], 5, 1);
		add_action('simcal_settings_meta_panels', [$this, 'add_settings_meta_panel'], 10, 1);
	}

	/**
	 * Add a tab to the settings meta box.
	 *
	 * Inserted at priority 5 so the tab sits immediately after Appearance.
	 * Visibility is still controlled by the existing feed-type tab JS.
	 *
	 * @since 4.2.0
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_settings_meta_tab_li($tabs)
	{
		return array_merge($tabs, [
			'sc-event' => [
				'label' => __('SC Event', 'google-calendar-events'),
				'target' => 'sc-event-settings-panel',
				'class' => ['simcal-feed-type', 'simcal-feed-type-sc-event'],
				'icon' => 'simcal-icon-event',
			],
		]);
	}

	/**
	 * Add a panel to the settings meta box.
	 *
	 * @since 4.2.0
	 *
	 * @param int $post_id
	 */
	public function add_settings_meta_panel($post_id)
	{
		$options = [
			'' => __('All categories', 'google-calendar-events'),
		];

		$terms = get_terms([
			'taxonomy' => 'sc-event-category',
			'hide_empty' => false,
		]);

		if (!is_wp_error($terms) && !empty($terms)) {
			foreach ($terms as $term) {
				$options[(string) $term->term_id] = $term->name;
			}
		}

		$inputs = [
			'sc-event' => [
				'_sc_event_category' => [
					'type' => 'select',
					'name' => '_sc_event_category',
					'id' => '_sc_event_category',
					'title' => __('SC Event Category', 'google-calendar-events'),
					'tooltip' => __(
						'Choose which SC Event category this calendar should display. Leave this set to "All categories" to include every published SC Event, including events with no category.',
						'google-calendar-events',
					),
					'options' => $options,
					'enhanced' => 'enhanced',
					'attributes' => [
						'data-noresults' => __('No event categories found.', 'google-calendar-events'),
					],
				],
			],
		];
		?>
		<div id="sc-event-settings-panel" class="simcal-panel">
			<table>
				<thead>
					<tr><th colspan="2"><?php esc_html_e('SC Event Settings', 'google-calendar-events'); ?></th></tr>
				</thead>
				<?php Settings::print_panel_fields($inputs, $post_id); ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Process meta fields.
	 *
	 * @since 4.2.0
	 *
	 * @param int $post_id
	 */
	public static function process_meta($post_id)
	{
		$feed_type = isset($_POST['_feed_type']) ? sanitize_title(wp_unslash($_POST['_feed_type'])) : '';

		if ('sc-event' !== $feed_type) {
			return;
		}

		$category = isset($_POST['_sc_event_category']) ? absint(wp_unslash($_POST['_sc_event_category'])) : 0;

		if ($category > 0 && !term_exists($category, 'sc-event-category')) {
			$category = 0;
		}

		update_post_meta($post_id, '_sc_event_category', $category);
	}
}
