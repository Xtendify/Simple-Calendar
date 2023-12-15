<?php
/**
 * Google Calendar - Admin
 *
 * @package    SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Feeds\Admin;

use SimpleCalendar\plugin_deps\Google\Service\Exception as Google_Service_Exception;

use SimpleCalendar\Admin\Metaboxes\Settings;
use SimpleCalendar\Admin\Notice;
use SimpleCalendar\Feeds\Google;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Google Calendar feed admin.
 *
 * @since 3.0.0
 */
class Google_Admin
{
	/**
	 * Google calendar feed object.
	 *
	 * @access private
	 * @var Google
	 */
	private $feed = null;

	/**
	 * Google Api Key.
	 *
	 * @access private
	 * @var string
	 */
	private $google_api_key = '';

	/**
	 * Google Calendar id.
	 *
	 * @access private
	 * @var string
	 */
	private $google_calendar_id = '';

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 *
	 * @param Google $feed
	 * @param string $google_api_key
	 * @param string $google_calendar_id
	 */
	public function __construct(Google $feed, $google_api_key, $google_calendar_id)
	{
		$this->feed = $feed;
		$this->google_api_key = $google_api_key;
		$this->google_calendar_id = $google_calendar_id;

		$screen = simcal_is_admin_screen();

		if ('calendar' == $screen) {
			$this->test_api_key_connection($this->google_calendar_id);
			add_filter('simcal_settings_meta_tabs_li', [$this, 'add_settings_meta_tab_li'], 10, 1);
			add_action('simcal_settings_meta_panels', [$this, 'add_settings_meta_panel'], 10, 1);
		}

		add_action('simcal_process_settings_meta', [$this, 'process_meta'], 10, 1);
	}

	/**
	 * Feed settings page fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function settings_fields()
	{
		return [
			'name' => $this->feed->name,
			'description' =>
				'<div class="simcal-text-sm simcal-font-poppins simcal-font-normal simcal-text-sc_grey-100 simcal-mt-[5px]">' .
				__(
					"To read events from your public Google Calendars you'll need create a Google API key and save it here.",
					'google-calendar-events'
				) .
				'</div><br/><br/>' .
				'<em style="font-size: 14px;">' .
				sprintf(
					__(
						'<strong>Note:</strong> Calendars configured to use the <a href="%s" class=" hover:simcal-text-green-600 simcal-underline" target="_blank">Google Calendar Pro add-on</a> use a different method of authorization.',
						'google-calendar-events'
					),
					simcal_ga_campaign_url(simcal_get_url('addons'), 'core-plugin', 'settings-link')
				) .
				'</em><section class="simcal-mt-[15px]"><hr></section>',
			'fields' => [
				'api_key' => [
					'type' => 'standard',
					'subtype' => 'text',
					'placeholder' => 'Type here...',
					'class' => [
						'simcal-wide-text simcal-text-base sc-btn-input simcal-w-[80%] simcal-h-[40px] simcal-mb-[13px]',
						'ltr',
					],
					'title' => __('Google API Key', 'google-calendar-events'),
					'validation' => [$this, 'check_google_api_key'],
				],
			],
		];
	}

	/**
	 * Check if there's a Google API Key or a legacy key is being used.
	 *
	 * This method only checks if the api key setting is not empty.
	 * It is not currently possible to check or validate an API Key without performing a full request.
	 * On the settings page there are no known calendars to use for this so we can only check if there is a string.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $api_key Google API key.
	 *
	 * @return true|string
	 */
	public function check_google_api_key($api_key = '')
	{
		$message = '';
		$has_errors = false;

		if (empty($api_key)) {
			$api_key = $this->google_api_key;
			if (empty($api_key)) {
				$settings = get_option('simple-calendar_settings_feeds');
				$api_key = isset($settings['google']['api_key']) ? esc_attr($settings['google']['api_key']) : '';
			}
		}

		$message =
			'<p class="description">' .
			sprintf(
				__(
					'<a href="%s" class="simcal-underline hover:simcal-text-green-600 simcal-text-[13px]" target="_blank">Step-by-step instructions</a> ',
					'google-calendar-events'
				),
				simcal_ga_campaign_url(simcal_get_url('docs') . '/google-api-key/', 'core-plugin', 'settings-link')
			) .
			sprintf(
				__(
					'<a href="%s" class="simcal-pl-[20px] simcal-underline hover:simcal-text-green-600 simcal-text-[13px]" target="_blank">Google Developers Console</a> ',
					'google-calendar-events'
				),
				simcal_get_url('gdev-console')
			) .
			'</p>';

		return $message;
	}

