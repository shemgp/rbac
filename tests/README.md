# Instructions for running Unit Tests.

## Front Matter

* The Unit Tests should be run using a database specific for Unit Testing. This
  way your dev/testing/production data will not be affected.
* To run the Unit Tests on MySQL or Postgres, you will need to have an
  existing database with the proper tables and default data prior to running
  the tests.
* If you are running the Unit Tests for the SQLite adapter the database will be
  created/overwritten for you automatically.

## The Setup

All tables prefixes _must_ be `phprbac_`, since the DbUnit setup assumes that.

* Create the database and tables
    * MySQL
        * Execute the queries located in the `mysql.sql` file in the
          `PhpRbac/database/` directory
            * **WARNING:** Make sure you replace `'PREFIX_` appropriately
    * SQLite
        * The database will be created/overwritten for you automatically when
          you run the Unit Tests
        * Unfortunately, the location of the sqlite test file is hard-coded
          into the test setup class - it _must_ be put into the `tests/`
          directory
    * Postgres
        * Execute the queries located in the `pgsql.sql` file in the
          `PhpRbac/database/` directory
        * Create the tables in the `public` schema, or any schema that is
          default reachable by the test user account.

* Open up `/tests/database.conf`. Change the database connection info
  accordingly. (This file, despite the extension, is a PHP file.)
* Open the  `/tests/*.xml` file for your database. Change the database
  connection info accordingly. Don't forget to change the database name in the
  DSN string (this is for the DBUnit connection, fixture and datasets).

## Run The Unit Tests

* This package now lists PHPUnit and DBUnit as developer dependenices. They
  are installed in your `/vendor/bin` directory once you've done `composer
  install` or `composer update`.
* Instead of providing `.bat` and `.sh` files for every possible database to
  run tests, simply start PHPUnit from your command line, passing it a config
  parameter based on which database you're using.

```
> composer update
> cd vendor
> ./bin/phpunit -c /owasp/phpunit/phpunit_[db_of_your_choice].xml
```

* To run only selected test files, do the same but specify the file on the
  command line after the config. (Note that MySQL or sqlite tests should use
  the tests in the `tests/src-nst/` directory.)

```
> ./bin/phpunit -c /owasp/phpunit/phpunit_pgsql.xml owasp/phprbac/tests/src/RbacRolesTest.php
```

## Notes

* You can enter separate credentials and DB config information for each database
  in the `tests/database.config` file. Unused info is ignored.

* **Thanks to the AuraPHP team for helping us bootstrap our Unit Testing methods**
