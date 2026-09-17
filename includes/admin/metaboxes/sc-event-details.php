<?php
/**
 * SC Event Details Meta Box
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Metaboxes;

use SimpleCalendar\Abstracts\Meta_Box;
use SimpleCalendar\Feeds\Sc_Event;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * SC Event details.
 *
 * Meta box for location and start/end datetime on the SC Event edit screen.
 *
 * @since 4.2.0
 */
class Sc_Event_Details implements Meta_Box
{
	/**
	 * Transient key prefix for validation errors.
	 *
	 * @var string
	 */
	const VALIDATION_TRANSIENT = 'simcal_sc_event_validation_';

	/**
	 * Photon geocoding endpoint.
	 *
	 * @var string
	 */
	const PHOTON_API = 'https://photon.komoot.io/api/';

	/**
	 * Output the meta box markup.
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post $post
	 */
	public static function html($post)
	{
		wp_nonce_field('simcal_save_data', 'simcal_meta_nonce');

		$timezone = Sc_Event::esc_timezone_string(simcal_get_wp_timezone());
		$location = sanitize_text_field((string) get_post_meta($post->ID, '_sc_event_location', true));
		$lat = get_post_meta($post->ID, '_sc_event_lat', true);
		$lng = get_post_meta($post->ID, '_sc_event_lng', true);
		$lat = is_numeric($lat) ? (string) $lat : '';
		$lng = is_numeric($lng) ? (string) $lng : '';
		$start = Sc_Event::format_datetime(absint(get_post_meta($post->ID, '_sc_event_start', true)), $timezone);
		$end = Sc_Event::format_datetime(absint(get_post_meta($post->ID, '_sc_event_end', true)), $timezone);
		?>
		<p class="description">
			<?php printf(
   	/* translators: %s: WordPress timezone */
   	esc_html__('Date and time values use the site timezone (%s).', 'google-calendar-events'),
   	esc_html($timezone),
   ); ?>
		</p>
		<div id="simcal-sc-event-details-errors" class="notice notice-error inline" style="display:none;" role="alert">
			<p></p>
		</div>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="_sc_event_location"><?php esc_html_e('Location', 'google-calendar-events'); ?></label>
					</th>
					<td>
						<div class="simcal-sc-event-location-wrap">
							<input
								type="text"
								class="large-text"
								name="_sc_event_location"
								id="_sc_event_location"
								value="<?php echo esc_attr($location); ?>"
								autocomplete="off"
								aria-autocomplete="list"
								aria-controls="simcal-sc-event-location-suggestions"
								aria-expanded="false"
								placeholder="<?php esc_attr_e('Start typing an address…', 'google-calendar-events'); ?>"
							/>
							<span
								id="simcal-sc-event-location-loader"
								class="simcal-sc-event-location-loader"
								aria-hidden="true"
								hidden
							></span>
							<input type="hidden" name="_sc_event_lat" id="_sc_event_lat" value="<?php echo esc_attr($lat); ?>" />
							<input type="hidden" name="_sc_event_lng" id="_sc_event_lng" value="<?php echo esc_attr($lng); ?>" />
							<ul
								id="simcal-sc-event-location-suggestions"
								class="simcal-sc-event-location-suggestions"
								role="listbox"
								hidden
							></ul>
						</div>
						<p class="description">
							<?php esc_html_e('Select a suggestion to save the address and map coordinates.', 'google-calendar-events'); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_sc_event_start">
							<?php esc_html_e('Start Date/Time', 'google-calendar-events'); ?>
							<span class="required">*</span>
						</label>
					</th>
					<td>
						<input
							type="datetime-local"
							name="_sc_event_start"
							id="_sc_event_start"
							value="<?php echo esc_attr($start); ?>"
							required
							aria-required="true"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_sc_event_end">
							<?php esc_html_e('End Date/Time', 'google-calendar-events'); ?>
							<span class="required">*</span>
						</label>
					</th>
					<td>
						<input
							type="datetime-local"
							name="_sc_event_end"
							id="_sc_event_end"
							value="<?php echo esc_attr($end); ?>"
							required
							aria-required="true"
						/>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Validate and save the meta box fields.
	 *
	 * @since 4.2.0
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public static function save($post_id, $post)
	{
		$timezone = Sc_Event::esc_timezone_string(simcal_get_wp_timezone());

		$location = isset($_POST['_sc_event_location'])
			? sanitize_text_field(wp_unslash($_POST['_sc_event_location']))
			: '';
		$lat = isset($_POST['_sc_event_lat']) ? sanitize_text_field(wp_unslash($_POST['_sc_event_lat'])) : '';
		$lng = isset($_POST['_sc_event_lng']) ? sanitize_text_field(wp_unslash($_POST['_sc_event_lng'])) : '';
		$start_raw = isset($_POST['_sc_event_start']) ? sanitize_text_field(wp_unslash($_POST['_sc_event_start'])) : '';
		$end_raw = isset($_POST['_sc_event_end']) ? sanitize_text_field(wp_unslash($_POST['_sc_event_end'])) : '';

		$start = Sc_Event::parse_datetime($start_raw, $timezone);
		$end = Sc_Event::parse_datetime($end_raw, $timezone);

		$errors = [];

		if ($start <= 0) {
			$errors[] = __('Start date/time is required.', 'google-calendar-events');
		}

		if ($end <= 0) {
			$errors[] = __('End date/time is required.', 'google-calendar-events');
		}

		if ($start > 0 && $end > 0 && $end <= $start) {
			$errors[] = __('End date/time must be greater than the start date/time.', 'google-calendar-events');
		}

		if (!empty($errors)) {
			set_transient(self::VALIDATION_TRANSIENT . get_current_user_id(), $errors, 45);
			return;
		}

		delete_transient(self::VALIDATION_TRANSIENT . get_current_user_id());

		$lat = is_numeric($lat) ? (float) $lat : '';
		$lng = is_numeric($lng) ? (float) $lng : '';

		if ('' === $location) {
			$lat = '';
			$lng = '';
		}

		update_post_meta($post_id, '_sc_event_location', $location);

		if ('' === $lat || '' === $lng) {
			delete_post_meta($post_id, '_sc_event_lat');
			delete_post_meta($post_id, '_sc_event_lng');
		} else {
			update_post_meta($post_id, '_sc_event_lat', $lat);
			update_post_meta($post_id, '_sc_event_lng', $lng);
		}

		update_post_meta($post_id, '_sc_event_start', $start);
		update_post_meta($post_id, '_sc_event_end', $end);
	}

	/**
	 * AJAX: location suggestions via Photon.
	 *
	 * @since 4.2.0
	 */
	public static function ajax_geocode_suggest()
	{
		if (!current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'forbidden'], 403);
		}

