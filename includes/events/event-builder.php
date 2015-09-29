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
	 * Tag attributes.
	 *
	 * @access private
	 * @var array
	 */
	public $tag_attributes = array();

	/**
	 * Constructor.
	 *
	 * @param Event    $event
	 * @param Calendar $calendar
	 */
	public function __construct( Event $event, Calendar $calendar ) {
		$this->event = $event;
		$this->calendar = $calendar;
		$this->tags = $this->get_content_tags();
		$this->tag_attributes = $this->get_content_tags_atts();
	}

	/**
	 * Get content tags.
	 *
	 * @return array
	 */
	public function get_content_tags() {
		return  array(

			/* ============ *
			 * Content Tags *
			 * ============ */

			'title',                 // The event title.
			'event-title',           // @deprecated An alias for 'title' tag.
			'description',           // The event description.

			'when',                  // Date and time of the event.
			'start-time',            // Start time of the event.
			'start-date',            // Start date of the event.
			'start-custom',          // @deprecated Start time in a user defined format (set by tag 'format' attribute).
			'start-human',           // Start time in a human friendly format.
			'end-time',              // End time of the event.
			'end-date',              // End date of the event.
			'end-custom',            // @deprecated End date-time in a user defined format (set by tag 'format' attribute).
			'end-human',             // End date-time in a human friendly format.

			'duration',              // How long the events lasts, in a human-readable format.
			'length',                // @deprecated An alias of 'duration' tag.

			'location',              // Start and end location of the event.
			'location-link',         // Links to Google Maps querying the event start and end locations.
			'start-location',        // Location name where the event starts.
			'maps-link',             // @deprecated An alias for 'start-location-link' tag.
			'start-location-link',   // Link to Google Maps querying the event start location address.
			'end-location',          // Location name where the event ends.
			'end-location-link',     // Link to Google Maps querying the event end location address.

			'link',                  // An HTML link to the event URL.
			'url',                   // A string with the raw event link URL.

			/* ================ *
			 * Conditional Tags *
			 * ================ */

			'if-title',              // If the event has a title.
			'if-description',        // If the event has a description.

			'if-now',                // If the event is taking place now.
			'if-not-now',            // If the event is not taking place now (may have ended or just not started yet).
			'if-started',            // If the event has started (and may as well as ended).
			'if-not-started',        // If the event has NOT started yet (event could be any time in the future).
			'if-ended',              // If the event has ended (event could be any time in the past).
			'if-not-ended',          // If the event has NOT ended (may as well as not started yet).

			'if-whole-day',          // If the event lasts the whole day.
			'if-all-day',            // @deprecated Alias for 'if-whole-day'.
			'if-not-whole-day',      // If the event does NOT last the whole day.
			'if-not-all-day',        // @deprecated Alias for 'if-not-whole-day'.
			'if-end-time',           // If the event has a set end time.
			'if-no-end-time',        // If the event has NOT a set end time.

			'if-first',              // If the event is the first of the day.
			'if-not-first',          // If the event is not the first of the day.

			'if-multi-day',          // If the event spans multiple days.
			'if-single-day',         // If the event does not span multiple days.

			'if-recurring',          // If the event is a recurring event.
			'if-not-recurring',      // If the event is NOT a recurring event.

			'if-location',           // @deprecated Alias for 'if-start-location'.
			'if-start-location',     // Does the event has a start location?
			'if-not-location',       // @deprecated Alias for 'if-not-start-location'.
			'if-not-start-location', // Does the event has NOT a start location?

			/* ========= *
			 * Meta Tags *
			 * ========= */

			'attachments',          // List of attachments.
			'attendees',            // List of attendees.
			'participants',         // Alias for attendees.
			'organizer',            // Organizer info.

		);
	}

	/**
	 * Get content tags attributes.
	 *
	 * @return array
	 */
	public function get_content_tags_atts() {
		return array(
			'autolink'  => false,   // Description: set to 'yes' to make plaintext URLs clickable.
			'format'    => '',      // Datetime: print date or time in custom format.
			'limit'     => 0,       // Trim description or title to specified amount of words.
			'html'      => false,   // Description: set to 'yes' to allow HTML (uses `wp_kses_post()`).
			'markdown'  => false,   // Description: set to 'yes' parses Markdown.
			'newwindow' => false,   // Links: set to 'new' to open anchor link target to '_blank'.
		);
	}

	/**
	 * Limit words in text string.
	 *
	 * @param  string $text
	 * @param  int $limit
	 *
	 * @return string
	 */
	private function limit_words( $text, $limit ) {

		$text = wp_strip_all_tags( $text );
		$limit = max( absint( $limit ), 0 );

		if ( $limit > 0 && ( str_word_count( $text, 0 ) > $limit ) ) {
			$words  = str_word_count( $text, 2 );
			$pos    = array_keys( $words );
			$text   = trim( substr( $text, 0, $pos[ $limit ] ) ) . '&hellip;';
		}

		return $text;
	}

	/**
	 * Get event content.
	 *
	 * @param  string $template_tags
	 *
	 * @return string
	 */
	public function parse_event_template_tags( $template_tags = '' ) {

		// Process tags.
		$result = preg_replace_callback(
			$this->get_regex(),
			array( $this, 'process_event_content' ),
			$template_tags
		);

		// Removes extra consecutive <br> tags.
		return preg_replace('#(<br */?>\s*)+#i', '<br />', trim( $result ) );
	}

	/**
	 * Process event content.
	 *
	 * @param  string $match
	 *
	 * @return string
	 */
	public function process_event_content( $match ) {

		if ( $match[1] == '[' && $match[6] == ']' ) {
			return substr( $match[0], 1, - 1 );
		}

		$tag     = $match[2];                         // Tag name without square brackets.
		$before  = $match[1];                         // Before tag.
		$partial = $match[5];                         // HTML content between tags.
		$after   = $match[6];                         // After tag.
		$attr    = shortcode_parse_atts( $match[3] ); // Tag attributes in quotes.

		// Default attributes.
		$attr = array_merge( $this->tag_attributes, (array) $attr );

		$calendar = $this->calendar;
		$event    = $this->event;

		if ( ( $calendar instanceof Calendar ) && ( $event instanceof Event ) ) {

			switch ( $tag ) {

				/* ============ *
				 * Content Tags *
				 * ============ */

				case 'title' :
				case 'event-title' :

					if ( empty( $event->title ) ) {
						return '';
					}

					$title = ' <span class="simcal-event-title">';
					$title .= $this->limit_words( $event->title, $attr['limit'] );
					$title .= '</span>';

					return $title;

				case 'description' :

					$description = $event->description;
					if ( empty( $description ) ) {
						return '';
					}

					$allow_html = $attr['html'] !== false ? true : false;
					$allow_md   = $attr['markdown'] !== false ? true : false;

					$html = '<div class="simcal-event-description">';

					if ( $allow_html || $allow_md ) {

						if ( $allow_html && $allow_md ) {
							$markdown = new \Parsedown();
							$description = wp_kses_post( $description );
							$html .= $markdown->text( $description );
						} elseif ( $allow_html ) {
							$html .= wp_kses_post( $description );
						} elseif ( $allow_md ) {
							$markdown = new \Parsedown();
							$html .= $markdown->text( wp_strip_all_tags( $description ) );
						}

					} else {

						$html .= $this->limit_words( $description, $attr['limit'] );

					}

					$html .= '</div>';

					if ( $attr['autolink'] !== false ) {
						$html = ' ' . make_clickable( $html );
					}

					return $html;

				case 'when' :

					$start = $event->start_dt->setTimezone( $event->timezone );
					$end = ! is_null( $event->end_dt ) ? $event->end_dt->setTimezone( $event->timezone ) : null;
					$time_start = '';
					$time_end = '';

					if ( ! $event->whole_day ) {
						$time_start = $calendar->datetime_separator .
						              ' <span class="simcal-event-start simcal-event-start-time" ' .
						              'data-event-start="' . $start->getTimestamp() . '" ' .
						              'data-event-format="' . $calendar->time_format . '">' .
						              $start->format( $calendar->time_format ) .
						              '</span> ';
						if ( $end instanceof Carbon ) {
							$time_end = ' <span class="simcal-event-end simcal-event-end-time" ' .
							            'data-event-end="' . $end->getTimestamp() . '" ' .
							            'data-event-format="' . $calendar->time_format . '">' .
							            $end->format( $calendar->time_format ) .
							            '</span> ';
						}
					}

					if ( $event->multiple_days ) {
						$output = ' <span class="simcal-event-start simcal-event-start-date" ' .
						          'data-event-start="' . $start->getTimestamp() . '" ' .
						          'data-event-format="' . $calendar->date_format . '">' .
						          $start->format( $calendar->date_format ) .
						          '</span> ' .
						          $time_start;

						if ( $end instanceof Carbon ) {
							$output .= '-' .
							           ' <span class="simcal-event-start simcal-event-end-date" ' .
							           'data-event-start="' . $end->getTimestamp() . '" ' .
							           'data-event-format="' . $calendar->date_format . '">' .
							           $end->format( $calendar->date_format ) .
							           '</span> ' .
							           $time_end;
						}
					} else {
						$time_end = ! empty( $time_start ) && ! empty( $time_end ) ? '- ' . $time_end : '';
						$output   = ' <span class="simcal-event-start simcal-event-start-date" ' .
						            'data-event-start="' . $start->getTimestamp() . '"' .
						            'data-event-format="' . $calendar->date_format . '">' .
						            $start->format( $calendar->date_format ) .
						            '</span> ' .
						            $time_start .
						            $time_end;
					}

					return trim( $output );

				case 'end-date'    :
				case 'end-custom'  :
				case 'end-human'   :
				case 'end-time'    :
				case 'start-custom':
				case 'start-date'  :
				case 'start-human' :
				case 'start-time'  :

					$bound = 0 === strpos( $tag, 'end' ) ? 'end' : 'start';
					if ( ( 'end' == $bound ) && ! $event->end ) {
						return '';
					}
					$dt = $bound . '_dt';
					if ( ! $event->$dt instanceof Carbon ) {
						return '';
					}
					$event_dt = $event->$dt->setTimezone( $event->timezone );

					$format    = ltrim( strstr( $tag, '-' ), '-' );
					$dt_format = '';
					if ( ! empty( $attr['format'] ) ) {
						$dt_format = esc_attr( wp_strip_all_tags( $attr['format'] ) );
					} elseif ( 'date' == $format ) {
						$dt_format = $calendar->date_format;
					} elseif ( 'time' == $format ) {
						$dt_format = $calendar->time_format;
					}

					if ( 'human' == $format ) {
						$value = $event_dt->diffForHumans( Carbon::now( $calendar->timezone ) );
					} else {
						$value = $event_dt->format( $dt_format );
					}

					return ' <span class="simcal-event-' . $bound . ' ' . 'simcal-event-' . $bound . '-' . $format . '"' .
				                'data-event-' . $bound . '="' . $event_dt->getTimestamp() . '"' .
				                'data-event-format="' . $dt_format . '">' .
				                $value .
				                '</span>';

				case 'length' :
				case 'duration' :
					if ( $event->end ) {
						return ' <span class="simcal-event-duration"' .
						       'data-event-duration="' . $event->start - $event->end . '">' .
						       human_time_diff( $event->start, $event->end ) .
						       '</span>';
					}
					return '<span class="simcal-event-duration"' .
					       'data-event-duration="-1">' .
					       __( 'No end time', 'google-calendar-events' ) .
					       '</span>';

				case 'location' :
					$output = '';
					if ( $start_location = $event->start_location['name'] ) {
						$output = $start_location;
						if ( $end_location = $event->end_location['name'] ) {
							if ( ! empty( $end_location ) && $end_location != $start_location ) {
								$output .= ' - ' . $end_location;
							}
						}
					}
					return $output;

				case 'location-link' :
					$output = array();
					$target = $attr['newwindow'] !== false ? 'target="_blank"' : '';
					if ( $start_location = $event->start_location['address'] ) {
						$output = '<a href="' . esc_url( '//maps.google.com?q=' . urlencode( $start_location ) ) . '" ' . $target . '>' . $partial . '</a>';
					}
					if ( $end_location = $event->end_location['address'] ) {
						if ( $end_location != $start_location ) {
							$output .= ' - <a href="' . esc_url( '//maps.google.com?q=' . urlencode( $end_location ) ) . '" ' . $target . '>' . $partial . '</a>';
						}
					}
					return $output;

				case 'start-location' :
				case 'end-location' :
					$location = $tag == 'end-location' ? $event->end_location['address'] : $event->start_location['address'];
					return ' <span class=" simcal-event-address simcal-event-start-location">' .
					       wp_strip_all_tags( $location ) .
					       '</span>';

				case 'start-location-link':
				case 'end-location-link' :
				case 'maps-link' :
					$location = $tag == 'end-location' ? $event->end_location['address'] : $event->start_location['address'];
					if ( $location ) {
						$target = $attr['newwindow'] !== false ? 'target="_blank"' : '';
						return ' <a href="' . esc_url( '//maps.google.com?q=' . urlencode( $location ) ) . '" ' . $target . '>' . $partial . '</a>';
					}
					break;

				case 'link' :
					if ( $event->link ) {
						$target = $attr['newwindow'] !== false ? 'target="_blank"' : '';
						return ' <a href="' . $event->link . '" ' . $target . '>' . $calendar->get_event_html( $event, $partial ) . '</a>';
					}
					break;

				case 'url' :
					return $attr['autolink'] == 'yes' ? ' ' . make_clickable( $event->link ) : ' ' . $event->link;

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

				case 'if-now':
				case 'if-not-now':

					$start_date = Carbon::createFromTimestamp( $event->start_utc, 'UTC' )->setTimezone( $calendar->timezone );
					$start      = $start_date->getTimestamp();

					if ( $event->end_utc ) {
						$end_date = Carbon::createFromTimestamp( $event->end_utc, 'UTC' )->setTimezone( $calendar->timezone );
						$end      = $end_date->getTimestamp();
					} else {
						$end_date = $start_date->endOfDay();
						$end      = $end_date->getTimestamp();
					}

					$now = $calendar->now;

					if ( 'if-now' == $tag ) {
						if ( ( $start <= $now ) && ( $end > $now ) ) {
							return $calendar->get_event_html( $event, $partial );
						}
					} elseif ( 'if-not-now' == $tag ) {
						if ( $start > $now && $end <= $now ) {
							return $calendar->get_event_html( $event, $partial );
						}
					}

					break;

				case 'if-started':
				case 'if-not-started':

					$start_date = Carbon::createFromTimestamp( $event->start_utc, 'UTC' )->setTimezone( $calendar->timezone );
					$start      = $start_date->getTimestamp();
					$now        = $calendar->now;

					if ( 'if-started' == $tag ) {
						if ( $start < $now ) {
							return $calendar->get_event_html( $event, $partial );
						}
					} elseif ( 'if-not-started' == $tag ) {
						if ( $start > $now ) {
							return $calendar->get_event_html( $event, $partial );
						}
					}

					break;

				case 'if-ended':
				case 'if-not-ended':

					if ( $event->end_utc ) {

						$end_date = Carbon::createFromTimestamp( $event->end_utc, 'UTC' )->setTimezone( $calendar->timezone );
						$end      = $end_date->getTimestamp();
						$now      = $calendar->now;

						if ( 'if-ended' == $tag ) {
							if ( $end < $now ) {
								return $calendar->get_event_html( $event, $partial );
							}
						} elseif ( 'if-not-ended' == $tag ) {
							if ( $end > $now ) {
								return $calendar->get_event_html( $event, $partial );
							}
						}

					}

					break;

				case 'if-end-time':
					if ( $event->end ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-no-end-time':
					if ( ! $event->end ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-first':
				case 'if-not-first' :
					$events = $calendar->events;
					$pos    = array_search( $event->start_utc, array_keys( $events ) );
					$case   = $tag == 'if-first' ? $pos === 0 : $pos !== 0;

					return $case ? $calendar->get_event_html( $event, $partial ) : '';

				case 'if-all-day':
				case 'if-whole-day':
					if ( $event->whole_day === true ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-not-all-day':
				case 'if-not-whole-day':
					if ( $event->whole_day === false ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-recurring' :
					if ( $event->recurrence ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-not-recurring' :
					if ( ! $event->recurrence ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-multi-day':
					if ( $event->end ) {
						if ( ( $event->start + $event->end ) > 86400 ) {
							return $calendar->get_event_html( $event, $partial );
						}
					}
					break;

				case 'if-single-day':
					if ( $event->end ) {
						if ( ( $event->start + $event->end ) <= 86400 ) {
							return $calendar->get_event_html( $event, $partial );
						}
					}
					break;

				case 'if-location':
				case 'if-start-location':
					if ( ! empty( $event->start_location['address'] ) ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				case 'if-end-location':
					if ( ! empty( $event->end_location['address'] ) ) {
						return $calendar->get_event_html( $event, $partial );
					}
					break;

				/* ========= *
				 * Meta Tags *
				 * ========= */

				case 'attachments' :
					if ( ! empty( $event->meta['attachments'] ) ) {

						$html = '<ul class="simcal-event-attachments">';

						foreach( $event->meta['attachments'] as $attachment ) {

						}

						$html .='</ul>';

						return $html;
					}
					break;

				case 'attendees' :
				case 'participants' :
					if ( ! empty( $event->meta['participants'] ) ) {

						$html = '<ul class="simcal-event-attendees">';

						foreach( $event->meta['participants'] as $attendee ) {
							$html .= '<li class="simcal-event-participant">';
							$html .= '<a href="mailto:' . $attendee['email'] . '">';
							$html .= $attendee['name'];
							$html .= '</a>';
							$html .= '</li>';
						}

						$html .='</ul>';

						return $html;
					}
					break;

				case 'organizer' :
					if ( ! empty( $event->meta['organizer'] ) ) {

						$html = '<div class="simcal-event-organizer">';
						$html .= '<a href="mailto:' . $event->meta['organizer']['email'] . '">';
						$html .= $event->meta['organizer']['name'];
						$html .= '</a>';
						$html .='</div>';

						return $html;
					}
					break;

				/* ======= *
				 * Default *
				 * ======= */

				default:
					return wp_kses_post( $before . $partial . $after );
			}
		}

		return '';
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
	 * @return string The tag search regular expression result
	 */
	private function get_regex() {

		// This is largely borrowed on get_shortcode_regex() from WordPress Core.
		// @see /wp-includes/shortcodes.php (with some modification)

		$tagregexp = implode( '|', array_values( $this->tags ) );

		return '/'
		       . '\\['                              // Opening bracket
		       . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
		       . "($tagregexp)"                     // 2: Shortcode name
		       . '(?![\\w-])'                       // Not followed by word character or hyphen
		       . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
		       .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
		       .     '(?:'
		       .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
		       .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
		       .     ')*?'
		       . ')'
		       . '(?:'
		       .     '(\\/)'                        // 4: Self closing tag ...
		       .     '\\]'                          // ... and closing bracket
		       . '|'
		       .     '\\]'                          // Closing bracket
		       .     '(?:'
		       .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
		       .             '[^\\[]*+'             // Not an opening bracket
		       .             '(?:'
		       .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
		       .                 '[^\\[]*+'         // Not an opening bracket
		       .             ')*+'
		       .         ')'
		       .         '\\[\\/\\2\\]'             // Closing shortcode tag
		       .     ')?'
		       . ')'
		       . '(\\]?)'                           // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
		       . '/s';
	}

}
