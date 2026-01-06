# MyTravelStatus Test Suite

Comprehensive PHPUnit test suite for the MyTravelStatus plugin.

## Test Structure

```
tests/
├── bootstrap.php          # PHPUnit bootstrap (loads WordPress)
├── TestCase.php           # Base test case with helpers
├── Unit/                  # Unit tests (no WordPress required)
│   └── CalculatorTest.php # Pure calculation logic tests
└── Integration/           # Integration tests (requires WordPress)
    ├── JurisdictionTest.php  # MTS_Jurisdiction class tests
    ├── RestApiTest.php       # REST API endpoint tests
    └── DatabaseTest.php      # Database operation tests
```

## Prerequisites

1. **PHP 7.4+**
2. **MySQL/MariaDB** (for integration tests)
3. **Composer**

## Installation

### 1. Install dependencies

```bash
cd mytravelstatus
composer install
```

### 2. Set up WordPress test environment

```bash
# Create a test database and install WordPress test suite
# Usage: ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> <db-host> <wp-version>

./bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

Or use the composer script:

```bash
composer install-wp-tests
```

## Running Tests

### All tests

```bash
composer test
```

Or:

```bash
./vendor/bin/phpunit
```

### Unit tests only (fast, no WordPress)

```bash
composer test:unit
```

Or:

```bash
./vendor/bin/phpunit --testsuite Unit
```

### Integration tests only

```bash
composer test:integration
```

Or:

```bash
./vendor/bin/phpunit --testsuite Integration
```

### Specific test file

```bash
./vendor/bin/phpunit tests/Integration/JurisdictionTest.php
```

### Specific test method

```bash
./vendor/bin/phpunit --filter test_calculate_summary_counts_days
```

## Test Coverage

### What's Tested

#### Unit Tests (`CalculatorTest.php`)
- US SPT weighted calculation formula
- UK SRT automatic tests and ties thresholds
- Schengen 90/180 rolling window calculation
- Ireland 183/280 multi-year rule
- Status threshold determination (ok/warning/critical/exceeded)
- UK tax year date calculation
- Australia fiscal year calculation
- Days remaining calculation (never negative)
- Trip overlap detection
- Unique days calculation with overlapping trips

#### Integration Tests

**`JurisdictionTest.php`**
- `get_all_rules()` returns jurisdictions
- `get_rule()` returns specific jurisdiction
- `get_user_tracked_jurisdictions()` default and custom
- `calculate_summary()` with no trips
- `calculate_summary()` counts days correctly
- Status thresholds (warning at 80%, critical at 95%, exceeded at 100%)
- `get_compliance_overview()` aggregate data and caching
- `invalidate_cache()` clears transients
- Rolling window excludes old trips
- Calendar year calculation
- Multiple trips counting
- Overlapping trips not double-counted

**`RestApiTest.php`**
- `GET /mts/v1/jurisdictions` returns all jurisdictions
- `GET /mts/v1/jurisdictions/{code}` returns single jurisdiction
- `GET /mts/v1/jurisdictions/tracked` returns tracked jurisdictions
- `POST /mts/v1/jurisdictions/tracked` adds jurisdiction
- `DELETE /mts/v1/jurisdictions/tracked/{code}` removes jurisdiction
- Cannot remove Schengen from tracking
- `GET /mts/v1/jurisdictions/summary` multi-jurisdiction summary
- `GET /mts/v1/jurisdictions/{code}/summary` single summary
- Legacy API namespace (`/fra-portal/v1/...`)
- Authentication required for all endpoints

**`DatabaseTest.php`**
- Tables exist (trips, jurisdiction_rules)
- Trip CRUD operations
- Trips isolated by user
- Date range queries
- Overlapping date queries
- Jurisdiction rules data integrity
- Jurisdiction code uniqueness
- Days calculation from trips table
- Country aggregation query
- Index usage
- User meta storage
- Transient caching
- Bulk trip insertion performance
- Rolling window query performance

## Writing New Tests

### Adding a Unit Test

Unit tests should be pure PHP without WordPress dependencies:

```php
<?php
// tests/Unit/MyTest.php

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase {
    public function test_something() {
        $this->assertEquals(1, 1);
    }
}
```

### Adding an Integration Test

Integration tests extend `MTS_TestCase` which provides WordPress and helper methods:

```php
<?php
// tests/Integration/MyIntegrationTest.php

class MyIntegrationTest extends MTS_TestCase {

    public function test_with_trips() {
        // Create a test trip
        $trip_id = $this->create_trip([
            'country'    => 'France',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-01-10',
        ]);

        // Test your functionality
        $result = some_function($this->test_user_id);

        $this->assertEquals('expected', $result);
    }
}
```

### Available Helper Methods

From `MTS_TestCase`:

- `$this->test_user_id` - ID of test user
- `$this->create_trip($args)` - Create a test trip
- `$this->create_trips($trips)` - Create multiple trips
- `$this->set_tracked_jurisdictions($codes)` - Set tracked jurisdictions
- `$this->get_jurisdiction()` - Get MTS_Jurisdiction instance
- `$this->api_request($method, $route, $params)` - Make REST API request

## Continuous Integration

Example GitHub Actions workflow:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: wordpress_test
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Install dependencies
        run: composer install
        working-directory: mytravelstatus

      - name: Install WP test suite
        run: ./bin/install-wp-tests.sh wordpress_test root password 127.0.0.1 latest
        working-directory: mytravelstatus

      - name: Run tests
        run: composer test
        working-directory: mytravelstatus
```

## Troubleshooting

### "Could not find .../functions.php"

WordPress test suite not installed. Run:

```bash
./bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### Database connection errors

Check your MySQL credentials in the install script arguments.

### "Class 'WP_UnitTestCase' not found"

The WordPress test library is not loaded. Ensure bootstrap.php is correct.

### Slow tests

Run unit tests separately for faster feedback:

```bash
composer test:unit
```
