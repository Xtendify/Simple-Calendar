<?php

define('ABSPATH', '/tmp/fake-wp/');

require_once dirname(__DIR__) . '/vendor/autoload.php';

require_once dirname(__DIR__) . '/includes/autoload.php';

$thirdPartyAutoload = dirname(__DIR__) . '/third-party/vendor/autoload.php';
if (file_exists($thirdPartyAutoload)) {
    require_once $thirdPartyAutoload;
}

if (!class_exists('SimpleCalendar\plugin_deps\Carbon\Carbon')) {
    class_alias('Carbon\Carbon', 'SimpleCalendar\plugin_deps\Carbon\Carbon');
}

if (!defined('SIMPLE_CALENDAR_VERSION')) {
    define('SIMPLE_CALENDAR_VERSION', 'test');
}
if (!defined('SIMPLE_CALENDAR_PATH')) {
    define('SIMPLE_CALENDAR_PATH', dirname(__DIR__) . '/');
}
if (!defined('SIMPLE_CALENDAR_INC')) {
    define('SIMPLE_CALENDAR_INC', dirname(__DIR__) . '/includes/');
}

require_once dirname(__DIR__) . '/includes/functions/shared.php';
