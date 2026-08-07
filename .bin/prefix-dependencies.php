<?php

declare(strict_types=1);

/**
 * Cross-platform replacement for the prefix-dependencies Composer scripts.
 *
 * Scopes vendor packages into third-party/ via php-scoper, then regenerates
 * the includes/ and third-party/ authoritative classmaps.
 */

$root = dirname(__DIR__);
chdir($root);

function run(string $command, ?string $cwd = null): void
{
	$descriptor = [
		0 => STDIN,
		1 => STDOUT,
		2 => STDERR,
	];

	$process = proc_open($command, $descriptor, $pipes, $cwd);
	if (!is_resource($process)) {
		fwrite(STDERR, "Failed to start command: {$command}\n");
		exit(1);
	}

	$code = proc_close($process);
	if ($code !== 0) {
		fwrite(STDERR, "Command failed ({$code}): {$command}\n");
		exit($code);
	}
}

function remove_path(string $path): void
{
	if (!file_exists($path)) {
		return;
	}

	if (is_file($path) || is_link($path)) {
		unlink($path);
		return;
	}

	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST,
	);

	foreach ($items as $item) {
		$itemPath = $item->getPathname();
		if ($item->isDir()) {
			rmdir($itemPath);
		} else {
			unlink($itemPath);
		}
	}

	rmdir($path);
}

$phpScoperDir = $root . DIRECTORY_SEPARATOR . 'php-scoper';
$composer = 'composer';

echo "Preparing php-scoper...\n";
remove_path($phpScoperDir);
if (!mkdir($phpScoperDir) && !is_dir($phpScoperDir)) {
	fwrite(STDERR, "Unable to create php-scoper directory.\n");
	exit(1);
}

run("{$composer} init -q --name \"calendar/php_scoper\"", $phpScoperDir);
run("{$composer} require humbug/php-scoper", $phpScoperDir);

$phpScoperBin = $phpScoperDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php-scoper';
if (PHP_OS_FAMILY === 'Windows') {
	$phpScoperBin .= '.bat';
}

echo "Running php-scoper...\n";
run('"' . $phpScoperBin . '" add --output-dir=./third-party --force', $root);

echo "Regenerating includes autoload...\n";
copy($root . '/composer.include.dump-autoload', $root . '/includes/composer.json');
run("{$composer} dump-autoload --no-plugins -vvv --classmap-authoritative --no-interaction", $root . '/includes');
unlink($root . '/includes/composer.json');

echo "Regenerating third-party autoload...\n";
copy($root . '/composer.third-party.dump-autoload', $root . '/third-party/composer.json');
run("{$composer} dump-autoload -vvv --no-plugins --classmap-authoritative --no-interaction", $root . '/third-party');
unlink($root . '/third-party/composer.json');

echo "Copying autoload_files.php...\n";
copy(
	$root . '/vendor/composer/autoload_files.php',
	$root . '/third-party/vendor/composer/autoload_files.php',
);

echo "Cleaning up php-scoper...\n";
remove_path($phpScoperDir);

echo "prefix-dependencies completed successfully.\n";