		check_ajax_referer('simcal', 'nonce');

		$query = isset($_REQUEST['q']) ? sanitize_text_field(wp_unslash($_REQUEST['q'])) : '';
		$query = trim($query);

		if (mb_strlen($query) < 2) {
			wp_send_json_success(['results' => []]);
		}

		wp_send_json_success(['results' => self::fetch_photon_suggestions($query)]);
	}

	/**
	 * Query Photon for place suggestions.
	 *
	 * @since 4.2.0
	 *
	 * @param string $query Search text.
	 *
	 * @return array
	 */
	protected static function fetch_photon_suggestions($query)
	{
		$url = add_query_arg(
			[
				'q' => $query,
				'limit' => 6,
				'lang' => self::geocode_lang(),
			],
			self::PHOTON_API,
		);

		$response = wp_remote_get($url, [
			'timeout' => 8,
			'headers' => [
				'Accept' => 'application/json',
				'User-Agent' => self::geocode_user_agent(),
			],
		]);

		if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
			return [];
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (empty($body['features']) || !is_array($body['features'])) {
			return [];
		}

		$results = [];

		foreach ($body['features'] as $feature) {
			if (!is_array($feature)) {
				continue;
			}

			$coords = isset($feature['geometry']['coordinates']) ? $feature['geometry']['coordinates'] : null;
			$props = isset($feature['properties']) && is_array($feature['properties']) ? $feature['properties'] : [];

			if (!is_array($coords) || count($coords) < 2 || !is_numeric($coords[0]) || !is_numeric($coords[1])) {
				continue;
			}

			$label = self::format_photon_label($props);

			if ('' === $label) {
				continue;
			}

			$results[] = [
				'label' => $label,
				'lat' => (float) $coords[1],
				'lng' => (float) $coords[0],
				'source' => 'photon',
			];
		}

		return $results;
	}

	/**
	 * Build a human-readable Photon address label.
	 *
	 * @since 4.2.0
	 *
	 * @param array $props Photon properties.
	 *
	 * @return string
	 */
	protected static function format_photon_label($props)
	{
		$parts = [];

		if (!empty($props['name'])) {
			$parts[] = (string) $props['name'];
		}

		$street = '';
		if (!empty($props['street'])) {
			$street = (string) $props['street'];
			if (!empty($props['housenumber'])) {
				$street = (string) $props['housenumber'] . ' ' . $street;
			}
		} elseif (!empty($props['housenumber'])) {
			$street = (string) $props['housenumber'];
		}

		if ('' !== $street && (!isset($parts[0]) || 0 !== strcasecmp($parts[0], $street))) {
			$parts[] = $street;
		}

		foreach (['city', 'town', 'village', 'municipality', 'county', 'state', 'country'] as $key) {
			if (empty($props[$key])) {
				continue;
			}

			$value = (string) $props[$key];

			if (!in_array($value, $parts, true)) {
				$parts[] = $value;
			}
		}

		$label = implode(', ', array_filter(array_map('trim', $parts)));

		return sanitize_text_field($label);
	}

	/**
	 * Language hint for Photon.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	protected static function geocode_lang()
	{
		$locale = function_exists('determine_locale') ? determine_locale() : get_locale();
		$lang = strtolower(substr((string) $locale, 0, 2));

		return preg_match('/^[a-z]{2}$/', $lang) ? $lang : 'en';
	}

	/**
	 * User-Agent for Photon requests.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	protected static function geocode_user_agent()
	{
		$version = defined('SIMPLE_CALENDAR_VERSION') ? SIMPLE_CALENDAR_VERSION : '4.2.0';

		return 'SimpleCalendar/' . $version . '; ' . home_url('/');
	}

	/**
	 * Show validation errors after a failed save.
	 *
	 * @since 4.2.0
	 */
	public static function admin_notices()
	{
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;

		if (!$screen || !in_array($screen->id, ['sc-event', 'edit-sc-event'], true)) {
			return;
		}

		$key = self::VALIDATION_TRANSIENT . get_current_user_id();
		$errors = get_transient($key);

		if (empty($errors) || !is_array($errors)) {
			return;
		}

		delete_transient($key);

		echo '<div class="notice notice-error is-dismissible"><p><strong>';
		esc_html_e('Event details could not be saved:', 'google-calendar-events');
		echo '</strong></p><ul style="list-style:disc;margin-left:1.5em;">';

		foreach ($errors as $error) {
			echo '<li>' . esc_html($error) . '</li>';
		}

		echo '</ul></div>';
	}
}
