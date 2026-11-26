<?php
/**
 * Event Schema
 *
 * @package SimpleCalendar/Events
 */

namespace SimpleCalendar\Events;

use SimpleCalendar\plugin_deps\Carbon\Carbon;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Event Schema microdata builder.
 *
 * Builds always-on schema.org Event meta independent of event template tags.
 *
 * @since 4.1.2
 */
class Event_Schema
{
	/**
	 * Event.
	 *
	 * @access private
	 * @var Event
	 */
	private $event;

	/**
	 * Constructor.
	 *
	 * @since 4.1.2
	 *
	 * @param Event $event
	 */
	public function __construct(Event $event)
	{
		$this->event = $event;
	}

	/**
	 * Build always-on Event schema microdata meta.
	 *
	 * Ensures recommended Event fields are present on the Event node even when
	 * the event template omits the related content tags.
	 *
	 * @since  4.1.2
	 *
	 * @return string
	 */
	public function get_schema_meta()
	{
		$event = $this->event;
		$html = '';

		$name = trim(wp_strip_all_tags((string) $event->title));
		if ('' !== $name) {
			$html .= '<meta itemprop="name" content="' . esc_attr($name) . '" />';
		}

		if ($event->start_dt instanceof Carbon) {
			$html .= '<meta itemprop="startDate" content="' . esc_attr($event->start_dt->toIso8601String()) . '" />';
		}

		if ($event->end_dt instanceof Carbon) {
			$html .= '<meta itemprop="endDate" content="' . esc_attr($event->end_dt->toIso8601String()) . '" />';
		}

		if (!empty($event->link)) {
			$html .= '<meta itemprop="url" content="' . esc_url($event->link) . '" />';
		}

		return $html . $this->get_supplemental_schema_meta();
	}

	/**
	 * Build only the Event schema meta that event template tags never supply.
	 *
	 * Calendar views wrap each event in a schema.org/Event item and fill the
	 * core properties (name, startDate, endDate, url) from the event template
	 * tags. Those tags have no equivalent for eventStatus, eventAttendanceMode,
	 * image, performer or offers, so Google reports them as missing. This
	 * returns that supplementary set only, to avoid emitting a second
	 * conflicting value for a property the template already provides.
	 *
	 * @since  4.3.3
	 *
	 * @return string
	 */
	public function get_supplemental_schema_meta()
	{
		$html = '<meta itemprop="eventStatus" content="' . esc_attr($this->get_schema_event_status()) . '" />';

		$html .= '<meta itemprop="eventAttendanceMode" content="' . esc_attr($this->get_schema_attendance_mode()) . '" />';

		/**
		 * Filter the image URL used for Event schema.
		 *
		 * Lets a site supply a real event image when the event source has none,
		 * instead of falling back to the site icon or logo.
		 *
		 * @since 4.3.3
		 *
		 * @param string $image_url Resolved image URL, empty when none was found.
		 * @param Event  $event     Event the schema is built for.
		 */
		$image_url = apply_filters('simcal_event_schema_image_url', $this->get_schema_image_url(), $this->event);
		if (!empty($image_url)) {
			$html .= '<meta itemprop="image" content="' . esc_url($image_url) . '" />';
		}

		$html .= $this->get_schema_virtual_location_meta();

		// Skip the organizer only when the [organizer] template tag will actually
		// render one: that tag outputs nothing unless the event source supplied
		// organizer data, so otherwise the Event node would have no organizer at all.
		$organizer = $this->event->get_organizer();
		if (empty($organizer) || !$this->template_has_tag('organizer')) {
			$html .= $this->get_schema_organizer_meta();
		}

		$html .= $this->get_schema_performer_meta();
		$html .= $this->get_schema_offers_meta();

		return $html;
	}

