<?php
/**
 * Input Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Abstracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Field.
 *
 * Object for handling an input field in plugin back end.
 *
 * @since 3.0.0
 */
abstract class Field {

	/**
	 * Field type.
	 *
	 * @access public
	 * @var string
	 */
	public $type = '';

	/**
	 * Field name.
	 *
	 * @access public
	 * @var string
	 */
	public $name = '';

	/**
	 * Field Id.
	 *
	 * @access public
	 * @var string
	 */
	public $id = '';

	/**
	 * Field title (label).
	 *
	 * @access public
	 * @var string
	 */
	public $title = '';

	/**
	 * Field type class.
	 *
	 * @access protected
	 * @var string
	 */
	protected $type_class = '';

	/**
	 * CSS classes.
	 *
	 * @access public
	 * @var string
	 */
	public $class = '';

	/**
	 * CSS styles.
	 *
	 * @access public
	 * @var string
	 */
	public $style = '';

	/**
	 * Description.
	 *
	 * @access public
	 * @var string
	 */
	public $description = '';

	/**
	 * Tooltip text.
	 *
	 * @access public
	 * @var string
	 */
	public $tooltip = '';

	/**
	 * Attributes.
	 *
	 * @access public
	 * @var string
	 */
	public $attributes = '';

	/**
	 * Placeholder.
	 *
	 * @access public
	 * @var string
	 */
	public $placeholder = '';

	/**
	 * Options.
	 *
	 * @access public
	 * @var array
	 */
	public $options = array();

	/**
	 * Value.
	 *
	 * @access public
	 * @var array|string
	 */
	public $value = '';

	/**
	 * Default value.
	 *
	 * @access public
	 * @var array|string
	 */
	public $default = '';

	/**
	 * Validation result.
	 *
	 * @access public
	 * @var string|true
	 */
	public $validation = true;

	/**
	 * Construct the field.
	 *
	 * Escapes and sets every field property.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field Field data.
	 */
	public function __construct( $field ) {

		// Field properties.
		if ( isset( $field['title'] ) ) {
			$this->title = esc_attr( $field['title'] );
		}
		if ( isset( $field['description'] ) ) {
			$this->description = wp_kses_post( $field['description'] );
		}
		if ( isset( $field['type'] ) ) {
			$this->type = esc_attr( $field['type'] );
		}
		if ( isset( $field['name'] ) ) {
			$this->name = esc_attr( $field['name'] );
		}
		if ( isset( $field['id'] ) ) {
			$this->id = esc_attr( $field['id'] );
		}
		if ( isset( $field['placeholder'] ) ) {
			$this->placeholder = esc_attr( $field['placeholder'] );
		}
		if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
			$this->options = array_map( 'esc_attr', $field['options'] );
		}

		// Escaping.
		if ( ! empty( $field['escaping'] ) && ( is_string( $field['escaping'] ) || is_array( $field['escaping'] ) ) ) {
			if ( isset( $field['default'] ) ) {
				$this->default = $this->escape_callback( $field['escaping'], $field['default'] );
			}
			if ( isset( $field['value'] ) ) {
				$this->value = $this->escape_callback( $field['escaping'], $field['value'] );
			}
		} else {
			if ( isset( $field['default'] ) ) {
				$this->default = $this->escape( $field['default'] );
			}
			if ( isset( $field['value'] ) ) {
				$this->value = $this->escape( $field['value'] );
			}
		}

		// Validation.
		if ( ! empty( $field['validation'] ) ) {
			$this->validation = $this->validate( $field['validation'], $this->value );
		}

		// CSS classes and styles.
		$classes = isset( $field['class'] ) ? $field['class'] : '';
		$this->set_class( $classes );
		if ( isset( $field['style'] ) ) {
			$this->set_style( $field['style'] );
		}

		// Custom attributes.
		if ( isset( $field['attributes'] ) ) {
			$this->set_attributes( $field['attributes'] );
		}

		// Tooltip markup.
		if ( isset( $field['tooltip'] ) ) {
			$this->tooltip = ' <i class="simcal-icon-help simcal-help-tip" data-tip="' . esc_attr( $field['tooltip'] ) . '"></i> ' ;
		}
	}

	/**
	 * Set custom HTML attributes.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $attributes
	 *
	 * @return void
	 */
	public function set_attributes( $attributes ) {

		$attr = '';

		if ( ! empty( $attributes ) && is_array( $attributes ) ) {
			foreach ( $attributes as $k => $v ) {
				$attr .= esc_attr( $k ) . '="' . esc_attr( $v ) . '" ';
			}
		}

		$this->attributes = $attr;
	}

	/**
	 * Set CSS styles.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $css
	 *
	 * @return void
	 */
	public function set_style( $css ) {

		$styles = '';

		if ( ! empty( $css ) && is_array( $css ) ) {
			foreach ( $css as $k => $v ) {
				$styles .= esc_attr( $k ) . ': ' . esc_attr( $v ) . '; ';
			}
		}

		$this->style = $styles;
	}

	/**
	 * Set classes.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $class
	 *
	 * @return void
	 */
	public function set_class( $class ) {

		$classes = '';
		$type_class = '';
		$error = '';

		if ( ! empty( $class ) && is_array( $class ) ) {
			$classes = implode( ' ', array_map( 'esc_attr', $class ) );
		}
		if ( ! empty( $this->type_class ) ) {
			$type_class = esc_attr( $this->type_class );
		}
		if ( true !== $this->validation && ! empty( $this->validation ) ) {
			$error = 'simcal-field-error ';
		}

		$this->class = trim( $error . 'simcal-field ' . $type_class . ' ' . $classes );
	}

	/**
	 * Escape field value.
	 *
	 * Default escape function, a wrapper for esc_attr().
	 *
	 * @since  3.0.0
	 * @access protected
	 *
	 * @param  array|string|int $value
	 *
	 * @return array|string
	 */
	protected function escape( $value )  {
		return ! empty( $value ) ? ( is_array( $value ) ? array_map( 'esc_attr', $value ) : esc_attr( $value ) ) : '';
	}

	/**
	 * Escape callback.
	 *
	 * Custom escaping function set in field args.
	 *
	 * @since  3.0.0
	 * @access protected
	 *
	 * @param  array|string $callback
	 * @param  mixed        $value
	 *
	 * @return mixed
	 */
	protected function escape_callback( $callback, $value ) {
		if ( $callback && ( is_string( $callback ) || is_array( $callback ) ) ) {
			return call_user_func( $callback, $value );
		}
		return esc_attr( $value );
	}

	/**
	 * Validation callback.
	 *
	 * Custom field validation callback set in field args.
	 *
	 * @since  3.0.0
	 * @access protected
	 *
	 * @param  array|string $callback
	 * @param  string       $value
	 *
	 * @return true|string Expected to return bool (true) if passes, message string if not.
	 */
	protected function validate( $callback, $value ) {
		if ( $callback && ( is_string( $callback ) || is_array( $callback ) ) ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : '';
			return call_user_func( $callback, $value, $screen );
		}
		return true;
	}

	/**
	 * Outputs the field markup.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	abstract public function html();

}
