<?php
/**
 * Calendar Widget
 *
 * @package SimpleCalendar\Widgets
 */
namespace SimpleCalendar\Widgets;

use SimpleCalendar\Abstracts\Calendar_View;
use SimpleCalendar\Abstracts\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calendar widget.
 *
 * Display calendars in a widget area.
 *
 * @since 3.0.0
 */
class Calendar extends \WP_Widget implements Widget {

	/**
	 * Calendar feeds.
	 *
	 * @access public
	 * @var array
	 */
	public $calendars = array();

	/**
	 * Calendar view.
	 *
	 * @access public
	 * @var Calendar_View
	 */
	public $view = null;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$id_base        = 'gce_widget'; // old id kept for legacy reasons
		$name           = __( 'Simple Calendar', 'google-calendar-events' );
		$widget_options = array(
			'description' => __( 'Display a calendar of events from one of your calendar feeds.', 'google-calendar-events' )
		);

		parent::__construct( $id_base, $name, $widget_options );

		if ( is_admin() ) {
			if ( ! defined( 'DOING_AJAX' ) ) {
				$this->calendars = simcal_get_calendars();
			} else {
				$this->calendars = get_transient( '_simple-calendar_feed_ids' );
			}
		} else {
			add_action( 'init', array( $this, 'load_assets' ) );
		}
	}

	/**
	 * Load assets for widgetized calendars.
	 *
	 * @since 3.0.0
	 */
	public function load_assets() {

		$settings = $this->get_settings();
		$settings = $settings ? array_pop( $settings ) : '';

		if ( isset( $settings['calendar_id'] ) ) {

			$view = simcal_get_calendar_view( absint( $settings['calendar_id'] ) );

			if ( $view instanceof Calendar_View ) {

				$this->view = $view;

				add_filter( 'simcal_front_end_scripts', function( $scripts, $min ) use ( $view ) {
					return array_merge( $scripts, $view->scripts( $min ) );
				}, 100, 2 );
				add_filter( 'simcal_front_end_styles', function( $styles, $min ) use ( $view ) {
					return array_merge( $styles, $view->styles( $min ) );
				}, 100, 2 );
			}
		}
	}

	/**
	 * Print the widget content.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args     Display arguments.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {

		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {

			$a_open = '';
			$a_close = '';

			if ( ! empty( $instance['title_url'] ) ) {
				$a_open  = '<a href="' . esc_url_raw( $instance['title_url'] ) . '">';
				$a_close = '</a>';
			}

			echo $args['before_title'] . $a_open . apply_filters( 'widget_title', $instance['title'] ). $a_close . $args['after_title'];
		}

		if ( ! empty( $instance['text_before'] ) ) {
			echo wp_kses_post( $instance['text_before'] );
		}

		$id = isset( $instance['calendar_id'] ) ? absint( $instance['calendar_id'] ) : 0;
		if ( $id > 0 ) {
			simcal_print_calendar( $id );
		}

		if ( ! empty( $instance['text_after'] ) ) {
			echo wp_kses_post( $instance['text_after'] );
		}

		echo $args['after_widget'];
	}

	/**
	 * Update a particular instance of the widget.
	 *
	 * This function should check that $new_instance is set correctly.
	 * The newly-calculated value of `$instance` should be returned.
	 * If false is returned, the instance won't be saved/updated.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $new_instance New settings for this instance as input by the user via
	 * @param  array $old_instance Old settings for this instance.
	 *
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = array();

		$instance['title']          = ( ! empty( $new_instance['title'] ) )        ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['title_url']      = ( ! empty( $new_instance['title_url'] ) )    ? sanitize_text_field( $new_instance['title_url'] ) : '';
		$instance['text_before']    = ( ! empty( $new_instance['text_before'] ) )  ? wp_kses_post( $new_instance['text_before'] ) : '';
		$instance['calendar_id']    = ( ! empty( $new_instance['calendar_id'] ) )  ? absint( $new_instance['calendar_id'] ) : '';
		$instance['text_after']     = ( ! empty( $new_instance['text_after'] ) )   ? wp_kses_post( $new_instance['text_after'] ) : '';

		return $instance;
	}

	/**
	 * Print the settings update form.
	 *
	 * @since  3.0.0
	 *
	 * @param  array  $instance Current settings.
	 *
	 * @return string
	 */
	public function form( $instance ) {

		$title          = isset( $instance['title'] )       ? esc_attr( $instance['title'] ) : __( 'Calendar', 'google-calendar-events' );
		$title_url      = isset( $instance['title_url'] )   ? esc_url_raw( $instance['title_url'] ) : '';
		$text_before    = isset( $instance['text_before'] ) ? esc_textarea( $instance['text_before'] ) : '';
		$calendar_id    = isset( $instance['calendar_id'] ) ? esc_attr( $instance['calendar_id'] ) : '';
		$text_after     = isset( $instance['text_after'] )  ? esc_textarea( $instance['text_after'] ) : '';

		?>
		<div class="simcal-calendar-widget-settings">

			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'google-calendar-events' ); ?></label>
				<br>
				<input type="text"
				       name="<?php echo $this->get_field_name( 'title' ); ?>"
				       id="<?php echo $this->get_field_id( 'title' ); ?>"
				       class="widefat simcal-field simcal-field-standard simcal-field-text"
				       value="<?php echo $title; ?>">
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'title_url' ); ?>"><?php _e( 'Title URL:', 'google-calendar-events' ); ?></label>
				<br>
				<input type="text"
				       name="<?php echo $this->get_field_name( 'title_url' ); ?>"
				       id="<?php echo $this->get_field_id( 'title_url' ); ?>"
				       class="widefat simcal-field simcal-field-standard simcal-field-text"
				       value="<?php echo $title_url; ?>">
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'text_before' ); ?>"><?php _e( 'Text before:', 'google-calendar-events' ); ?></label>
				<br>
				<textarea name="<?php echo $this->get_field_name( 'text_before' ); ?>"
				          id="<?php echo $this->get_field_id( 'text_before' ); ?>"
				          class="widefat simcal-field simcal-field-textarea"><?php echo $text_before; ?></textarea>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'calendar_id' ); ?>"><?php _e( 'Calendar:', 'google-calendar-events' ); ?></label>
				<br>
				<select name="<?php echo $this->get_field_name( 'calendar_id' ) ?>"
				        id="<?php echo $this->get_field_id( 'calendar_id' ) ?>"
						class="simcal-field simcal-field-select simcal-field-select-enhanced"
						data-noresults="<?php __( 'No calendars found.', 'google-calendar-events' ); ?>">
						<?php foreach ( $this->calendars as $id => $name ) : ?>
							<option value="<?php echo $id; ?>" <?php selected( $id, $calendar_id, true ); ?>><?php echo $name; ?></option>
						<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'text_after' ); ?>"><?php _e( 'Text after:', 'google-calendar-events' ); ?></label>
				<br>
				<textarea name="<?php echo $this->get_field_name( 'text_after' ); ?>"
				          id="<?php echo $this->get_field_id( 'text_after' ); ?>"
				          class="widefat simcal-field simcal-field-textarea"><?php echo $text_after; ?></textarea>
			</p>

		</div>
		<?php

	}

}
