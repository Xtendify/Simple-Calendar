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
	 * @param array $args     Display arguments.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {

		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}

		$id = isset( $instance['calendar_id'] ) ? absint( $instance['calendar_id'] ) : 0;
		if ( $id > 0 ) {
			simcal_print_calendar( $id );
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
	 * @param  array $new_instance New settings for this instance as input by the user via
	 * @param  array $old_instance Old settings for this instance.
	 *
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = array();

		$instance['title']       = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['calendar_id'] = ( ! empty( $new_instance['calendar_id'] ) ) ? absint( $new_instance['calendar_id'] ) : '';

		return $instance;
	}

	/**
	 * Print the settings update form.
	 *
	 * @param  array  $instance Current settings.
	 *
	 * @return string
	 */
	public function form( $instance ) {

		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Calendar', 'google-calendar-events' );
		$calendar_id = isset( $instance['calendar_id'] ) ? $instance['calendar_id'] : '';

		?>
		<div class="simcal-calendar-widget-settings">

			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'google-calendar-events' ); ?></label>
				<br>
				<input type="text"
				       name="<?php echo $this->get_field_name( 'title' ); ?>"
				       id="<?php echo $this->get_field_id( 'title' ); ?>"
				       class="widefat simcal-field simcal-field-standard simcal-field-text"
				       value="<?php echo esc_attr( $title ); ?>">
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

		</div>
		<?php

	}

}
