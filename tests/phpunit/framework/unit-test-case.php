<?php
/**
 * Test Case.
 */
namespace SimpleCalendar\Tests;

use SimpleCalendar\Post_Types;

/**
 * Test Case.
 */
class Unit_Test_Case extends \WP_UnitTestCase {

	/**
	 * Setup test case
	 */
	public function setUp() {

		parent::setUp();

		Post_Types::register_post_types();
		Post_Types::register_taxonomies();
	}

	/**
	 * Asserts thing is not WP_Error
	 *
	 * @param mixed  $actual
	 * @param string $message
	 */
	public function assertNotWPError( $actual, $message = '' ) {
		$this->assertNotInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts thing is WP_Error
	 *
	 * @param mixed  $actual
	 * @param string $message
	 */
	public function assertIsWPError( $actual, $message = '' ) {
		$this->assertInstanceOf( 'WP_Error', $actual, $message );
	}

}
