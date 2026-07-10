<?php
/**
 * Dao filteringId Tests
 *
 * Tests for the refactored filteringId() method that now throws
 * InvalidArgumentException and returns int for 'sql', string for 'xss'.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class DaoFilteringIdTest extends TestCase
{
    private $sanitizeMock;

    protected function setUp(): void
    {
        $this->sanitizeMock = $this->createMock(Sanitize::class);
        $this->sanitizeMock->method('sanitasi')
            ->willReturnCallback(function ($str, $type) {
                if ($type === 'sql') {
                    return (int)$str;
                }
                return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
            });
    }

    public function testFilteringIdSqlWithValidInt(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $result = $reflection->invoke($dao, $this->sanitizeMock, 42, 'sql');
        $this->assertIsInt($result);
        $this->assertEquals(42, $result);
    }

    public function testFilteringIdSqlWithStringNumber(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $result = $reflection->invoke($dao, $this->sanitizeMock, '99', 'sql');
        $this->assertIsInt($result);
        $this->assertEquals(99, $result);
    }

    public function testFilteringIdSqlWithZeroThrowsException(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a positive integer');
        $reflection->invoke($dao, $this->sanitizeMock, 0, 'sql');
    }

    public function testFilteringIdSqlWithNegativeThrowsException(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a positive integer');
        $reflection->invoke($dao, $this->sanitizeMock, -5, 'sql');
    }

    public function testFilteringIdSqlWithNullThrowsException(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');
        $reflection->invoke($dao, $this->sanitizeMock, null, 'sql');
    }

    public function testFilteringIdSqlWithEmptyStringThrowsException(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');
        $reflection->invoke($dao, $this->sanitizeMock, '', 'sql');
    }

    public function testFilteringIdSqlWithNonNumericThrowsException(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a positive integer');
        $reflection->invoke($dao, $this->sanitizeMock, 'abc', 'sql');
    }

    public function testFilteringIdXssWithValidString(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $result = $reflection->invoke($dao, $this->sanitizeMock, 'hello world', 'xss');
        $this->assertIsString($result);
    }

    public function testFilteringIdXssWithNonStringThrowsException(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a non-empty string');
        $reflection->invoke($dao, $this->sanitizeMock, 12345, 'xss');
    }

    public function testFilteringIdInvalidTypeThrowsException(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sanitization type');
        $reflection->invoke($dao, $this->sanitizeMock, 1, 'invalid_type');
    }

    public function testFilteringIdXssWithEmptyStringThrowsException(): void
    {
        if (!class_exists('Dao')) {
            $this->markTestSkipped('Dao class not found');
        }

        $reflection = new ReflectionMethod('Dao', 'filteringId');
        $reflection->setAccessible(true);

        $dao = $this->getMockBuilder('Dao')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a non-empty string');
        $reflection->invoke($dao, $this->sanitizeMock, '  ', 'xss');
    }
}
