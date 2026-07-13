<?php
/**
 * ICS Feed - Admin
 *
 * @package SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Feeds\Admin;

use SimpleCalendar\Admin\Metaboxes\Settings;
use SimpleCalendar\Feeds\Ics_Feed;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * ICS feed admin.
 *
 * @since 4.1.0
 */
class Ics_Feed_Admin
{
	/**
	 * ICS feed object.
	 *
	 * @access private
	 * @var Ics_Feed
	 */
	private $feed = null;

	/**
	 * Register admin hooks that must run outside the calendar edit screen.
	 *
	 * @since 4.1.0
	 */
	public static function register_hooks()
	{
		static $registered = false;

		if ($registered) {
			return;
		}

		$registered = true;

		// File uploads are handled via AJAX (see admin.js). Do not change the
		// post form enctype — that can prevent other feed fields from saving.
		add_filter('upload_mimes', [__CLASS__, 'allow_ics_mime']);
		add_action('simcal_process_settings_meta', [__CLASS__, 'process_meta'], 10, 1);
	}

	/**
	 * Hook in tabs.
	 *
	 * @since 4.1.0
	 *
	 * @param Ics_Feed $feed
	 */
	public function __construct(Ics_Feed $feed)
	{
		$this->feed = $feed;

		self::register_hooks();

		if ('calendar' == simcal_is_admin_screen()) {
			add_filter('simcal_settings_meta_tabs_li', [$this, 'add_settings_meta_tab_li'], 10, 1);
			add_action('simcal_settings_meta_panels', [$this, 'add_settings_meta_panel'], 10, 1);
		}
	}

	/**
	 * Allow ICS file uploads.
	 *
	 * @since 4.1.0
	 *
	 * @param array $mimes Allowed mime types.
	 *
	 * @return array
	 */
	public static function allow_ics_mime($mimes)
	{
		$mimes['ics'] = 'text/calendar';
		$mimes['ical'] = 'text/calendar';

		return $mimes;
	}

	/**
	 * Add a tab to the settings meta box.
	 *
	 * @since 4.1.0
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_settings_meta_tab_li($tabs)
	{
		return array_merge($tabs, [
			'ics-feed' => [
				'label' => $this->feed->name,
				'target' => 'ics-feed-settings-panel',
				'class' => ['simcal-feed-type', 'simcal-feed-type-ics-feed'],
				'icon' => 'simcal-icon-calendar',
			],
		]);
	}

	/**
	 * Add a panel to the settings meta box.
	 *
	 * @since 4.1.0
	 *
	 * @param int $post_id
	 */
	public function add_settings_meta_panel($post_id)
	{
		$ics_file = sanitize_text_field(get_post_meta($post_id, '_ics_feed_file', true));
		$ics_filename = $ics_file ? basename($ics_file) : '';
		?>
		<div id="ics-feed-settings-panel" class="simcal-panel">
			<table>
				<thead>
					<tr><th colspan="2"><?php _e('ICS Calendar Settings', 'google-calendar-events'); ?></th></tr>
				</thead>
				<?php /**
     * Add ICS feed source fields before the file upload (e.g. live URL in Pro).
     *
     * @since 4.1.0
     *
     * @param int $post_id Calendar post ID.
     */
    do_action('simcal_ics_feed_settings_fields_before', $post_id); ?>
				<tbody class="simcal-panel-section simcal-panel-section-ics-feed-file">
					<tr class="simcal-panel-field">
						<th><label for="_ics_feed_file"><?php _e('ICS File', 'google-calendar-events'); ?></label></th>
						<td>
							<input
								type="file"
								name="_ics_feed_file"
								id="_ics_feed_file"
								class="simcal-field simcal-field-file"
								accept=".ics,.ical,text/calendar"
							/>
							<p
								class="description simcal-ics-upload-status"
								id="simcal-ics-upload-status"
								<?php echo $ics_filename ? '' : 'style="display:none;"'; ?>
							>
								<?php if ($ics_filename) {
        	printf(
        		/* translators: %s: uploaded ICS filename */
        		esc_html__('Current file: %s', 'google-calendar-events'),
        		esc_html($ics_filename),
        	);
        } ?>
							</p>
							<?php if ($ics_filename) { ?>
								<label for="_ics_feed_remove_file">
									<input
										type="checkbox"
										name="_ics_feed_remove_file"
										id="_ics_feed_remove_file"
										value="1"
									/>
									<?php esc_html_e('Remove uploaded ICS file', 'google-calendar-events'); ?>
								</label>
							<?php } ?>
							<i class="simcal-icon-help simcal-help-tip" data-tip="<?php esc_attr_e(
       	'Upload an ICS/iCal file. It will be stored in wp-content/uploads/simple-calendar/ and used as the event source for this calendar.',
       	'google-calendar-events',
       ); ?>"></i>
						</td>
					</tr>
				</tbody>
				<?php
    $show_core_options = apply_filters('simcal_ics_feed_show_core_options', true, $post_id);
    if ($show_core_options) {
    	$inputs = [
    		'ics-feed' => [
    			'_ics_feed_search_query' => [
    				'type' => 'standard',
    				'subtype' => 'text',
    				'name' => '_ics_feed_search_query',
    				'id' => '_ics_feed_search_query',
    				'title' => __('Search Query', 'google-calendar-events'),
    				'tooltip' => __(
    					'Type in keywords if you only want display events that match these terms. You can use basic boolean search operators too.',
    					'google-calendar-events',
    				),
    				'placeholder' => __('Filter events to display by search terms...', 'google-calendar-events'),
    			],
    			'_ics_feed_recurring' => [
    				'type' => 'select',
    				'name' => '_ics_feed_recurring',
    				'id' => '_ics_feed_recurring',
    				'title' => __('Recurring Events', 'google-calendar-events'),
    				'tooltip' => __('Events that are programmed to repeat themselves periodically.', 'google-calendar-events'),
    				'options' => [
    					'show' => __('Show all', 'google-calendar-events'),
    					'first-only' => __('Only show first occurrence', 'google-calendar-events'),
    				],
    				'default' => 'show',
    			],
    			'_ics_feed_max_results' => [
    				'type' => 'standard',
    				'subtype' => 'number',
    				'name' => '_ics_feed_max_results',
    				'id' => '_ics_feed_max_results',
    				'title' => __('Maximum Events', 'google-calendar-events'),
    				'tooltip' => __('Limit how many events are stored and displayed from this feed.', 'google-calendar-events'),
    				'class' => ['simcal-field-small'],
    				'default' => '2500',
    				'attributes' => [
    					'min' => '0',
    					'max' => '2500',
    				],
    			],
    		],
    	];

    	Settings::print_panel_fields($inputs, $post_id);
    }

