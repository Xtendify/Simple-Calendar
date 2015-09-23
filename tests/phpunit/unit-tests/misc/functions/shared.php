<?php
namespace SimpleCalendar\Tests;

class Shared_functions extends Unit_Test_Case {

	public function test_common_scripts_variables() {

		$vars = simcal_common_scripts_variables();

		$this->assertArrayHasKey( 'ajax_url', $vars );
		$this->assertArrayHasKey( 'nonce',    $vars );
		$this->assertArrayHasKey( 'locale',   $vars );
		$this->assertArrayHasKey( 'text_dir', $vars );
		$this->assertArrayHasKey( 'months',   $vars );
		$this->assertArrayHasKey( 'days',     $vars );
		$this->assertArrayHasKey( 'meridiem', $vars );

		$locale = get_locale();
		$this->assertEquals( $locale, $vars['locale'] );

		$rtl = is_rtl() ? 'rtl' : 'ltr';
		$this->assertEquals( $rtl, $vars['text_dir'] );
	}

	public function test_get_date_format_order() {

		// The order is day < month < year.
		$date_order = simcal_get_date_format_order( 'j F Y' );
		$this->assertGreaterThan( $date_order['d'], $date_order['m'] );
		$this->assertGreaterThan( $date_order['m'], $date_order['y'] );

		// The order is month < year < day.
		$date_order = simcal_get_date_format_order( 'F j Y' );
		$this->assertGreaterThan( $date_order['m'], $date_order['y'] );
		$this->assertGreaterThan( $date_order['d'], $date_order['y'] );

		// The order is year < month. No day in format string (false).
		$date_order = simcal_get_date_format_order( 'x Y 1 m abc ' );
		$this->assertGreaterThan( $date_order['y'], $date_order['m'] );
		$this->assertEquals( $date_order['d'], false );

	}

	public function test_get_wp_timezone() {

		$timezone = simcal_get_wp_timezone();
		$datetime = new \DateTimeZone( $timezone );

		// Returns a valid DateTimeZone.
		$this->assertInstanceOf( '\DateTimeZone', $datetime );
	}

	public function test_get_timezone_from_gmt_offset() {

		// UTC
		$offset = simcal_get_timezone_from_gmt_offset( 0 );
		$timezone = new \DateTimeZone( $offset );
		$datetime = new \DateTime( 'now', $timezone );
		$hours = $timezone->getOffset( $datetime );

		$this->assertInstanceOf( '\DateTimeZone', $timezone );
		$this->assertEquals( 0, $hours );

		// @todo needs tests adjusted by DST (should catch DST)
	}

	public function test_get_timezone_offset() {

		// GMT has no offset.
		$offset = simcal_get_timezone_offset( 'UTC' );
		$this->assertEquals( 0, $offset );

		// Minus 3 hours.
		$offset = simcal_get_timezone_offset( 'America/Sao_Paulo' );
		$this->assertEquals( -10800, $offset );

		// Plus 2 hours.
		$offset = simcal_get_timezone_offset( 'Europe/Rome' );
		$this->assertEquals( 7200, $offset );

	}

	public function test_get_calendar_names_i18n() {

		// 0-6 week days.
		$days = simcal_get_calendar_names_i18n( 'day', 'full' );
		$this->assertArrayHasKey( 0, $days );
		$this->assertArrayHasKey( 1, $days );
		$this->assertArrayHasKey( 2, $days );
		$this->assertArrayHasKey( 3, $days );
		$this->assertArrayHasKey( 4, $days );
		$this->assertArrayHasKey( 5, $days );
		$this->assertArrayHasKey( 6, $days );

		// 0-11 months.
		$months = simcal_get_calendar_names_i18n( 'month', 'full' );
		$this->assertArrayHasKey( 0, $months );
		$this->assertArrayHasKey( 1, $months );
		$this->assertArrayHasKey( 2, $months );
		$this->assertArrayHasKey( 3, $months );
		$this->assertArrayHasKey( 4, $months );
		$this->assertArrayHasKey( 5, $months );
		$this->assertArrayHasKey( 6, $months );
		$this->assertArrayHasKey( 7, $months );
		$this->assertArrayHasKey( 8, $months );
		$this->assertArrayHasKey( 9, $months );
		$this->assertArrayHasKey( 10, $months );
		$this->assertArrayHasKey( 11, $months );

		// Meridiem.
		$meridiem = simcal_get_calendar_names_i18n( 'meridiem' );
		$this->assertArrayHasKey( 'am', $meridiem );
		$this->assertArrayHasKey( 'AM', $meridiem );
		$this->assertArrayHasKey( 'pm', $meridiem );
		$this->assertArrayHasKey( 'PM', $meridiem );

	}

}
