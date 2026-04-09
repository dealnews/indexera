<?php

declare(strict_types=1);

namespace Dealnews\Indexera\Tests;

use Dealnews\Indexera\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for Indexera unit tests.
 *
 * Provides helpers for building Repository mocks and seeding $_SESSION.
 *
 * @package Dealnews\Indexera\Tests
 */
abstract class TestCase extends PHPUnitTestCase {

    /**
     * Reset session state before every test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $_SESSION = [];
    }

    /**
     * Sets the current session user ID.
     *
     * @param int $user_id
     *
     * @return void
     */
    protected function setSessionUser(int $user_id): void {
        $_SESSION['user_id'] = $user_id;
    }

    /**
     * Creates a PHPUnit mock of the application Repository.
     *
     * @return MockObject&Repository
     */
    protected function makeRepositoryMock(): MockObject&Repository {
        return $this->createMock(Repository::class);
    }

    /**
     * Uses reflection to inject a value into a protected or private property.
     *
     * @param object $target   The object to modify.
     * @param string $property The property name.
     * @param mixed  $value    The value to set.
     *
     * @return void
     */
    protected function setProperty(object $target, string $property, mixed $value): void {
        $ref = new \ReflectionProperty($target, $property);
        $ref->setValue($target, $value);
    }
}
