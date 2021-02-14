bash -c "rm --recursive --force php-scoper &&  mkdir php-scoper"
bash -c "cd php-scoper && composer init -q --name calendar/php_scoper --stability dev && composer require humbug/php-scoper"
bash -c " ./php-scoper/vendor/bin/php-scoper add --output-dir=./third-party --force" 
echo "{ \"autoload\": { \"classmap\": [\"\"] } } "> ./includes/composer.json
bash -c "cd includes && pwd && composer dump-autoload --classmap-authoritative --no-interaction && rm composer.json"
echo "{ \"autoload\": { \"classmap\": [\"\"],\"files\":[\"guzzlehttp/psr7/src/functions_include.php\"] } } "> ./third-party/composer.json
bash -c "cd third-party && composer dump-autoload --classmap-authoritative --no-interaction  && rm composer.json"
bash -c "cp vendor/composer/autoload_files.php third-party/vendor/composer/autoload_files.php"
bash -c "rm -rf php-scoper"

