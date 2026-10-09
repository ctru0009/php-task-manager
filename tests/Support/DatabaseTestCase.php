<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that touch MySQL: every test starts from empty tables.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestDatabase::truncate();
    }
}