    /**
     * Add ICS feed source fields after the file upload.
     *
     * @since 4.1.0
     *
     * @param int $post_id Calendar post ID.
     */
    do_action('simcal_ics_feed_settings_fields_after', $post_id);?>
			</table>
		</div>
		<?php
	}

	/**
	 * Process meta fields.
	 *
	 * @since 4.1.0
	 *
	 * @param int $post_id
	 */
	public static function process_meta($post_id)
	{
		$feed_type = isset($_POST['_feed_type']) ? sanitize_title(wp_unslash($_POST['_feed_type'])) : '';

		if ('ics-feed' !== $feed_type) {
			return;
		}

		$show_core_options = apply_filters('simcal_ics_feed_show_core_options', true, $post_id);
		if ($show_core_options) {
			$search_query = isset($_POST['_ics_feed_search_query']) ? esc_attr($_POST['_ics_feed_search_query']) : '';
			update_post_meta($post_id, '_ics_feed_search_query', $search_query);

			$recurring = isset($_POST['_ics_feed_recurring']) ? sanitize_key($_POST['_ics_feed_recurring']) : 'show';
			$recurring = in_array($recurring, ['show', 'first-only'], true) ? $recurring : 'show';
			update_post_meta($post_id, '_ics_feed_recurring', $recurring);

			$max_results = isset($_POST['_ics_feed_max_results']) ? absint(esc_attr($_POST['_ics_feed_max_results'])) : 2500;
			update_post_meta($post_id, '_ics_feed_max_results', $max_results);
		}

		/**
		 * Process add-on ICS feed meta before core file handling.
		 *
		 * @since 4.1.0
		 *
		 * @param int $post_id Calendar post ID.
		 */
		do_action('simcal_ics_feed_process_meta', $post_id);

		if (!empty($_FILES['_ics_feed_file']['name'])) {
			$uploaded = Ics_Feed::save_uploaded_file($post_id, $_FILES['_ics_feed_file']);

			if (!is_wp_error($uploaded)) {
				simcal_delete_feed_transients($post_id);
			}

			return;
		}

		if (!empty($_POST['_ics_feed_remove_file'])) {
			Ics_Feed::delete_post_ics_file($post_id);
		}

		simcal_delete_feed_transients($post_id);
	}
}