<?php
/**
 * Datetime Formatter Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Datetime formatter field.
 *
 * A special field to choose a format for date and time.
 */
class Datetime_Format extends Field {

	/**
	 * Datetime controls.
	 *
	 * @access public
	 * @var string 'date' or 'time'
	 */
	public $subtype = '';

	/**
	 * Timestamp.
	 *
	 * @access private
	 * @var int
	 */
	private $timestamp = 0;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field
	 */
	public function __construct( $field ) {

		$this->subtype     = isset( $field['subtype'] ) ? $field['subtype'] : '';
		$this->type_class  = 'simcal-field-datetime-format simcal-field-' . $this->subtype . '-format-field';
		$this->timestamp   = mktime( 13, 10, 00, 1, 1, intval( date( 'Y', time() ) ) + 1 );

		parent::__construct( $field );

		if ( empty( $this->value ) ) {
			if ( 'date' == $this->subtype ) {
				$this->value = 'l, d F Y';
			}
			if ( 'time' == $this->subtype ) {
				$this->value = 'G:i a';
			}
		}
	}

	/**
	 * Output field markup.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		$id     = $this->id     ? ' id="' . $this->id . '" ' : '';
		$class  = $this->class  ? ' class="' . $this->class . '" ' : '';
		$style  = $this->style  ? ' style="' . $this->style . '" ' : '';
		$attr   = $this->attributes;

		?>
		<div <?php echo $id . $class . $style . $attr; ?>>
			<?php

			if ( ! empty( $this->description ) ) {
				echo '<p class="description">' . $this->description . '</p>';
			}

			$matches = array_unique( str_split( $this->value ) );

			if ( 'date' == $this->subtype ) {
				$this->print_date( $matches );
			}

			if ( 'time' == $this->subtype ) {
				$this->print_time( $matches );
			}

			?>
			<input type="hidden"
			       name="<?php echo $this->name; ?>"
			       value="<?php echo trim( $this->value ); ?>" />
			<span>
				<em><?php _e( 'Preview', 'google-calendar-events' ); ?>:</em>&nbsp;&nbsp;
				<code><?php echo date_i18n( $this->value, $this->timestamp ); ?></code>
			</span>
		</div>
		<?php

	}

	/**
	 * Print date input.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  array $matches
	 */
	private function print_date( $matches ) {

		$date = array( 'weekday' => '', 'divider' => '', 'day' => '', 'month' => '', 'year' => '' );

		foreach ( $matches as $match ) {
			if ( in_array( $match, array( 'D', 'l' ) ) ) {
				$this->weekday();
				unset( $date['weekday'] );
			} elseif ( in_array( $match, array( 'd', 'j' ) ) ) {
				$this->day();
				unset( $date['day'] );
			} elseif ( in_array( $match, array( 'F', 'M', 'm', 'n' ) ) ) {
				$this->month();
				unset( $date['month'] );
			} elseif ( in_array( $match, array( 'y', 'Y' ) ) ) {
				$this->year();
				unset( $date['year'] );
			} elseif ( in_array( $match, array( '.', ',', ':', '/', '-' ) ) ) {
				$this->divider();
				unset( $date['divider'] );
			}
		}

		$this->print_fields( $date );
	}

	/**
	 * Print time input.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  array $matches
	 */
	private function print_time( $matches ) {

		$time = array( 'hours' => '', 'divider' => '', 'minutes' => '', 'meridiem' => '' );

		foreach ( $matches as $match  ) {
			if ( in_array( $match, array( 'h', 'H', 'g', 'G' ) ) ) {
				$this->hours();
				unset( $time['hours'] );
			} elseif ( in_array( $match, array( 'i' ) )  ) {
				$this->minutes();
				unset( $time['minutes'] );
			} elseif ( in_array( $match, array( 'A', 'a' ) ) ) {
				$this->meridiem();
				unset( $time['meridiem'] );
			} elseif ( in_array( $match, array( '.', ',', ':', '/', '-' ) ) ) {
				$this->divider();
				unset( $time['divider'] );
			}
		}

		$this->print_fields( $time );
	}