	/**
	 * Allowed HTML for schema microdata built by this class.
	 *
	 * Shared by every caller that echoes get_schema_meta() output, so the
	 * allowlist cannot drift between the SC Event single template and the
	 * calendar views.
	 *
	 * @since  4.3.3
	 *
	 * @return array
	 */
	public static function get_allowed_html()
	{
		return [
			'meta' => [
				'itemprop' => true,
				'content' => true,
			],
			'span' => [
				'itemprop' => true,
				'itemscope' => true,
				'itemtype' => true,
				'style' => true,
			],
		];
	}

	/**
	 * Resolve schema.org eventAttendanceMode for the event.
	 *
	 * @since  4.1.2
	 * @access private
	 *
	 * @return string
	 */
	private function get_schema_attendance_mode()
	{
		$has_location = !empty($this->event->start_location['address']);
		$has_virtual_location = '' !== $this->get_schema_virtual_url();
		if ($has_location && $has_virtual_location) {
			return 'https://schema.org/MixedEventAttendanceMode';
		}
		if ($has_virtual_location) {
			return 'https://schema.org/OnlineEventAttendanceMode';
		}
		return 'https://schema.org/OfflineEventAttendanceMode';
	}

	/**
	 * Resolve schema.org eventStatus for the event.
	 *
	 * Google treats a missing eventStatus as EventScheduled, so that stays the
	 * default, but a status reported by the event source is honoured: a
	 * cancelled event must not keep advertising itself as going ahead.
	 *
	 * @since  4.3.3
	 * @access private
	 *
	 * @return string
	 */
	private function get_schema_event_status()
	{
		$status = isset($this->event->meta['status']) ? strtolower(trim((string) $this->event->meta['status'])) : '';

		switch ($status) {
			case 'cancelled':
			case 'canceled':
				return 'https://schema.org/EventCancelled';
			case 'postponed':
				return 'https://schema.org/EventPostponed';
			case 'rescheduled':
				return 'https://schema.org/EventRescheduled';
			case 'moved-online':
			case 'moved_online':
				return 'https://schema.org/EventMovedOnline';
			default:
				// Google Calendar's "tentative" is still a scheduled event.
				return 'https://schema.org/EventScheduled';
		}
	}

	/**
	 * Resolve the event's virtual meeting URL.
	 *
	 * Prefers the meeting URL the event source reported, because the event
	 * link itself is a permalink to the event page rather than to the meeting.
	 * Falls back to recognising a well-known meeting host in the event link.
	 *
	 * @since  4.3.3
	 * @access private
	 *
	 * @return string Empty string when the event has no virtual location.
	 */
	private function get_schema_virtual_url()
	{
		$virtual_url = isset($this->event->meta['virtual_url']) ? trim((string) $this->event->meta['virtual_url']) : '';

		if ('' !== $virtual_url) {
			return $virtual_url;
		}

		$link = !empty($this->event->link) ? $this->event->link : '';

		return $this->is_virtual_event_url($link) ? $link : '';
	}

	/**
	 * Build VirtualLocation microdata for an online event.
	 *
	 * Google requires an online event's location to be a VirtualLocation
	 * carrying the meeting URL. Only emitted when the event has no physical
	 * address and the event template has no [location] tag of its own, so this
	 * never adds a second, conflicting location to the Event node.
	 *
	 * @since  4.3.3
	 * @access private
	 *
	 * @return string
	 */
	private function get_schema_virtual_location_meta()
	{
		$virtual_url = $this->get_schema_virtual_url();
		if ('' === $virtual_url) {
			return '';
		}

		if (!empty($this->event->start_location['address']) || $this->template_has_tag('location')) {
			return '';
		}

		return '<span itemprop="location" itemscope itemtype="https://schema.org/VirtualLocation" style="display:none;">' .
			'<meta itemprop="url" content="' .
			esc_url($virtual_url) .
			'" />' .
			'</span>';
	}

