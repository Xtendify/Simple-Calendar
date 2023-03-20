<?php

declare(strict_types=1);

/**
 * PHP-Scoper configuration file.
 *
 * @package   Google\Site_Kit
 * @copyright 2019 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link      https://sitekit.withgoogle.com
 */

use Isolated\Symfony\Component\Finder\Finder;

// Google API services to include classes for.
$google_services = implode(
	'|',
	array_map(
		function ($service) {
			return preg_quote($service, '#');
		},
		array(
			'Calendar',
			'Drive',
		)
	)
);

return array(
	'prefix' 					 => 'SimpleCalendar\plugin_deps',
	'finders'                    => array(

		// General dependencies, except Google API services.
		Finder::create()
			->files()
			->ignoreVCS(true)
			->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
			->exclude(
				array(
					'doc',
					'test',
					'test_old',
					'tests',
					'Tests',
					'vendor-bin',
				)
			)
			->path('#^firebase/#')
			->path('#^google/apiclient/#')
			->path('#^google/auth/#')
			->path('#^guzzlehttp/#')
			->path('#^monolog/#')
			->path('#^psr/#')
			->path('#^ralouphie/#')
			->path('#^react/#')
			->path('#^nesbot/#')
			->path('#^symfony/#')
			->path('#^mexitek/#')
			->in('vendor'),

		// Google API service infrastructure classes.
		Finder::create()
			->files()
			->ignoreVCS(true)
			->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
			->exclude(
				array(
					'doc',
					'test',
					'test_old',
					'tests',
					'Tests',
					'vendor-bin',
				)
			)
			->path("#^google/apiclient-services/src/($google_services)/#")
			->in('vendor'),

		// Google API service entry classes.
		Finder::create()
			->files()
			->ignoreVCS(true)
			->name("#($google_services)\.php#")
			->in('vendor/google/apiclient-services/src'),
	),
	'exclude-files'            => array(
		// This dependency is a global function which should remain global.
		'vendor\\ralouphie\\getallheaders\\src\\getallheaders.php',
	),
	'exclude-classes' => [
        'Isolated\Symfony\Component\Finder\Finder',
    ],
);
