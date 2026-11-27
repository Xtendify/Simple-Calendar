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
		$performers = Sc_Event::get_performers_meta($post->ID);
		$performer_names = [];
		foreach ($performers as $performer) {
			$performer_names[] = $performer['name'];
		}
		// One per line rather than comma separated: commas are common inside the
		// names themselves ("Acme, Inc."), and a comma-joined field would split
		// such a name into two performers on the next save.
		$performer_names = implode("\n", $performer_names);
		$performer_type =
			!empty($performers[0]['type']) && 'Organization' === $performers[0]['type'] ? 'Organization' : 'Person';
		// The raw meta, not Sc_Event::get_offer_meta(): that returns nothing for a
		// stored offer which fails validation, so the fields would render blank and
		// the next save of an unrelated field would silently delete the offer.
		$offer = get_post_meta($post->ID, '_sc_event_offer', true);
		$offer = is_array($offer) ? $offer : [];
		$offer_url = isset($offer['url']) ? (string) $offer['url'] : '';
		$offer_price = isset($offer['price']) ? (string) $offer['price'] : '';
		$offer_currency = isset($offer['priceCurrency']) ? (string) $offer['priceCurrency'] : '';
		$offer_availability = isset($offer['availability']) ? (string) $offer['availability'] : 'InStock';
		$offer_valid_from = isset($offer['validFrom']) ? (string) $offer['validFrom'] : '';

		if (!array_key_exists($offer_availability, Sc_Event::get_availability_options())) {
			$offer_availability = 'InStock';
		}
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
				<tr>
					<th scope="row">
						<label for="_sc_event_performers"><?php esc_html_e('Performer', 'google-calendar-events'); ?></label>
					</th>
					<td>
						<textarea
							class="large-text"
							name="_sc_event_performers"
							id="_sc_event_performers"
							rows="3"
							placeholder="<?php esc_attr_e("Jane Doe\nThe String Quartet", 'google-calendar-events'); ?>"
						><?php echo esc_textarea($performer_names); ?></textarea>
						<p class="description">
							<?php esc_html_e(
       	'Who performs at this event, one per line. Leave empty if the event has no performer — search engines list this field as recommended, not required.',
       	'google-calendar-events',
       ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_sc_event_performer_type">
							<?php esc_html_e('Performer Type', 'google-calendar-events'); ?>
						</label>
					</th>
					<td>
						<select name="_sc_event_performer_type" id="_sc_event_performer_type">
							<option value="Person" <?php selected($performer_type, 'Person'); ?>>
								<?php esc_html_e('Person', 'google-calendar-events'); ?>
							</option>
							<option value="Organization" <?php selected($performer_type, 'Organization'); ?>>
								<?php esc_html_e('Organization', 'google-calendar-events'); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e('Applies to every performer listed above.', 'google-calendar-events'); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_sc_event_offer_url"><?php esc_html_e('Ticket URL', 'google-calendar-events'); ?></label>
					</th>
					<td>
						<input
							type="url"
							class="large-text"
							name="_sc_event_offer_url"
							id="_sc_event_offer_url"
							value="<?php echo esc_attr($offer_url); ?>"
							placeholder="https://"
						/>
						<p class="description">
							<?php esc_html_e(
       	'Where visitors buy or register for tickets. Ticket URL, price and currency are published together, so fill in all three or leave all three empty.',
       	'google-calendar-events',
       ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_sc_event_offer_price"><?php esc_html_e('Ticket Price', 'google-calendar-events'); ?></label>
					</th>
					<td>
						<input
							type="text"
							class="small-text"
							name="_sc_event_offer_price"
							id="_sc_event_offer_price"
							value="<?php echo esc_attr($offer_price); ?>"
							inputmode="decimal"
						/>
						<p class="description">
							<?php esc_html_e('Use 0 for an event that is free to attend.', 'google-calendar-events'); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_sc_event_offer_currency">
							<?php esc_html_e('Ticket Currency', 'google-calendar-events'); ?>
						</label>
					</th>
					<td>
						<input
							type="text"
							class="small-text"
							name="_sc_event_offer_currency"
							id="_sc_event_offer_currency"
							value="<?php echo esc_attr($offer_currency); ?>"
							maxlength="3"
							placeholder="USD"
						/>
						<p class="description">
							<?php esc_html_e('Three-letter currency code, for example USD or EUR.', 'google-calendar-events'); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_sc_event_offer_availability">
							<?php esc_html_e('Ticket Availability', 'google-calendar-events'); ?>
						</label>
					</th>
					<td>
						<select name="_sc_event_offer_availability" id="_sc_event_offer_availability">
							<?php foreach (Sc_Event::get_availability_options() as $availability_value => $availability_label) {
       	echo '<option value="' .
       		esc_attr($availability_value) .
       		'"' .
       		selected($offer_availability, $availability_value, false) .
       		'>' .
       		esc_html($availability_label) .
       		'</option>';
       } ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_sc_event_offer_valid_from">
							<?php esc_html_e('Tickets On Sale From', 'google-calendar-events'); ?>
						</label>
					</th>
					<td>
						<input
							type="date"
							name="_sc_event_offer_valid_from"
							id="_sc_event_offer_valid_from"
							value="<?php echo esc_attr($offer_valid_from); ?>"
						/>
						<p class="description">
							<?php esc_html_e('Optional. When left empty, the date this event was published is used.', 'google-calendar-events'); ?>
						</p>
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

		// sanitize_textarea_field, not sanitize_text_field: the latter collapses
		// the newlines that separate the performers.
		$performer_names = isset($_POST['_sc_event_performers'])
			? sanitize_textarea_field(wp_unslash($_POST['_sc_event_performers']))
			: '';
		$performer_type = isset($_POST['_sc_event_performer_type'])
			? sanitize_text_field(wp_unslash($_POST['_sc_event_performer_type']))
			: 'Person';
		$performer_type = 'Organization' === $performer_type ? 'Organization' : 'Person';

		$performers = [];
		foreach (preg_split('/\R/', $performer_names) as $performer_name) {
			$performer_name = trim($performer_name);

			if ('' === $performer_name) {
				continue;
			}

			$performers[] = [
				'name' => $performer_name,
				'type' => $performer_type,
			];
		}

		// esc_url_raw, not sanitize_text_field: the latter strips percent-encoded
		// octets, which would quietly corrupt a ticket URL containing them.
		$offer_url =
			isset($_POST['_sc_event_offer_url']) && is_string($_POST['_sc_event_offer_url'])
				? esc_url_raw(trim(wp_unslash($_POST['_sc_event_offer_url'])))
				: '';
		$offer_price = isset($_POST['_sc_event_offer_price'])
			? trim(sanitize_text_field(wp_unslash($_POST['_sc_event_offer_price'])))
			: '';
		$offer_currency = isset($_POST['_sc_event_offer_currency'])
			? strtoupper(trim(sanitize_text_field(wp_unslash($_POST['_sc_event_offer_currency']))))
			: '';
		$offer_availability = isset($_POST['_sc_event_offer_availability'])
			? sanitize_text_field(wp_unslash($_POST['_sc_event_offer_availability']))
			: 'InStock';
		$offer_valid_from = isset($_POST['_sc_event_offer_valid_from'])
			? trim(sanitize_text_field(wp_unslash($_POST['_sc_event_offer_valid_from'])))
			: '';

		if (!array_key_exists($offer_availability, Sc_Event::get_availability_options())) {
			$offer_availability = 'InStock';
		}

		// Ticket URL, price and currency are published as one Offer, so a
		// partially filled ticket is a mistake rather than a smaller offer.
		$offer_filled = '' !== $offer_url || '' !== $offer_price || '' !== $offer_currency;

		if ($offer_filled) {
			if ('' === $offer_url) {
				$errors[] = __('Ticket URL is required when a ticket price or currency is set.', 'google-calendar-events');
			}

			if ('' === $offer_price || !is_numeric($offer_price) || (float) $offer_price < 0) {
				$errors[] = __('Ticket price must be a number of 0 or more.', 'google-calendar-events');
			}

			if (!preg_match('/^[A-Z]{3}$/', $offer_currency)) {
				$errors[] = __(
					'Ticket currency must be a three-letter currency code, for example USD.',
					'google-calendar-events',
				);
			}
		}

		if ('' !== $offer_valid_from && !self::is_valid_date($offer_valid_from)) {
			$errors[] = __('Tickets on sale from must be a valid date.', 'google-calendar-events');
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

		if (empty($performers)) {
			delete_post_meta($post_id, '_sc_event_performers');
		} else {
			update_post_meta($post_id, '_sc_event_performers', $performers);
		}

		if ($offer_filled) {
			update_post_meta($post_id, '_sc_event_offer', [
				'url' => $offer_url,
				// is_numeric() also accepts "1e3" and "+10"; normalize so a price
				// Google would reject can never reach the schema.
				'price' => (string) (float) $offer_price,
				'priceCurrency' => $offer_currency,
				'availability' => $offer_availability,
				'validFrom' => $offer_valid_from,
			]);
		} else {
			delete_post_meta($post_id, '_sc_event_offer');
		}

		update_post_meta($post_id, '_sc_event_start', $start);
		update_post_meta($post_id, '_sc_event_end', $end);
	}

	/**
	 * Whether a string is a real calendar date in Y-m-d form.
	 *
	 * @since  4.3.3
	 * @access protected
	 *
	 * @param  string $value
	 *
	 * @return bool
	 */
	protected static function is_valid_date($value)
	{
		if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
			return false;
		}

		return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
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
