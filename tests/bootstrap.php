<?php

define('ABSPATH', '/tmp/fake-wp/');

// Patchwork must load before any user-defined functions so it can intercept
// them when Brain\Monkey overrides them per-test.
require_once dirname(__DIR__) . '/vendor/antecedent/patchwork/Patchwork.php';

require_once dirname(__DIR__) . '/vendor/autoload.php';

// WP function stubs — loaded through Patchwork's stream wrapper so they are
// interceptable by Brain\Monkey at test time.
require_once __DIR__ . '/wp-stubs.php';

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
require_once dirname(__DIR__) . '/includes/functions/admin.php';
require_once dirname(__DIR__) . '/includes/wp-requirements.php';