	/**
	 * Whether a URL looks like a virtual meeting link.
	 *
	 * @since  4.1.2
	 * @access private
	 *
	 * @param  string $url
	 *
	 * @return bool
	 */
	private function is_virtual_event_url($url)
	{
		if (empty($url) || !is_string($url)) {
			return false;
		}

		$host = wp_parse_url($url, PHP_URL_HOST);
		if (empty($host)) {
			return false;
		}

		$host = strtolower($host);
		$virtual_hosts = [
			'meet.google.com',
			'zoom.us',
			'teams.microsoft.com',
			'webex.com',
			'gotomeeting.com',
			'goto.com',
			'whereby.com',
		];

		foreach ($virtual_hosts as $virtual_host) {
			if ($host === $virtual_host || substr($host, -(strlen($virtual_host) + 1)) === '.' . $virtual_host) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolve an image URL for Event schema.
	 *
	 * @since  4.1.2
	 * @access private
	 *
	 * @return string
	 */
	private function get_schema_image_url()
	{
		$attachments = $this->event->get_attachments();
		if (!empty($attachments) && is_array($attachments)) {
			foreach ($attachments as $attachment) {
				if (!is_array($attachment) || empty($attachment['url'])) {
					continue;
				}

				$mime = !empty($attachment['mime']) ? strtolower((string) $attachment['mime']) : '';
				$url = esc_url_raw($attachment['url']);
				if ('' === $url) {
					continue;
				}

				if ('' !== $mime && 0 === strpos($mime, 'image/')) {
					return $url;
				}

				if (preg_match('/\.(jpe?g|png|gif|webp|svg)(\?|$)/i', $url)) {
					return $url;
				}
			}
		}

		$cover = isset($this->event->meta['cover_image']) ? $this->event->meta['cover_image'] : '';
		if (is_string($cover) && '' !== $cover) {
			if (0 === stripos($cover, 'http://') || 0 === stripos($cover, 'https://')) {
				return esc_url_raw($cover);
			}

			if (preg_match('/src=["\']([^"\']+)["\']/i', $cover, $matches)) {
				$src = $matches[1];
				if (0 === stripos($src, 'http://') || 0 === stripos($src, 'https://')) {
					return esc_url_raw($src);
				}
			}
		}

		// SC Events: only use a real event image, not site icon / logo stand-ins.
		if ('sc-event' === $this->event->type) {
			return '';
		}

		$site_icon = get_site_icon_url(512);
		if (!empty($site_icon)) {
			return esc_url_raw($site_icon);
		}

		$custom_logo_id = get_theme_mod('custom_logo');
		if ($custom_logo_id) {
			$logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
			if (!empty($logo_url)) {
				return esc_url_raw($logo_url);
			}
		}

		return '';
	}

	/**
	 * Build organizer microdata for Event schema.
	 *
	 * @since  4.1.2
	 * @access private
	 *
	 * @return string
	 */
	private function get_schema_organizer_meta()
	{
		$organizer = $this->event->get_organizer();
		if (!empty($organizer) && is_array($organizer) && !empty($organizer['name'])) {
			$html = '<span itemprop="organizer" itemscope itemtype="https://schema.org/Person" style="display:none;">';
			$html .= '<meta itemprop="name" content="' . esc_attr($organizer['name']) . '" />';
			if (!empty($organizer['email']) && $this->is_organizer_email_public()) {
				$html .= '<meta itemprop="email" content="' . esc_attr($organizer['email']) . '" />';
			}
			$html .= '</span>';

			return $html;
		}

		// SC Events have no organizer field — do not invent one from the site name.
		if ('sc-event' === $this->event->type) {
			return '';
		}

		$site_name = get_bloginfo('name');
		if (empty($site_name)) {
			$site_name = home_url('/');
		}

		return '<span itemprop="organizer" itemscope itemtype="https://schema.org/Organization" style="display:none;">' .
			'<meta itemprop="name" content="' .
			esc_attr($site_name) .
			'" />' .
			'<meta itemprop="url" content="' .
			esc_url(home_url('/')) .
			'" />' .
			'</span>';
	}

	/**
	 * Whether the calendar template explicitly exposes organizer email publicly.
	 *
	 * Mirrors Event_Builder [organizer] default: email="hide" unless email="show".
	 *
	 * @since  4.1.2
	 * @access private
	 *
	 * @return bool
	 */
	private function is_organizer_email_public()
	{
		$template = $this->get_event_template();

		if ('' === $template) {
			return false;
		}

		return (bool) preg_match('/\[organizer\b[^\]]*email\s*=\s*([\'"]?)show\1/i', $template);
	}

	/**
	 * Resolve the event template this event is rendered with.
	 *
	 * @since  4.3.3
	 * @access private
	 *
	 * @return string Empty string when no template can be resolved.
	 */
	private function get_event_template()
	{
		$template = !empty($this->event->template) ? (string) $this->event->template : '';

		if ('' === $template && !empty($this->event->calendar)) {
			$post = get_post((int) $this->event->calendar);
			if ($post instanceof \WP_Post) {
				$template = (string) $post->post_content;
			}
		}

		return $template;
	}

	/**
	 * Whether the event template uses a given template tag.
	 *
	 * @since  4.3.3
	 * @access private
	 *
	 * @param  string $tag Tag name without brackets, e.g. "organizer".
	 *
	 * @return bool
	 */
	private function template_has_tag($tag)
	{
		$template = $this->get_event_template();

		if ('' === $template) {
			return false;
		}

		return (bool) preg_match('/\[' . preg_quote($tag, '/') . '\b/i', $template);
	}

	/**
	 * Build performer microdata for Event schema.
	 *
	 * Only emitted when the event source provides actual performer data.
	 * Does not fall back to organizer or site name.
	 *
	 * @since  4.1.2
	 * @access private
	 *
	 * @return string
	 */
	private function get_schema_performer_meta()
	{
		/**
		 * Filter the performers used for Event schema.
		 *
		 * No core event source supplies performers, so Google reports the
		 * recommended "performer" field as missing. Rather than inventing a
		 * value, this lets a site declare the real performers per event.
		 *
		 * @since 4.3.3
		 *
		 * @param array $performers List of ['name' => string, 'type' => 'Person'|'Organization'].
		 * @param Event $event      Event the schema is built for.
		 */
		$performers = apply_filters('simcal_event_schema_performers', $this->get_event_performers(), $this->event);
		if (empty($performers) || !is_array($performers)) {
			return '';
		}

		$html = '';
		foreach ($performers as $performer) {
			$name = !empty($performer['name']) ? $performer['name'] : '';
			if ('' === $name) {
				continue;
			}

			$type = !empty($performer['type']) && 'Organization' === $performer['type'] ? 'Organization' : 'Person';

			$html .=
				'<span itemprop="performer" itemscope itemtype="https://schema.org/' . $type . '" style="display:none;">';
			$html .= '<meta itemprop="name" content="' . esc_attr($name) . '" />';
			$html .= '</span>';
		}

		return $html;
	}

	/**
	 * Get performer data supplied by the event source.
	 *
	 * @since  4.1.2
	 * @access private
	 *
	 * @return array
	 */
	private function get_event_performers()
	{
		if (!empty($this->event->meta['performers']) && is_array($this->event->meta['performers'])) {
			return $this->event->meta['performers'];
		}

		if (!empty($this->event->meta['performer']) && is_array($this->event->meta['performer'])) {
			$performer = $this->event->meta['performer'];
			// Single performer stored as associative meta: ['name' => '...'].
			if (isset($performer['name'])) {
				return [$performer];
			}

			return $performer;
		}

		return [];
	}

	/**
	 * Build offers microdata for Event schema.
	 *
	 * Only emitted when the event source provides verified ticket Offer data.
	 *
	 * @since  4.1.2
	 * @access private
	 *
	 * @return string
	 */
	private function get_schema_offers_meta()
	{
		$offer = $this->get_verified_event_offer();
		if (empty($offer)) {
			return '';
		}

		$html = '<span itemprop="offers" itemscope itemtype="https://schema.org/Offer" style="display:none;">';
		$html .= '<meta itemprop="url" content="' . esc_url($offer['url']) . '" />';
		$html .= '<meta itemprop="price" content="' . esc_attr($offer['price']) . '" />';
		$html .= '<meta itemprop="priceCurrency" content="' . esc_attr($offer['priceCurrency']) . '" />';
		$html .= '<meta itemprop="availability" content="' . esc_attr($offer['availability']) . '" />';
		$html .= '<meta itemprop="validFrom" content="' . esc_attr($offer['validFrom']) . '" />';
		$html .= '</span>';

		return $html;
	}

	/**
	 * Get a verified ticket Offer from event source meta.
	 *
	 * Requires price, priceCurrency, availability, url, and validFrom.
	 *
	 * @since  4.1.2
	 * @access private
	 *
	 * @return array Empty array when required ticket fields are missing.
	 */
	private function get_verified_event_offer()
	{
		$candidates = [];

		if (!empty($this->event->meta['offers']) && is_array($this->event->meta['offers'])) {
			// Prefer a list of offers; use the first complete one.
			if (isset($this->event->meta['offers']['price']) || isset($this->event->meta['offers']['url'])) {
				$candidates[] = $this->event->meta['offers'];
			} else {
				$candidates = $this->event->meta['offers'];
			}
		}

		if (!empty($this->event->meta['offer']) && is_array($this->event->meta['offer'])) {
			$candidates[] = $this->event->meta['offer'];
		}

		/**
		 * Filter the candidate ticket Offers used for Event schema.
		 *
		 * No core event source supplies ticket data, so Google reports the
		 * recommended "offers" field as missing. Rather than inventing a price,
		 * this lets a site declare the real offers per event. Google accepts a
		 * price of 0 for an event that is genuinely free to attend. Each
		 * candidate still has to carry price, priceCurrency, availability, url
		 * and validFrom to be emitted.
		 *
		 * @since 4.3.3
		 *
		 * @param array $candidates Candidate offers from the event source.
		 * @param Event $event      Event the schema is built for.
		 */
		$candidates = apply_filters('simcal_event_schema_offers', $candidates, $this->event);
		if (empty($candidates) || !is_array($candidates)) {
			return [];
		}

		foreach ($candidates as $candidate) {
			if (!is_array($candidate)) {
				continue;
			}

			$price = isset($candidate['price']) ? $candidate['price'] : null;
			$currency = !empty($candidate['priceCurrency'])
				? $candidate['priceCurrency']
				: (!empty($candidate['currency'])
					? $candidate['currency']
					: '');
			$availability = !empty($candidate['availability']) ? $candidate['availability'] : '';
			$url = !empty($candidate['url'])
				? $candidate['url']
				: (!empty($candidate['purchase_url'])
					? $candidate['purchase_url']
					: '');
			$valid_from = !empty($candidate['validFrom'])
				? $candidate['validFrom']
				: (!empty($candidate['valid_from'])
					? $candidate['valid_from']
					: (!empty($candidate['sales_start'])
						? $candidate['sales_start']
						: ''));

			if (null === $price || !is_numeric($price)) {
				continue;
			}

			$url = esc_url_raw((string) $url);
			if ('' === $currency || '' === $availability || '' === $url || '' === $valid_from) {
				continue;
			}

			// Availability may be a bare enum name; normalize to a schema.org URL.
			if (0 !== strpos($availability, 'http://') && 0 !== strpos($availability, 'https://')) {
				$availability = 'https://schema.org/' . ltrim($availability, '/');
			}

			return [
				'price' => (string) $price,
				'priceCurrency' => (string) $currency,
				'availability' => (string) $availability,
				'url' => $url,
				'validFrom' => (string) $valid_from,
			];
		}

		return [];
	}
}
