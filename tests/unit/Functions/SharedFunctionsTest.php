<?php

namespace SimpleCalendar\Tests\Unit\Functions;

use SimpleCalendar\Tests\Unit\TestCase;

class SharedFunctionsTest extends TestCase
{
    public function testEscTimezoneWithValidTimezone()
    {
        $this->assertSame('America/New_York', simcal_esc_timezone('America/New_York'));
    }

    public function testEscTimezoneWithInvalidTimezoneReturnsDefault()
    {
        $this->assertSame('UTC', simcal_esc_timezone('Invalid/Zone'));
    }

    public function testEscTimezoneWithCustomDefault()
    {
        $this->assertSame('Europe/London', simcal_esc_timezone('Invalid/Zone', 'Europe/London'));
    }

    public function testEscTimezoneWithEmptyStringReturnsDefault()
    {
        $this->assertSame('UTC', simcal_esc_timezone(''));
    }

    public function testEscTimezoneWithUtc()
    {
        $this->assertSame('UTC', simcal_esc_timezone('UTC'));
    }

    public function testGetTimezoneFromGmtOffsetZeroReturnsUtc()
    {
        $this->assertSame('UTC', simcal_get_timezone_from_gmt_offset(0));
    }

    public function testGetTimezoneFromGmtOffsetPositive()
    {
        $result = simcal_get_timezone_from_gmt_offset(5);
        $this->assertNotNull($result);
        $this->assertContains($result, timezone_identifiers_list());
    }

    public function testGetTimezoneFromGmtOffsetNegative()
    {
        $result = simcal_get_timezone_from_gmt_offset(-5);
        $this->assertNotNull($result);
        $this->assertContains($result, timezone_identifiers_list());
    }

    public function testGetTimezoneFromGmtOffsetFractional()
    {
        $result = simcal_get_timezone_from_gmt_offset(5.5);
        $this->assertNotNull($result);
        $this->assertContains($result, timezone_identifiers_list());
    }

    public function testGetTimezoneFromGmtOffsetNonNumericReturnsNull()
    {
        $this->assertNull(simcal_get_timezone_from_gmt_offset('abc'));
    }

    public function testGetTimezoneFromGmtOffsetStringNumberWorks()
    {
        $result = simcal_get_timezone_from_gmt_offset('0');
        $this->assertSame('UTC', $result);
    }

    public function testGetDateFormatOrderUsFormat()
    {
        $order = simcal_get_date_format_order('m/j/Y');
        $this->assertLessThan($order['d'], $order['m']);
        $this->assertLessThan($order['y'], $order['d']);
    }

    public function testGetDateFormatOrderEuropeanFormat()
    {
        $order = simcal_get_date_format_order('j/m/Y');
        $this->assertLessThan($order['m'], $order['d']);
        $this->assertLessThan($order['y'], $order['m']);
    }

    public function testGetDateFormatOrderIsoFormat()
    {
        $order = simcal_get_date_format_order('Y-m-j');
        $this->assertLessThan($order['m'], $order['y']);
        $this->assertLessThan($order['d'], $order['m']);
    }

    public function testGetDateFormatOrderWithTextMonth()
    {
        $order = simcal_get_date_format_order('F j, Y');
        $this->assertIsInt($order['m']);
        $this->assertIsInt($order['d']);
        $this->assertIsInt($order['y']);
    }

    public function testGetDateFormatOrderMissingComponentReturnsEmptyString()
    {
        $order = simcal_get_date_format_order('m/Y');
        $this->assertSame('', $order['d']);
        $this->assertIsInt($order['m']);
        $this->assertIsInt($order['y']);
    }
}
