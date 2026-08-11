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

		if ($event->end_dt instanceof Carbon) {
			$html .= '<meta itemprop="endDate" content="' . esc_attr($event->end_dt->toIso8601String()) . '" />';
		}

		$html .= '<meta itemprop="eventStatus" content="https://schema.org/EventScheduled" />';

		$html .= '<meta itemprop="eventAttendanceMode" content="' . esc_attr($this->get_schema_attendance_mode()) . '" />';

		$image_url = $this->get_schema_image_url();
		if (!empty($image_url)) {
			$html .= '<meta itemprop="image" content="' . esc_url($image_url) . '" />';
		}

		$html .= $this->get_schema_organizer_meta();
		$html .= $this->get_schema_performer_meta();
		$html .= $this->get_schema_offers_meta();

		return $html;
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
		$location = !empty($this->event->start_location['address']) ? $this->event->start_location['address'] : '';
		$link = !empty($this->event->link) ? $this->event->link : '';
		$has_location = !empty($location);
		$has_virtual_location = $this->is_virtual_event_url($link);
		if ($has_location && $has_virtual_location) {
			return 'https://schema.org/MixedEventAttendanceMode';
		}
		if ($has_virtual_location) {
			return 'https://schema.org/OnlineEventAttendanceMode';
		}
		return 'https://schema.org/OfflineEventAttendanceMode';
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
			if ($host === $virtual_host || substr($host, -(1 - strlen($virtual_host))) === '.' . $virtual_host) {
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
		$template = !empty($this->event->template) ? (string) $this->event->template : '';

		if ('' === $template && !empty($this->event->calendar)) {
			$post = get_post((int) $this->event->calendar);
			if ($post instanceof \WP_Post) {
				$template = (string) $post->post_content;
			}
		}

		if ('' === $template) {
			return false;
		}

		return (bool) preg_match('/\[organizer\b[^\]]*email\s*=\s*([\'"]?)show\1/i', $template);
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
		$performers = $this->get_event_performers();
		if (empty($performers)) {
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
	 * Uses verified ticket Offer data when present. When no ticket data exists,
	 * falls back to a free Offer with price 0.
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
			$offer = $this->get_default_free_offer();
			if (empty($offer)) {
				return '';
			}
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
	 * Build a free Offer fallback (price 0) when no verified ticket data exists.
	 *
	 * @since  4.1.2
	 * @access private
	 *
	 * @return array Empty array when a purchase/event URL is unavailable.
	 */
	private function get_default_free_offer()
	{
		$offer_url = !empty($this->event->link) ? esc_url_raw($this->event->link) : '';
		if ('' === $offer_url) {
			$offer_url = esc_url_raw(home_url(add_query_arg([])));
		}

		if ('' === $offer_url) {
			return [];
		}

		$currency = 'USD';
		if (function_exists('get_woocommerce_currency')) {
			$woo_currency = get_woocommerce_currency();
			if (!empty($woo_currency)) {
				$currency = $woo_currency;
			}
		}

		$valid_from = $this->event->start_dt instanceof Carbon ? $this->event->start_dt->toIso8601String() : '';
		if ('' === $valid_from) {
			return [];
		}

		return [
			'price' => '0',
			'priceCurrency' => $currency,
			'availability' => 'https://schema.org/InStock',
			'url' => $offer_url,
			'validFrom' => $valid_from,
		];
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
