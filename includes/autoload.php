<?php
/**
 * Autoloader
 *
 * @package SimpleCalendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'SimpleCalendar_Autoload' ) ) {

	/**
	 * Plugin autoloader.
	 *
	 * Pattern (with or without <Subnamespace>):
	 *  <Namespace>/<Subnamespace>.../Class_Name (or Classname)
	 *  'includes/subdir.../class-name.php' or '...classname.php'
	 *
	 * @since 3.0.0
	 *
	 * @param $class
	 */
	function SimpleCalendar_Autoload( $class ) {

		// Do not load unless in plugin domain.
		$namespace = 'SimpleCalendar';
		if ( strpos( $class, $namespace ) !== 0 ) {
			return;
		}

		// Converts Class_Name (class convention) to class-name (file convention).
		$class_name = implode( '-', array_map( 'lcfirst', explode( '_', strtolower( $class ) ) ) );

		// Remove the root namespace.
		$unprefixed = substr( $class_name, strlen( $namespace ) );

		// Build the file path.
		$file_path = str_replace( '\\', DIRECTORY_SEPARATOR, $unprefixed );
		$file      = dirname( __FILE__ ) . '/' . $file_path . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}

	}

	// Register the autoloader.
	spl_autoload_register( 'SimpleCalendar_Autoload' );

}
