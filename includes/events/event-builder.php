<?php
/**
 * Event Builder
 *
 * @package SimpleCalendar/Events
 */
namespace SimpleCalendar\Events;

use Carbon\Carbon;
use SimpleCalendar\Abstracts\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Event Builder.
 *
 * Parses event templates from a feed post type for printing on calendar views.
 *
 * @since 3.0.0
 */
class Event_Builder {

	/**
	 * Event.
	 *
	 * @access public
	 * @var Event
	 */
	public $event = null;

	/**
	 * Calendar.
	 *
	 * @access public
	 * @var Calendar
	 */
	public $calendar = null;

	/**
	 * Tags.
	 *
	 * @access public
	 * @var array
	 */
	public $tags = array();

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Event    $event
	 * @param Calendar $calendar
	 */
	public function __construct( Event $event, Calendar $calendar ) {
		$this->event    = $event;
		$this->calendar = $calendar;
		$this->tags     = $this->get_content_tags();
	}

	/**
	 * Get content tags.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function get_content_tags() {
		return array_merge( array(

			/* ============ *
			 * Content Tags *
			 * ============ */

			'title',
			// The event title.
			'event-title',
			// @deprecated An alias for 'title' tag.
			'description',
			// The event description.

			'when',
			// Date and time of the event.
			'start-time',
			// Start time of the event.
			'start-date',
			// Start date of the event.
			'start-custom',
			// @deprecated Start time in a user defined format (set by tag 'format' attribute).
			'start-human',
			// Start time in a human friendly format.
			'end-time',
			// End time of the event.
			'end-date',
			// End date of the event.
			'end-custom',
			// @deprecated End date-time in a user defined format (set by tag 'format' attribute).
			'end-human',
			// End date-time in a human friendly format.

			'duration',
			// How long the events lasts, in a human-readable format.
			'length',
			// @deprecated An alias of 'duration' tag.

			'location',
			// Alias of start-location.
			'start-location',
			// Location name where the event starts.
			'maps-link',
			// @deprecated An alias for 'start-location-link' tag.
			'start-location-link',
			// Link to Google Maps querying the event start location address.
			'end-location',
			// Location name where the event ends.
			'end-location-link',
			// Link to Google Maps querying the event end location address.

			'link',
			// An HTML link to the event URL.
			'url',
			// A string with the raw event link URL.
			'add-to-gcal-link',
			// Link for viewers to add to their GCals.

			'calendar',
			// The title of the source calendar.
			'feed-title',
			// @deprecated An alias of 'calendar'.

			'id',
			// The event unique ID.
			'uid',
			// An alias of ID.
			'ical-id',
			// iCal ID.
			'event-id',
			// @deprecated An alias for 'id' tag.
			'calendar-id',
			// The calendar ID.
			'feed-id',
			// @deprecated An alias for 'calendar-id' tag.
			'cal-id',
			// @deprecated An alias for 'calendar-id' tag.

			/* ========= *
			 * Meta Tags *
			 * ========= */

			'attachments',
			// List of attachments.
			'attendees',
			// List of attendees.
			'organizer',
			// Creator info.

			/* ================ *
			 * Conditional Tags *
			 * ================ */

			'if-title',
			// If the event has a title.
			'if-description',
			// If the event has a description.

			'if-now',
			// If the event is taking place now.
			'if-not-now',
			// If the event is not taking place now (may have ended or just not started yet).
			'if-started',
			// If the event has started (and may as well as ended).
			'if-not-started',
			// If the event has NOT started yet (event could be any time in the future).
			'if-ended',
			// If the event has ended (event could be any time in the past).
			'if-not-ended',
			// If the event has NOT ended (may as well as not started yet).

			'if-whole-day',
			// If the event lasts the whole day.
			'if-all-day',
			// @deprecated Alias for 'if-whole-day'.
			'if-not-whole-day',
			// If the event does NOT last the whole day.
			'if-not-all-day',
			// @deprecated Alias for 'if-not-whole-day'.
			'if-end-time',
			// If the event has a set end time.
			'if-no-end-time',
			// If the event has NOT a set end time.

			'if-multi-day',
			// If the event spans multiple days.
			'if-single-day',
			// If the event does not span multiple days.

			'if-recurring',
			// If the event is a recurring event.
			'if-not-recurring',
			// If the event is NOT a recurring event.

