# Unit Tests

## Initial Setup

1) Install WordPress and the WP Unit Test lib using the `install.sh` script. Change to the plugin root directory and type: 

    $ tests/bin/install.sh <db-name> <db-user> <db-password> [db-host]

2) Composer already includes PHPUnit. If you haven't yet you need to run the following command from the plugin root: 

    $ composer install

Sample usage:

    $ tests/bin/install.sh plugin_tests root root

**Important**: The `<db-name>` database will be created if it doesn't exist and all data will be removed during testing.

## Running Tests

Simply change to the plugin root directory and type:

    $ grunt test

The tests will execute and you'll be presented with a summary.

You can run specific tests by providing the path and filename to the test class (example):

    $ phpunit tests/unit-tests/path/to/tests/

## Writing Tests

* Each test file should roughly correspond to an associated source file(s).
* Each test method should cover a single method or function with one or more assertions.
* A single method or function can have multiple associated test methods if it's a large or complex method.
* Use the test coverage HTML report (under `tmp/coverage/index.html`) to examine which lines your tests are covering and aim for 100% coverage.
* For code that cannot be tested (e.g. they require a certain PHP version), you can exclude them from coverage using a comment: `// @codeCoverageIgnoreStart` and `// @codeCoverageIgnoreEnd`.
* In addition to covering each line of a method/function, make sure to test common input and edge cases.
* Prefer `assertsEquals()` where possible as it tests both type & equality.
* Remember that only methods prefixed with `test` will be run so use helper methods liberally to keep test methods small and reduce code duplication. If there is a common helper method used in multiple test files, consider extending the main unit test case class.
* Filters persist between test cases so be sure to remove them in your test method or in the `tearDown()` method.
* Use data providers where possible. Be sure that their name is like `data_provider_function_to_test` (i.e. the data provider for `test_is_postcode` would be `data_provider_test_is_postcode`). Read more about data providers [here](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers).
