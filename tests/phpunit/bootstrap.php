<?php
/**
 * Bootstrap testing environment.
 *
 * @package SimpleCalendar\Tests
 */
namespace SimpleCalendar\Tests;

use SimpleCalendar\Installation;

/**
 * PhpUnit Tests Bootstrap.
 */
class Bootstrap {

	/**
	 * Instance of this class.
	 *
	 * @var Bootstrap
	 */
	protected static $instance = null;

	/** @var string */

	/**
	 * Directory where wordpress-tests-lib is installed.
	 *
	 * @var string
	 */
	public $wp_tests_dir;

	/**
	 * Testing directory.
	 *
	 * @var string
	 */
	public $tests_dir;

	/**
	 * Plugin directory.
	 *
	 * @var string
	 */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment.
	 */
	public function __construct() {

		ini_set( 'display_errors','on' );
		error_reporting( E_ALL );

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( dirname( $this->tests_dir ) );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : $this->plugin_dir . '/tmp/wordpress-tests-lib';

		// Load test function so tests_add_filter() is available.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// Load plugin.
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_plugin' ) );

		// Install plugin.
		tests_add_filter( 'setup_theme', array( $this, 'install_plugin' ) );

		// Load WordPress testing environment
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// Load testing framework.
		$this->includes();
	}

	/**
	 * Load plugin.
	 */
	public function load_plugin() {
		require_once $this->plugin_dir . '/google-calendar-events.php';
	}

	/**
	 * Install plugin.
	 */
	public function install_plugin() {

		// Clean existing install first.
		define( 'WP_UNINSTALL_PLUGIN', true );
		include $this->plugin_dir . '/uninstall.php';

		Installation::activate();

		$GLOBALS['wp_roles']->reinit();
		echo "Installing plugin..." . PHP_EOL;
	}

	/**
	 * Load testing framework.
	 */
	public function includes() {
		require_once 'framework/unit-test-case.php';
	}

	/**
	 * Get the single class instance.
	 *
	 * @return Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}

Bootstrap::instance();
