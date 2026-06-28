<?php
/**
 * DbFactory Tests
 *
 * Phase 3.6: Core Classes - DbFactory (4 tests)
 * Tests for database connection factory
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class DbFactoryTest extends TestCase
{
    public function testDbFactoryClassExists(): void
    {
        $this->assertTrue(class_exists('DbFactory'));
    }

    public function testConnectMethodExists(): void
    {
        $this->assertTrue(method_exists('DbFactory', 'connect'));
    }

    public function testConnectReturnsNullWithInvalidConnection(): void
    {
        $connection = [
            'mysql:host=nonexistent;port=3306;dbname=invalid',
            'user',
            'pass'
        ];

        $result = DbFactory::connect($connection);
        $this->assertNull($result);
    }

    public function testFactoryIsStaticClass(): void
    {
        $reflection = new ReflectionClass('DbFactory');
        $methods = $reflection->getMethods(ReflectionMethod::IS_STATIC);

        $this->assertNotEmpty($methods);
    }
}
