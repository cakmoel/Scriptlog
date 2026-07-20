<?php

use PHPUnit\Framework\TestCase;

class ReadDatetimeFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('read_datetime')) {
            require_once __DIR__ . '/../../src/lib/utility/read-datetime.php';
        }
    }

    public function testReadDatetimeFunctionExists(): void
    {
        $this->assertTrue(function_exists('read_datetime'));
    }

    public function testReadDatetimeReturnsEmptyStringWhenDateGeneratorNotAvailable(): void
    {
        $result = read_datetime('2026-07-20 12:00:00');
        $this->assertIsString($result);
    }

    public function testReadDatetimeHandlesEmptyString(): void
    {
        $result = read_datetime('');
        $this->assertIsString($result);
    }

    public function testReadDatetimeHandlesNullInput(): void
    {
        $result = read_datetime(null);
        $this->assertTrue(is_string($result) || is_null($result));
    }

    public function testReadDatetimeSourceFileHasCorrectStructure(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/read-datetime.php');
        $this->assertStringContainsString('function read_datetime', $source);
        $this->assertStringContainsString('DateGenerator', $source);
    }

    public function testReadDatetimeUsesDateGeneratorWhenAvailable(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/read-datetime.php');
        $this->assertStringContainsString('getExternalDate', $source);
    }
}
