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
	 * Markup context.
	 *
	 * @access public
	 * @var string
	 */
	public $context = '';

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
	 * @param array $field Field data.
	 */
	public function __construct( $field ) {

		// Context.
		$this->context = isset( $field['context'] ) ? $field['context'] : '';

		// Field properties.
		$this->title        = isset( $field['title'] )       ? esc_attr( $field['title'] ) : '';
		$this->description  = isset( $field['description'] ) ? wp_kses_post( $field['description'] ) : '';
		$this->type         = isset( $field['type'] )        ? esc_attr( $field['type'] ) : '';
		$this->name         = isset( $field['name'] )        ? esc_attr( $field['name'] ) : '';
		$this->id           = isset( $field['id'] )          ? esc_attr( $field['id'] ) : '';
		$this->placeholder  = isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$this->options      = isset( $field['options'] )     ? array_map( 'esc_attr', (array) $field['options'] ) : array();

		// Escaping.
		$escaping = isset( $field['escaping'] ) ? $field['escaping'] : '';
		if ( ! empty( $escaping ) && ( is_string( $escaping ) || is_array( $escaping ) ) ) {
			$this->default = isset( $field['default'] ) ? $this->escape_callback( $escaping, $field['default'] ) : '';
			$this->value   = isset( $field['value'] )   ? $this->escape_callback( $escaping, $field['value'] ) : '';
		} else {
			$this->default = isset( $field['default'] ) ? $this->escape( $field['default'] ) : '';
			$this->value   = isset( $field['value'] )   ? $this->escape( $field['value'] ) : '';
		}

		// Validation.
		$callback = isset( $field['validation'] ) ? $field['validation'] : '';
		$this->validation = ! empty( $callback ) ? $this->validate( $callback, $this->value ) : true;

		// CSS classes and styles.
		$class       = isset( $field['class'] ) ? implode( ' ', array_map( 'esc_attr', $field['class'] ) ) : '';
		$type_class  = $this->type_class ? esc_attr( $this->type_class ) : '';
		$error       = $this->validation !== true && ! empty( $this->validation ) ? 'simcal-field-error ' : '';
		$this->class = trim( $error . 'simcal-field ' . $type_class . ' ' . $class );
		$this->style = '';
		if ( isset( $field['style'] ) ) {
			if ( $field['style'] && is_array( $field['style'] ) ) {
				foreach ( $field['style'] as $k => $v ) {
					$this->style .= esc_attr( $k ) . ': ' . esc_attr( $v ) . '; ';
				}
			}
		}

		// Custom attributes.
		$this->attributes = '';
		if ( isset( $field['attributes'] ) ) {
			if ( ! empty( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
				foreach ( $field['attributes'] as $k => $v ) {
					$this->attributes .= esc_attr( $k ) . '="' . esc_attr( $v ) . '" ';
				}
			}
		}

		// Tooltip markup.
		$this->tooltip = isset( $field['tooltip'] ) ? ' <i class="simcal-icon-help simcal-help-tip" data-tip="' . esc_attr( $field['tooltip'] ) . '"></i> ' : '';

	}

	/**
	 * Escape field value.
	 *
	 * Default escape function, a wrapper for esc_attr().
	 *
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
	 * @return void
	 */
	abstract public function html();

}
