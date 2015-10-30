# Testing Glue Framework

Tests are run using phpunit and dbunit. To prep your machine to run tests, you will need to install the appropriate versions with composer:

```
composer global require "phpunit/phpunit=4.8.*"
composer global require "phpunit/dbunit=1.4.*"
```

You will also need to make sure composer's `bin` folder is in your PATH before things will work correctly. Once everything is set up you should be able to run `phpunit` with no arguments and the default test suites will run. You'll know it worked when running `phpunit` produces something like the following:

```
$ phpunit
PHPUnit 4.8.16 by Sebastian Bergmann and contributors.

..

Time: 2.13 seconds, Memory: 6.00Mb

OK (2 tests, 6 assertions)

```

## Writing tests

Tests are stored in the tests folder, organized by the namespace or class of what they are testing. For example, the entire namespace `glue\CRUDder` is tested with tests in `tests/glue/CRUDder`.

If any new test files are added, they must be added to `phpunit.xml` as well, so that they will run automatically.
