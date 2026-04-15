<?php

namespace SimpleCalendar\Tests\Unit\Functions;

use SimpleCalendar\Tests\Unit\TestCase;

class AdminFunctionsTest extends TestCase
{
    public function testNullReturnsEmpty()
    {
        $this->assertSame('', simcal_sanitize_input(null));
    }

    public function testTrueReturnsYes()
    {
        $this->assertSame('yes', simcal_sanitize_input(true));
    }

    public function testFalseReturnsNo()
    {
        $this->assertSame('no', simcal_sanitize_input(false));
    }

    public function testStringPassesThrough()
    {
        $this->assertSame('hello world', simcal_sanitize_input('hello world'));
    }

    public function testIntegerPassesThrough()
    {
        $this->assertSame('42', simcal_sanitize_input(42));
    }

    public function testFloatPassesThrough()
    {
        $this->assertSame('3.14', simcal_sanitize_input(3.14));
    }

    public function testArrayIsRecursed()
    {
        $result = simcal_sanitize_input(['a', true, null]);
        $this->assertSame(['a', 'yes', ''], $result);
    }

    public function testObjectIsCastToArrayAndRecursed()
    {
        $result = simcal_sanitize_input((object) ['key' => 'val']);
        $this->assertSame(['key' => 'val'], $result);
    }

    public function testNestedArrayIsFullyRecursed()
    {
        $result = simcal_sanitize_input(['a' => ['b' => false]]);
        $this->assertSame(['a' => ['b' => 'no']], $result);
    }

    public function testEmptyStringPassesThrough()
    {
        $this->assertSame('', simcal_sanitize_input(''));
    }

    public function testGaCampaignUrlContainsUtmParams()
    {
        $url = simcal_ga_campaign_url('https://example.com', 'core-plugin', 'sidebar-link');
        $this->assertStringContainsString('utm_source=inside-plugin', $url);
        $this->assertStringContainsString('utm_campaign=core-plugin', $url);
        $this->assertStringContainsString('utm_content=sidebar-link', $url);
        $this->assertStringContainsString('utm_medium=link', $url);
    }

    public function testGaCampaignUrlRawUsesEscUrlRaw()
    {
        $url = simcal_ga_campaign_url('https://example.com', 'core-plugin', 'sidebar-link', true);
        $this->assertStringContainsString('utm_source=inside-plugin', $url);
    }

    public function testGaCampaignUrlContainsBaseUrl()
    {
        $url = simcal_ga_campaign_url('https://simplecalendar.io', 'test', 'test');
        $this->assertStringContainsString('https://simplecalendar.io', $url);
    }
}
