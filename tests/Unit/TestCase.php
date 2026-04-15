<?php

namespace SimpleCalendar\Tests\Unit;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\stubs([
            'esc_attr'             => function ($v) { return $v; },
            'esc_html'             => function ($v) { return $v; },
            'esc_url'              => function ($v) { return $v; },
            'esc_url_raw'          => function ($v) { return $v; },
            'wp_kses_post'         => function ($v) { return $v; },
            'absint'               => function ($v) { return abs(intval($v)); },
            'sanitize_title'       => function ($v) { return strtolower(str_replace(' ', '-', $v)); },
            'sanitize_text_field'  => function ($v) { return $v; },
            'add_query_arg'        => function ($args, $url = '') {
                $sep = strpos($url, '?') === false ? '?' : '&';
                return $url . $sep . http_build_query($args);
            },
            '__'                   => function ($text, $domain = 'default') { return $text; },
            'apply_filters'        => function ($tag, $value) { return $value; },
            'add_filter'           => null,
            'add_action'           => null,
        ]);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
