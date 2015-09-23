<?php
namespace SimpleCalendar\Tests;

class Admin_Functions extends Unit_Test_Case {

	public function test_sanitize_input() {

		$null = simcal_sanitize_input( null );
		$this->assertEquals( '', $null );

		$true = simcal_sanitize_input( true );
		$this->assertEquals( 'yes', $true );

		$false = simcal_sanitize_input( false );
		$this->assertEquals( 'no', $false );

		$string = simcal_sanitize_input( 'string' );
		$this->assertEquals( 'string', $string );

		$int = simcal_sanitize_input( 1 );
		$this->assertEquals( '1', $int );

		$html = simcal_sanitize_input( '<tag>test</tag>' );
		$this->assertEquals( 'test', $html );

		$array = simcal_sanitize_input( array(
			'test' => 'test'
		) );
		$this->assertArrayHasKey( 'test', $array );
		$this->assertEquals( 'test', $array['test'] );

		$htmlArray = simcal_sanitize_input( array(
			'test' => '<test>test</test>'
		) );
		$this->assertArrayHasKey( 'test', $htmlArray );
		$this->assertEquals( 'test', $htmlArray['test'] );

	}

}