	/**
	 * Print input fields.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  $fields
	 */
	private function print_fields( $fields ) {
		if ( ! empty( $fields ) && is_array( $fields ) ) {
			foreach ( $fields as $func => $v ) {
				if ( method_exists( $this, $func ) ) {
					$this->$func();
				};
			}
		}
	}

	/**
	 * Print weekday input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function weekday() {

		?>
		<div>
			<label for="<?php echo $this->id; ?>-weekday">
				<?php _e( 'Weekday', 'google-calendar-events' ); ?>
				<select name="" id="<?php echo $this->id; ?>-weekday">
					<option value="" data-preview=""></option>
					<option value="D" <?php selected( 'D', strpbrk( 'D', $this->value ) ) ?> data-preview="<?php echo date_i18n( 'D', $this->timestamp ); ?>"><?php echo date_i18n( 'D', $this->timestamp ); ?></option>
					<option value="l" <?php selected( 'l', strpbrk( 'l', $this->value ) ) ?> data-preview="<?php echo date_i18n( 'l', $this->timestamp ); ?>"><?php echo date_i18n( 'l', $this->timestamp ); ?></option>
				</select>
			</label>
		</div>
		<?php

	}

	/**
	 * Print day input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function day() {

		?>
		<div>
			<label for="<?php echo $this->id; ?>-day">
				<?php _e( 'Day', 'google-calendar-events' ); ?>
				<select name="" id="<?php echo $this->id; ?>-day">
					<option value="" data-preview=""></option>
					<option value="j" <?php selected( 'j', strpbrk( 'j', $this->value ) ) ?> data-preview="<?php echo date( 'j', $this->timestamp ); ?>"><?php echo date( 'j', $this->timestamp ); ?></option>
					<option value="d" <?php selected( 'd', strpbrk( 'd', $this->value ) ) ?> data-preview="<?php echo date( 'd', $this->timestamp ); ?>"><?php echo date( 'd', $this->timestamp ); ?></option>
				</select>
			</label>
		</div>
		<?php

	}

	/**
	 * Print month input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function month() {

		?>
		<div>
			<label for="<?php echo $this->id; ?>-month">
				<?php _e( 'Month', 'google-calendar-events' ); ?>
				<select name="" id="<?php echo $this->id; ?>-month">
					<option value="" data-preview=""></option>
					<option value="F" <?php selected( 'F', strpbrk( 'F', $this->value ) ) ?> data-preview="<?php echo date_i18n( 'F', $this->timestamp ); ?>"><?php echo date_i18n( 'F', $this->timestamp ); ?></option>
					<option value="M" <?php selected( 'M', strpbrk( 'M', $this->value ) ) ?> data-preview="<?php echo date_i18n( 'M', $this->timestamp ); ?>"><?php echo date_i18n( 'M', $this->timestamp ); ?></option>
					<option value="m" <?php selected( 'm', strpbrk( 'm', $this->value ) ) ?> data-preview="<?php echo date( 'm', $this->timestamp ); ?>"><?php echo date( 'm', $this->timestamp ); ?></option>
					<option value="n" <?php selected( 'n', strpbrk( 'n', $this->value ) ) ?> data-preview="<?php echo date( 'n', $this->timestamp ); ?>"><?php echo date( 'n', $this->timestamp ); ?></option>
				</select>
			</label>
		</div>
		<?php

	}

	/**
	 * Print year input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function year() {

		?>
		<div>
			<label for="<?php echo $this->id; ?>-year">
				<?php _e( 'Year', 'google-calendar-events' ); ?>
				<select name="" id="<?php echo $this->id; ?>-year">
					<option value="" data-preview=""></option>
					<option value="Y" <?php selected( 'Y', strpbrk( 'Y', $this->value ) ) ?> data-preview="<?php echo date( 'Y', $this->timestamp ); ?>"><?php echo date( 'Y', $this->timestamp ); ?></option>
					<option value="y" <?php selected( 'y', strpbrk( 'y', $this->value ) ) ?> data-preview="<?php echo date( 'y', $this->timestamp ); ?>"><?php echo date( 'y', $this->timestamp ); ?></option>
				</select>
			</label>
		</div>
		<?php

	}

	/**
	 * Print hours input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function hours() {

		?>
		<div>
			<label for="<?php echo $this->id; ?>-hours">
				<?php _e( 'Hours', 'google-calendar-events' ) ?>
				<select name="" id="<?php echo $this->id; ?>-hours">
					<option value="" data-preview=""></option>
					<option value="g" <?php selected( 'g', strpbrk( 'g', $this->value ) ); ?> data-preview="<?php echo date( 'g', $this->timestamp ); ?>"><?php echo date( 'g', $this->timestamp ) . ' (12h)'; ?></option>
					<option value="G" <?php selected( 'G', strpbrk( 'G', $this->value ) ); ?> data-preview="<?php echo date( 'G', $this->timestamp - 43200 ); ?>"><?php echo date( 'G', $this->timestamp - 43200 ) . ' (24h)'; ?></option>
					<option value="h" <?php selected( 'h', strpbrk( 'h', $this->value ) ); ?> data-preview="<?php echo date( 'h', $this->timestamp ); ?>"><?php echo date( 'h', $this->timestamp ) . ' (12h)'; ?></option>
					<option value="H" <?php selected( 'H', strpbrk( 'H', $this->value ) ); ?> data-preview="<?php echo date( 'H', $this->timestamp - 43200 ); ?>"><?php echo date( 'H', $this->timestamp - 43200 ) . ' (24h)'; ?></option>
				</select>
			</label>
		</div>
		<?php

	}

	/**
	 * Print minutes input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function minutes() {

		?>
		<div>
			<label for="<?php echo $this->id; ?>-minutes">
				<?php _e( 'Minutes', 'google-calendar-events' ); ?>
				<select name="" id="<?php echo $this->id; ?>-minutes">
					<option value="" data-preview=""></option>
					<option value="i" <?php selected( 'i', strpbrk( 'i', $this->value ) ); ?> data-preview="<?php echo date( 'i', $this->timestamp ); ?>"><?php echo date( 'i', $this->timestamp ); ?></option>
				</select>
			</label>
		</div>
		<?php

	}

	/**
	 * Print meridiem input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function meridiem() {

	    ?>
		<div>
			<label for="<?php echo $this->id; ?>-meridiem">
				<?php _e( 'Meridiem', 'google-calendar-events' ); ?>
				<select name="" id="<?php echo $this->id; ?>-meridiem">
					<option value="" data-preview=""></option>
					<option value="a" <?php selected( 'a', strpbrk( 'a', $this->value ) ); ?> data-preview="<?php echo date( 'a', $this->timestamp ); ?>"><?php echo date( 'a', $this->timestamp ); ?></option>
					<option value="A" <?php selected( 'A', strpbrk( 'A', $this->value ) ); ?> data-preview="<?php echo date( 'A', $this->timestamp ); ?>"><?php echo date( 'A', $this->timestamp ); ?></option>
				</select>
			</label>
		</div>
		<?php

	}

	/**
	 * Print divider input.
	 *
	 * @since  3.0.0
	 * @access private
	 */
	private function divider() {

		?>
		<div>
			<label for="<?php echo $this->id; ?>-divider">
				<?php _ex( 'Divider', 'A character to separate two elements', 'google-calendar-events' ); ?>
				<select name="" id="<?php echo $this->id; ?>-divider">
					<option value="" data-preview=""></option>
					<option value="."  <?php selected( '.', strpbrk( '.', $this->value ) ); ?> data-preview="."  data-trim="true">.</option>
					<option value=", " <?php selected( ',', strpbrk( ',', $this->value ) ); ?> data-preview=", " data-trim="true">,</option>
					<option value=":"  <?php selected( ':', strpbrk( ':', $this->value ) ); ?> data-preview=":"  data-trim="true">:</option>
					<option value="-"  <?php selected( '-', strpbrk( '-', $this->value ) ); ?> data-preview="-"  data-trim="true">-</option>
					<option value="/"  <?php selected( '/', strpbrk( '/', $this->value ) ); ?> data-preview="/"  data-trim="true">/</option>
				</select>
			</label>
		</div>
		<?php

	}

}