	/**
	 * Add a tab to the settings meta box.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $tabs
	 *
	 * @return array
	 */
	public function add_settings_meta_tab_li($tabs)
	{
		return array_merge($tabs, [
			'google' => [
				'label' => $this->feed->name,
				'target' => 'google-settings-panel',
				'class' => ['simcal-feed-type', 'simcal-feed-type-google'],
				'icon' => 'simcal-icon-google',
			],
		]);
	}

	/**
	 * Add a panel to the settings meta box.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id
	 */
	public function add_settings_meta_panel($post_id)
	{
		$inputs = [
			$this->feed->type => [
				'_google_calendar_id' => [
					'type' => 'standard',
					'subtype' => 'text',
					'name' => '_google_calendar_id',
					'id' => '_google_calendar_id',
					'title' => __('Calendar ID', 'google-calendar-events'),
					'tooltip' => __(
						'Visit your Google Calendar account, copy your public calendar ID, then paste it here.',
						'google-calendar-events'
					),
					'placeholder' => __('Enter a valid Google Calendar ID from a public calendar', 'google-calendar-events'),
					'escaping' => [$this->feed, 'esc_google_calendar_id'],
					'validation' => [$this, 'test_api_key_connection'],
				],
				'_google_events_search_query' => [
					'type' => 'standard',
					'subtype' => 'text',
					'name' => '_google_events_search_query',
					'id' => '_google_events_search_query',
					'title' => __('Search Query', 'google-calendar-events'),
					'tooltip' => __(
						'Type in keywords if you only want display events that match these terms. You can use basic boolean search operators too.',
						'google-calendar-events'
					),
					'placeholder' => __('Filter events to display by search terms...', 'google-calendar-events'),
				],
				'_google_events_recurring' => [
					'type' => 'select',
					'name' => '_google_events_recurring',
					'id' => '_google_events_recurring',
					'title' => __('Recurring Events', 'google-calendar-events'),
					'tooltip' => __('Events that are programmed to repeat themselves periodically.', 'google-calendar-events'),
					'options' => [
						'show' => __('Show all', 'google-calendar-events'),
						'first-only' => __('Only show first occurrence', 'google-calendar-events'),
					],
				],
				'_google_events_max_results' => [
					'type' => 'standard',
					'subtype' => 'number',
					'name' => '_google_events_max_results',
					'id' => '_google_events_max_results',
					'title' => __('Maximum Events', 'google-calendar-events'),
					'tooltip' => __(
						'Google Calendar only allows to query for a maximum amount of 2500 events from a calendar each time.',
						'google-calendar-events'
					),
					'class' => ['simcal-field-small'],
					'default' => '2500',
					'attributes' => [
						'min' => '0',
						'max' => '2500',
					],
				],
			],
		]; ?>
		<div id="google-settings-panel" class="simcal-panel">
			<table>
				<thead>
					<tr><th colspan="2"><?php _e('Google Calendar Settings', 'google-calendar-events'); ?></th></tr>
				</thead>
				<?php Settings::print_panel_fields($inputs, $post_id); ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Test a connection to Google Calendar API.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $google_calendar_id
	 *
	 * @return true|string
	 */
	public function test_api_key_connection($google_calendar_id)
	{
		global $post;

		$post_id = isset($post->ID) ? $post->ID : 0;
		$feed = null;
		if ($feed_type = wp_get_object_terms($post_id, 'calendar_feed')) {
			$feed = sanitize_title(current($feed_type)->name);
		}

		$message = '';
		$error = '';
		$has_errors = false;

		$message .=
			'<p class="description">' .
			sprintf(
				__(
					'Step 1: Set the Google Calendar you want to use as <strong>"public."</strong> <a href="%1s" target="_blank">Detailed instructions</a>',
					'google-calendar-events'
				) .
					'<br />' .
					__(
						'Step 2: Copy and paste your Google Calendar ID here. <a href="%2s" target="_blank">Detailed instructions</a>',
						'google-calendar-events'
					),
				simcal_ga_campaign_url(
					simcal_get_url('docs') . '/make-google-calendar-public/',
					'core-plugin',
					'settings-link'
				),
				simcal_ga_campaign_url(simcal_get_url('docs') . '/find-google-calendar-id/', 'core-plugin', 'settings-link')
			) .
			'</p>';

		if ($post_id > 0 && !is_null($feed) && !empty($this->feed->type)) {
			$no_key_notice = new Notice([
				'id' => ['calendar_' . $post_id => 'google-no-api-key'],
				'type' => 'error',
				'screen' => 'calendar',
				'post' => $post_id,
				'dismissable' => false,
				'content' =>
					'<p>' .
					'<i class="simcal-icon-warning"></i> ' .
					sprintf(
						__(
							'Your Google Calendar events will not show up until you <a href="%s">create and save a Google API key</a>.',
							'google-calendar-events'
						),
						admin_url('edit.php?post_type=calendar&page=simple-calendar_settings&tab=feeds')
					) .
					'</p>',
			]);

			if (empty($this->google_api_key) && $feed == $this->feed->type) {
				$has_errors = true;
				$no_key_notice->add();
			} else {
				$no_key_notice->remove();

				try {
					$this->feed->make_request($google_calendar_id);
				} catch (Google_Service_Exception $e) {
					$error = $e->getMessage();
					$message = !empty($error) ? '<blockquote>' . $error . '</blockquote>' : '';
				}

				$error_notice = new Notice([
					'id' => ['calendar_' . $post_id => 'google-error-response'],
					'type' => 'error',
					'screen' => 'calendar',
					'post' => $post_id,
					'dismissable' => false,
					'content' =>
						'<p>' .
						'<i class="simcal-icon-warning"></i> ' .
						__('While trying to retrieve events, Google returned an error:', 'google-calendar-events') .
						'<br>' .
						$message .
						'<br>' .
						__(
							'Please ensure that both your Google Calendar ID and API Key are valid and that the Google Calendar you want to display is public.',
							'google-calendar-events'
						) .
						'</p>',
				]);

				if (!empty($error) && $feed == $this->feed->type) {
					$error_notice->add();
					$has_errors = true;
				} else {
					$error_notice->remove();
					$has_errors = false;
				}
			}
		}

		return $message;
	}

	/**
	 * Process meta fields.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id
	 */
	public function process_meta($post_id)
	{
		$calendar_id = isset($_POST['_google_calendar_id']) ? base64_encode(esc_attr($_POST['_google_calendar_id'])) : '';
		update_post_meta($post_id, '_google_calendar_id', $calendar_id);

		$search_query = isset($_POST['_google_events_search_query']) ? esc_attr($_POST['_google_events_search_query']) : '';
		update_post_meta($post_id, '_google_events_search_query', $search_query);

		$recurring = isset($_POST['_google_events_recurring']) ? sanitize_key($_POST['_google_events_recurring']) : 'show';
		update_post_meta($post_id, '_google_events_recurring', $recurring);

		$max_results_sanitize = isset($_POST['_google_events_max_results'])
			? esc_attr($_POST['_google_events_max_results'])
			: '2500';
		$max_results = absint($max_results_sanitize);
		update_post_meta($post_id, '_google_events_max_results', $max_results);

		$this->test_api_key_connection($calendar_id);
	}
}