			'if-location',
			// @deprecated Alias for 'if-start-location'.
			'if-start-location',
			// Does the event has a start location?
			'if-end-location',
			// Does the event has an end location?
			'if-not-location',
			// @deprecated Alias for 'if-not-start-location'.
			'if-not-start-location',
			// Does the event has NOT a start location?
			'if-not-end-location',
			// Does the event has NOT an end location?

		), (array) $this->add_custom_event_tags() );
	}

	/**
	 * Get event content.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $template_tags
	 *
	 * @return string
	 */
	public function parse_event_template_tags( $template_tags = '' ) {

		// Process tags.
		$result = preg_replace_callback( $this->get_regex(), array( $this, 'process_event_content' ), $template_tags );

		// Removes extra consecutive <br> tags.
		// TODO: Doesn't seem to work but going to remove it to allow multiple <br> tags in the editor
		/*return preg_replace( '#(<br *//*?>\s*)+#i', '<br />', trim( $result ) );*/

		return do_shortcode( trim( $result ) );
	}

	/**
	 * Process event content.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $match
	 *
	 * @return string
	 */
	public function process_event_content( $match ) {

		if ( $match[1] == '[' && $match[6] == ']' ) {
			return substr( $match[0], 1, -1 );
		}

		$tag     = $match[2]; // Tag name without square brackets.
		$before  = $match[1]; // Before tag.
		$partial = $match[5]; // HTML content between tags.
		$after   = $match[6]; // After tag.
		$attr    = $match[3]; // Tag attributes in quotes.

		$calendar = $this->calendar;
		$event    = $this->event;

		if ( ( $calendar instanceof Calendar ) && ( $event instanceof Event ) ) {

			switch ( $tag ) {

				/* ============ *
				 * Content Tags *
				 * ============ */

				case 'title' :
				case 'event-title' :
					return $this->get_title( $event->title, $attr );

				case 'description' :
					return $this->get_description( $event->description, $attr );

				case 'when' :
					return $this->get_when( $event );

				case 'end-date' :
				case 'end-custom' :
				case 'end-human' :
				case 'end-time' :
				case 'start-custom' :
				case 'start-date' :
				case 'start-human' :
				case 'start-time' :
					return $this->get_dt( $tag, $event, $attr );

				case 'length' :
				case 'duration' :
					if ( false !== $event->end ) {
						$duration = $event->start - $event->end;
						$value    = human_time_diff( $event->start, $event->end );
					} else {
						$duration = '-1';
						$value    = __( 'No end time', 'google-calendar-events' );
					}

					return ' <span class="simcal-event-duration" data-event-duration="' . $duration . '">' . $value . '</span>';

				case 'location' :
				case 'start-location' :
				case 'end-location' :
					$location       = ( 'end-location' == $tag ) ? $event->end_location['address'] : $event->start_location['address'];
					$location_class = ( 'end-location' == $tag ) ? 'end' : 'start';

					// location, location.name, location.address (type PostalAddress) all required for schema data.
					// Need to use event name where location data doesn't exist.
					// Since we have 1 location field, use it as the name and address.
					// If the location is blank, use the event title as the name and address.
					// Wrap with wp_strip_all_tags().
					$meta_location_name_and_address = empty( $location ) ? wp_strip_all_tags( $event->title ) : wp_strip_all_tags( $location );

					return ' <span class="simcal-event-address simcal-event-' . $location_class . '-location" itemprop="location" itemscope itemtype="http://schema.org/Place">' . '<meta itemprop="name" content="' . $meta_location_name_and_address . '" />' . '<meta itemprop="address" content="' . $meta_location_name_and_address . '" />' . wp_strip_all_tags( $location ) . '</span>';

				case 'start-location-link':
				case 'end-location-link' :
				case 'maps-link' :
					$location = ( 'end-location-link' == $tag ) ? $event->end_location['address'] : $event->start_location['address'];
					if ( ! empty( $location ) ) {
						$url = '//maps.google.com?q=' . urlencode( $location );

						return $this->make_link( $tag, $url, $calendar->get_event_html( $event, $partial ), $attr );
					}
					break;

				case 'link' :
				case 'url' :
					$content = ( 'link' == $tag ) ? $calendar->get_event_html( $event, $partial ) : '';

					return $this->make_link( $tag, $event->link, $content, $attr );

				case 'add-to-gcal-link';
					$content = ( 'add-to-gcal-link' == $tag ) ? $calendar->get_event_html( $event, $partial ) : '';
					if ( ! empty( $content ) ) {
						$url = $calendar->get_add_to_gcal_url( $event );

						return $this->make_link( $tag, $url, $content, $attr );
					}
					break;

				case 'calendar' :
				case 'feed-title' :
					return $event->source;

				case 'id' :
				case 'uid' :
				case 'event-id' :
					return $event->uid;

				case 'ical-id' :
					return $event->ical_id;

				case 'calendar-id' :
				case 'cal-id' :
				case 'feed-id' :
					return $event->calendar;

				/* ========= *
				 * Meta Tags *
				 * ========= */

				case 'attachments' :
					$attachments = $event->get_attachments();
					if ( ! empty( $attachments ) ) {
						return $this->get_attachments( $attachments );
					}
					break;

				case 'attendees' :
					$attendees = $event->get_attendees();
					if ( ! empty( $attendees ) ) {
						return $this->get_attendees( $attendees, $attr );
					}
					break;

				case 'organizer' :
					$organizer = $event->get_organizer();
					if ( ! empty( $organizer ) ) {
						return $this->get_organizer( $organizer, $attr );
					}
					break;

				/* ================ *
				 * Conditional Tags *
				 * ================ */

				case 'if-title':
					if ( ! empty( $event->title ) ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-description':
					if ( ! empty( $event->description ) ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-now' :
				case 'if-not-now' :

					$start_dt = $event->start_dt->setTimezone( $calendar->timezone );
					$start    = $start_dt->getTimestamp();

					if ( $event->end_dt instanceof Carbon ) {
						$end = $event->end_dt->setTimezone( $calendar->timezone )->getTimestamp();
					} else {
						return '';
					}

					$now = ( $start <= $calendar->now ) && ( $end >= $calendar->now );

					if ( ( 'if-now' == $tag ) && $now ) {
						return $calendar->get_event_html( $event, $partial );
					} elseif ( ( 'if-not-now' == $tag ) && ( false == $now ) ) {
						return $calendar->get_event_html( $event, $partial );
					}

					break;

				case 'if-started' :
				case 'if-not-started' :

					$start = $event->start_dt->setTimezone( $calendar->timezone )->getTimestamp();

					if ( 'if-started' == $tag ) {
						if ( $start < $calendar->now ) {
							return $calendar->get_event_html( $event, $partial );
						}
					} elseif ( 'if-not-started' == $tag ) {
						if ( $start > $calendar->now ) {
							return $calendar->get_event_html( $event, $partial );
						}
					}

					break;

				case 'if-ended' :
				case 'if-not-ended' :

					if ( false !== $event->end ) {

						$end = $event->end_dt->setTimezone( $calendar->timezone )->getTimestamp();

						if ( 'if-ended' == $tag ) {
							if ( $end < $calendar->now ) {
								return $calendar->get_event_html( $event, $partial );
							}
						} elseif ( 'if-not-ended' == $tag ) {
							if ( $end > $calendar->now ) {
								return $calendar->get_event_html( $event, $partial );
							}
						}

					}

					break;

				case 'if-end-time' :
					if ( false !== $event->end ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-no-end-time' :
					if ( false === $event->end ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-all-day' :
				case 'if-whole-day' :
				case 'if-not-all-day' :
				case 'if-not-whole-day' :
					$bool = strstr( $tag, 'not' ) ? false : true;
					if ( $bool === $event->whole_day ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-recurring' :
					if ( ! empty( $event->recurrence ) ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-not-recurring' :
					if ( false === $event->recurrence ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-multi-day' :
					if ( false !== $event->multiple_days ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-single-day' :
					if ( false === $event->multiple_days ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-location' :
				case 'if-start-location' :
					if ( ! empty( $event->start_location['address'] ) ) {
						return $calendar->get_event_html( $event, $partial );
					}

					return false;

				case 'if-not-location' :
				case 'if-not-start-location' :
					if ( empty( $event->start_location['address'] ) ) {
						return $calendar->get_event_html( $event, $partial );
					}

					return '';

				case 'if-not-end-location' :
					if ( empty( $event->end_location['address'] ) ) {
						return $calendar->get_event_html( $event, $partial );
					}

					return '';

				case 'if-end-location' :
					if ( ! empty( $event->end_location['address'] ) ) {
						return $calendar->get_event_html( $event, $partial );
					}

					return '';

				/* ======= *
				 * Custom Event Tags or Default *
				 * ======= */

				default :
					$resultCustom = $this->do_custom_event_tag( $tag, $partial, $attr, $event );
					if ( $resultCustom != "" ) {
						return $resultCustom;
					}

					return wp_kses_post( $before . $partial . $after );
			}
		}

		return '';
	}

	/**
	 * Limit words in text string.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  string $text
	 * @param  int    $limit
	 *
	 * @return string
	 */
	private function limit_words( $text, $limit ) {

		$limit = max( absint( $limit ), 0 );

		if ( $limit > 0 && ( str_word_count( $text, 0 ) > $limit ) ) {
			$words = str_word_count( $text, 2 );
			$pos   = array_keys( $words );
			$text  = trim( substr( $text, 0, $pos[ $limit ] ) ) . '&hellip;';
		}

		return $text;
	}

	/**
	 * Get event title.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  $title
	 * @param  $attr
	 *
	 * @return string
	 */
	private function get_title( $title, $attr ) {

		if ( empty( $title ) ) {
			return '';
		}

		$attr = array_merge( array(
			'html'  => '',  // Parse HTML
			'limit' => 0,   // Trim length to amount of words
		), (array) shortcode_parse_atts( $attr ) );

		if ( ! empty( $attr['html'] ) ) {
			$title = wp_kses_post( $title );
			$tag   = 'div';
		} else {
			$title = $this->limit_words( $title, $attr['limit'] );
			$tag   = 'span';
		}

		return '<' . $tag . ' class="simcal-event-title" itemprop="name">' . $title . '</' . $tag . '>';
	}

	/**
	 * Get event description.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  string $description
	 * @param  string $attr
	 *
	 * @return string
	 */
	private function get_description( $description, $attr ) {

		if ( empty( $description ) ) {
			return '';
		}

		$attr = array_merge( array(
			'limit'    => 0,       // Trim length to number of words
			'html'     => 'no',    // Parse HTML content
			'markdown' => 'no',    // Parse Markdown content
			'autolink' => 'no',    // Automatically convert plaintext URIs to anchors
		), (array) shortcode_parse_atts( $attr ) );

		$allow_html = 'no' != $attr['html'] ? true : false;
		$allow_md   = 'no' != $attr['markdown'] ? true : false;

		$html = '<div class="simcal-event-description" itemprop="description">';

		// Markdown and HTML don't play well together, use one or the other in the same tag.
		if ( $allow_html || $allow_md ) {
			if ( $allow_html ) {
				$description = wp_kses_post( $description );
			} elseif ( $allow_md ) {
				$markdown    = new \Parsedown();
				$description = $markdown->text( wp_strip_all_tags( $description ) );
			}
		} else {
			$description = wpautop( $description );
		}

		$description = $this->limit_words( $description, $attr['limit'] );

		$html .= $description . '</div>';

		if ( 'no' != $attr['autolink'] ) {
			$html = ' ' . make_clickable( $html );
		}

		return $html;
	}

	/**
	 * Get event start and end date and time.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  Event $event
	 *
	 * @return string
	 */
	private function get_when( Event $event ) {

		$start = $event->start_dt;
		$end   = $event->end_dt;

		$time_start = '';
		$time_end   = '';
		$start_ts   = $start->timestamp;
		$end_ts     = ! is_null( $end ) ? $end->timestamp : null;
		$start_iso  = $start->toIso8601String();
		$end_iso    = ! is_null( $end ) ? $end->toIso8601String() : null;;

		if ( ! $event->whole_day ) {

			$time_start = $this->calendar->datetime_separator . ' <span class="simcal-event-start simcal-event-start-time" ' . 'data-event-start="' . $start_ts . '" ' . 'data-event-format="' . $this->calendar->time_format . '" ' . 'itemprop="startDate" content="' . $start_iso . '">' . date_i18n( $this->calendar->time_format, strtotime( $start->toDateTimeString() ) ) . '</span> ';

			if ( $end instanceof Carbon ) {

				$time_end = ' <span class="simcal-event-end simcal-event-end-time" ' . 'data-event-end="' . $end_ts . '" ' . 'data-event-format="' . $this->calendar->time_format . '" ' . 'itemprop="endDate" content="' . $end_iso . '">' . date_i18n( $this->calendar->time_format, strtotime( $end->toDateTimeString() ) ) . '</span> ';

			}

		}

		if ( $event->multiple_days ) {

			$output = ' <span class="simcal-event-start simcal-event-start-date" ' . 'data-event-start="' . $start_ts . '" ' . 'data-event-format="' . $this->calendar->date_format . '" ' . 'itemprop="startDate" content="' . $start_iso . '">' . date_i18n( $this->calendar->date_format, strtotime( $start->toDateTimeString() ) ) . '</span> ' . $time_start;

			if ( $end instanceof Carbon ) {

				$output .= '-' . ' <span class="simcal-event-start simcal-event-end-date" ' . 'data-event-start="' . $end_ts . '" ' . 'data-event-format="' . $this->calendar->date_format . '" ' . 'itemprop="endDate" content="' . $end_iso . '">' . date_i18n( $this->calendar->date_format, strtotime( $end->toDateTimeString() ) ) . '</span> ' . $time_end;
			}

		} else {

			$time_end = ! empty( $time_start ) && ! empty( $time_end ) ? ' - ' . $time_end : '';

			// All-day events also need startDate for schema data.
			$output = ' <span class="simcal-event-start simcal-event-start-date" ' . 'data-event-start="' . $start_ts . '" ' . 'data-event-format="' . $this->calendar->date_format . '" ' . 'itemprop="startDate" content="' . $start_iso . '">' . date_i18n( $this->calendar->date_format, strtotime( $start->toDateTimeString() ) ) . '</span> ' . $time_start . $time_end;

		}

		return trim( $output );
	}

	/**
	 * Get event date or time.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  string $tag
	 * @param  Event  $event
	 * @param  string $attr
	 *
	 * @return string
	 */
	private function get_dt( $tag, Event $event, $attr ) {

		$bound = 0 === strpos( $tag, 'end' ) ? 'end' : 'start';

		if ( ( 'end' == $bound ) && ( false === $event->end ) ) {
			return '';
		}

		$dt = $bound . '_dt';

		if ( ! $event->$dt instanceof Carbon ) {
			return '';
		}

		$event_dt = $event->$dt;

		$attr = array_merge( array(
			'format' => '',
		), (array) shortcode_parse_atts( $attr ) );

		$format    = ltrim( strstr( $tag, '-' ), '-' );
		$dt_format = '';

		if ( ! empty( $attr['format'] ) ) {
			$dt_format = esc_attr( wp_strip_all_tags( $attr['format'] ) );
		} elseif ( 'date' == $format ) {
			$dt_format = $this->calendar->date_format;
		} elseif ( 'time' == $format ) {
			$dt_format = $this->calendar->time_format;
		}

		$dt_ts = $event_dt->timestamp;

		if ( 'human' == $format ) {
			$value = human_time_diff( $dt_ts, Carbon::now( $event->timezone )->getTimestamp() );

			if ( $dt_ts < Carbon::now( $event->timezone )->getTimestamp() ) {
				$value .= ' ' . _x( 'ago', 'human date event builder code modifier', 'google-calendar-events' );
			} else {
				$value .= ' ' . _x( 'from now', 'human date event builder code modifier', 'google-calendar-events' );
			}
		} else {
			$value = date_i18n( $dt_format, strtotime( $event_dt->toDateTimeString() ) );
		}

		return '<span class="simcal-event-' . $bound . ' ' . 'simcal-event-' . $bound . '-' . $format . '" ' . 'data-event-' . $bound . '="' . $dt_ts . '" ' . 'data-event-format="' . $dt_format . '" ' . 'itemprop="' . $bound . 'Date" content="' . $event_dt->toIso8601String() . '">' . $value . '</span>';
	}

	/**
	 * Make a link.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  string $tag
	 * @param  string $url
	 * @param  string $content
	 * @param  string $attr
	 *
	 * @return string
	 */
	private function make_link( $tag, $url, $content, $attr ) {

		if ( empty( $url ) ) {
			return '';
		}

		$text = empty( $content ) ? $url : $content;

		$attr = array_merge( array(
			'autolink'  => false,   // Convert url to link anchor
			'newwindow' => false,   // If autolink attribute is true, open link in new window
		), (array) shortcode_parse_atts( $attr ) );

		$anchor = $tag != 'url' ? 'yes' : $attr['autolink'];
		$target = $attr['newwindow'] !== false ? 'target="_blank"' : '';

		return $anchor !== false ? ' <a href="' . esc_url( $url ) . '" ' . $target . '>' . $text . '</a>' : ' ' . $text;
	}

	/**
	 * Get event attachments.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  array $attachments
	 *
	 * @return string
	 */
	private function get_attachments( $attachments ) {

		$html = '<ul class="simcal-attachments">' . "\n\t";

		foreach ( $attachments as $attachment ) {
			$html .= '<li class="simcal-attachment">';
			$html .= '<a href="' . $attachment['url'] . '" target="_blank">';
			$html .= ! empty( $attachment['icon'] ) ? '<img src="' . $attachment['icon'] . '" />' : '';
			$html .= '<span>' . $attachment['name'] . '</span>';
			$html .= '</a>';
			$html .= '</li>' . "\n";
		}

		$html .= '</ul>' . "\n";

		return $html;
	}

	/**
	 * Get attendees.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  array  $attendees
	 * @param  string $attr
	 *
	 * @return string
	 */
	private function get_attendees( $attendees, $attr ) {

		$attr = array_merge( array(
			'photo'    => 'show',  // show/hide attendee photo
			'email'    => 'hide',  // show/hide attendee email address
			'rsvp'     => 'hide',  // show/hide rsvp response status
			'response' => '',      // filter attendees by rsvp response (yes/no/maybe)
		), (array) shortcode_parse_atts( $attr ) );

		$html = '<ul class="simcal-attendees" itemprop="attendees">' . "\n\t";

		$known   = 0;
		$unknown = 0;

		foreach ( $attendees as $attendee ) {

			if ( 'yes' == $attr['response'] && 'yes' != $attendee['response'] ) {
				continue;
			} elseif ( 'no' == $attr['response'] && 'no' != $attendee['response'] ) {
				continue;
			} elseif ( 'maybe' == $attr['response'] && ! in_array( $attendee['response'], array( 'yes', 'maybe' ) ) ) {
				continue;
			}

			if ( ! empty( $attendee['name'] ) ) {

				$photo    = 'hide' != $attr['photo'] ? '<img class="avatar avatar-128 photo" src="' . $attendee['photo'] . '" itemprop="image" />' : '';
				$response = 'hide' != $attr['rsvp'] ? $this->get_rsvp_response( $attendee['response'] ) : '';
				$guest    = $photo . '<span itemprop="name">' . $attendee['name'] . $response . '</span>';

				if ( ! empty( $attendee['email'] ) && ( 'show' == $attr['email'] ) ) {
					$guest = sprintf( '<a href="mailto:' . $attendee['email'] . '" itemprop="email">%s</a>', $guest );
				}

				$html .= '<li class="simcal-attendee" itemprop="attendee" itemscope itemtype="http://schema.org/Person">' . $guest . '</li>' . "\n";

				$known++;

			} else {

				$unknown++;

			}
		}

		if ( $unknown > 0 ) {
			if ( $known > 0 ) {
				/* translators: One more person attending the event. */
				$others = sprintf( _n( '1 more attendee', '%s more attendees', $unknown, 'google-calendar-events' ), $unknown );
			} else {
				/* translators: One or more persons attending the event whose name is unknown. */
				$others = sprintf( _n( '1 anonymous attendee', '%s anonymous attendees', $unknown, 'google-calendar-events' ), $unknown );
			}
			$photo = $attr['photo'] !== 'hide' ? get_avatar( '', 128 ) : '';
			$html .= '<li class="simcal-attendee simcal-attendee-anonymous">' . $photo . '<span>' . $others . '</span></li>' . "\n";
		} elseif ( $known === 0 ) {
			$html .= '<li class="simcal-attendee">' . _x( 'No one yet', 'No one yet rsvp to attend the event.', 'google-calendar-events' ) . '</li>' . "\n";
		}

		$html .= '</ul>' . "\n";

		return $html;
	}

	/**
	 * Format attendee rsvp response.
	 *
	 * @since  3.0.0
	 *
	 * @param  $response
	 *
	 * @return string
	 */
	private function get_rsvp_response( $response ) {

		if ( 'yes' == $response ) {
			/* translators: Someone replied with 'yes' to a rsvp request. */
			$rsvp = __( 'Attending', 'google-calendar-events' );
		} elseif ( 'no' == $response ) {
			/* translators: Someone replied with 'no' to a rsvp request. */
			$rsvp = __( 'Not attending', 'google-calendar-events' );
		} elseif ( 'maybe' == $response ) {
			/* translators: Someone replied with 'maybe' to a rsvp request. */
			$rsvp = __( 'Maybe attending', 'google-calendar-events' );
		} else {
			/* translators: Someone did not send yet a rsvp confirmation to join an event. */
			$rsvp = __( 'Response pending', 'google-calendar-events' );
		}

		return ' <small>(' . $rsvp . ')</small>';
	}

	/**
	 * Get event organizer.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  array  $organizer
	 * @param  string $attr
	 *
	 * @return string
	 */
	private function get_organizer( $organizer, $attr ) {

		$attr = array_merge( array(
			'photo' => 'show',  // show/hide attendee photo
			'email' => 'hide',  // show/hide attendee email address
		), (array) shortcode_parse_atts( $attr ) );

		$photo          = 'hide' != $attr['photo'] ? '<img class="avatar avatar-128 photo" src="' . $organizer['photo'] . '" itemprop="image"  />' : '';
		$organizer_html = $photo . '<span itemprop="name">' . $organizer['name'] . '</span>';

		if ( ! empty( $organizer['email'] ) && ( 'show' == $attr['email'] ) ) {
			$organizer_html = sprintf( '<a href="mailto:' . $organizer['email'] . '" itemprop="email">%s</a>', $organizer_html );
		}

		return '<div class="simcal-organizer" itemprop="organizer" itemscope itemtype="https://schema.org/Person">' . $organizer_html . '</div>';
	}

	/**
	 * Retrieve the event builder tag regular expression for searching.
	 *
	 * Combines the event builder tags in the regular expression in a regex class.
	 * The regular expression contains 6 different sub matches to help with parsing:
	 *
	 *  1 - An extra [ to allow for escaping tags with double square brackets [[]]
	 *  2 - The tag name
	 *  3 - The tag argument list
	 *  4 - The self closing /
	 *  5 - The content of a tag when it wraps some content.
	 *  6 - An extra ] to allow for escaping tags with double square brackets [[]]
	 *
	 * @since  3.0.0
	 *
	 * @return string The tag search regular expression result
	 */
	private function get_regex() {

		// This is largely borrowed on get_shortcode_regex() from WordPress Core.
		// @see /wp-includes/shortcodes.php (with some modification)

		$tagregexp = implode( '|', array_values( $this->tags ) );

		return '/' . '\\['                              // Opening bracket
		       . '(\\[?)'                           // 1: Optional second opening bracket for escaping tags: [[tag]]
		       . "($tagregexp)"                     // 2: Tag name
		       . '(?![\\w-])'                       // Not followed by word character or hyphen
		       . '('                                // 3: Unroll the loop: Inside the opening tag
		       . '[^\\]\\/]*'                   // Not a closing bracket or forward slash
		       . '(?:' . '\\/(?!\\])'               // A forward slash not followed by a closing bracket
		       . '[^\\]\\/]*'               // Not a closing bracket or forward slash
		       . ')*?' . ')' . '(?:' . '(\\/)'                        // 4: Self closing tag ...
		       . '\\]'                          // ... and closing bracket
		       . '|' . '\\]'                          // Closing bracket
		       . '(?:' . '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing tags
		       . '[^\\[]*+'             // Not an opening bracket
		       . '(?:' . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing tag
		       . '[^\\[]*+'         // Not an opening bracket
		       . ')*+' . ')' . '\\[\\/\\2\\]'             // Closing tag
		       . ')?' . ')' . '(\\]?)'                           // 6: Optional second closing bracket for escaping tags: [[tag]]
		       . '/s';
	}

	//allow other plugins to register own event tags
	private function add_custom_event_tags() {
		$array = apply_filters( 'simcal_event_tags_add_custom', array() );

		return $array;
	}

	//allow other plugins to replace own (registered) event tags with their value
	private function do_custom_event_tag( $tag, $partial, $attr, $event ) {
		$returnvalue = apply_filters( 'simcal_event_tags_do_custom', "", $tag, $partial, $attr, $event );

		return $returnvalue;
	}
}
