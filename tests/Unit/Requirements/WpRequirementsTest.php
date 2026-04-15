<?php

namespace SimpleCalendar\Tests\Unit\Requirements;

use Brain\Monkey\Functions;
use SimpleCalendar\Tests\Unit\TestCase;

class WpRequirementsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\stubs([
            'get_bloginfo' => function ($key) {
                if ($key === 'version') {
                    return '6.0';
                }
                return '';
            },
        ]);
    }

    public function testAllRequirementsPass()
    {
        $req = new \SimCal_WP_Requirements('Test Plugin', 'test/test.php', [
            'WordPress'  => '5.0',
            'PHP'        => '5.0',
            'Extensions' => ['json'],
        ]);

        $this->assertTrue($req->pass());
        $this->assertEmpty($req->failures());
    }

    public function testPhpVersionFails()
    {
        $req = new \SimCal_WP_Requirements('Test Plugin', 'test/test.php', [
            'PHP' => '999.0.0',
        ]);

        $this->assertFalse($req->pass());
        $this->assertArrayHasKey('PHP', $req->failures());
    }

    public function testWordPressVersionFails()
    {
        $req = new \SimCal_WP_Requirements('Test Plugin', 'test/test.php', [
            'WordPress' => '999.0.0',
        ]);

        $this->assertFalse($req->pass());
        $this->assertArrayHasKey('WordPress', $req->failures());
    }

    public function testMissingExtensionFails()
    {
        $req = new \SimCal_WP_Requirements('Test Plugin', 'test/test.php', [
            'Extensions' => ['nonexistent_ext_xyz_abc'],
        ]);

        $this->assertFalse($req->pass());
        $failures = $req->failures();
        $this->assertArrayHasKey('Extensions', $failures);
        $this->assertArrayHasKey('nonexistent_ext_xyz_abc', $failures['Extensions']);
    }

    public function testPresentExtensionPasses()
    {
        $req = new \SimCal_WP_Requirements('Test Plugin', 'test/test.php', [
            'Extensions' => ['json'],
        ]);

        $this->assertTrue($req->pass());
        $this->assertEmpty($req->failures());
    }

    public function testMultipleFailures()
    {
        $req = new \SimCal_WP_Requirements('Test Plugin', 'test/test.php', [
            'WordPress' => '999.0.0',
            'PHP'       => '999.0.0',
        ]);

        $this->assertFalse($req->pass());
        $failures = $req->failures();
        $this->assertArrayHasKey('WordPress', $failures);
        $this->assertArrayHasKey('PHP', $failures);
    }

    public function testPluginNameStripsHtml()
    {
        $req = new \SimCal_WP_Requirements('<script>alert(1)</script>My Plugin', 'test/test.php', [
            'PHP' => '999.0.0',
        ]);

        $notice = $req->get_notice();
        $this->assertStringNotContainsString('<script>', $notice);
        $this->assertStringContainsString('My Plugin', $notice);
    }

    public function testGetNoticeIsEmptyWhenAllPass()
    {
        $req = new \SimCal_WP_Requirements('Test Plugin', 'test/test.php', [
            'PHP' => '5.0',
        ]);

        $this->assertSame('', $req->get_notice());
    }

    public function testGetNoticeContainsRequirementWhenFails()
    {
        $req = new \SimCal_WP_Requirements('My Plugin', 'test/test.php', [
            'PHP' => '999.0.0',
        ]);

        $notice = $req->get_notice();
        $this->assertStringContainsString('My Plugin', $notice);
        $this->assertStringContainsString('999.0.0', $notice);
        $this->assertStringContainsString('PHP', $notice);
    }

    public function testGetNoticeContainsMissingExtension()
    {
        $req = new \SimCal_WP_Requirements('My Plugin', 'test/test.php', [
            'Extensions' => ['nonexistent_ext_xyz_abc'],
        ]);

        $notice = $req->get_notice();
        $this->assertStringContainsString('nonexistent_ext_xyz_abc', $notice);
    }

    public function testEmptyRequirementsHasNoFailures()
    {
        // An empty requirements array triggers trigger_error(E_USER_ERROR) in the
        // source, which is deprecated in PHP 8.4. We silence it and verify the
        // object is still unusable (pass() returns true by default).
        set_error_handler(function () { return true; }, E_USER_ERROR);
        $req = new \SimCal_WP_Requirements('Test', 'test/test.php', []);
        restore_error_handler();

        $this->assertEmpty($req->failures());
    }
}
