<?php

use PHPUnit\Framework\TestCase;

/**
 * MessageLogGuardTest
 *
 * Unit tests for the null/empty path guard and suppressed fopen() in
 * MessageLog::writeMessageToFile(), ensuring the method returns false
 * gracefully instead of emitting warnings.
 *
 * @category Unit Test
 * @author Blogware Team
 * @license MIT
 */
class MessageLogGuardTest extends TestCase
{
    private $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    private function getMessageLogClass(): string
    {
        return class_exists('MessageLog') ? 'MessageLog' : 'Scriptlog\Core\MessageLog';
    }

    public function testMessageLogClassExists(): void
    {
        $className = $this->getMessageLogClass();
        $this->assertTrue(class_exists($className));
    }

    public function testWriteMessageToFileReturnsFalseForNullPath(): void
    {
        $className = $this->getMessageLogClass();

        $reflection = new ReflectionClass($className);
        $method = $reflection->getMethod('writeMessageToFile');
        $method->setAccessible(true);

        $result = $method->invoke(null, ['test' => 'data'], null);
        $this->assertFalse($result);
    }

    public function testWriteMessageToFileReturnsFalseForEmptyPath(): void
    {
        $className = $this->getMessageLogClass();

        $reflection = new ReflectionClass($className);
        $method = $reflection->getMethod('writeMessageToFile');
        $method->setAccessible(true);

        $result = $method->invoke(null, ['test' => 'data'], '');
        $this->assertFalse($result);
    }

    public function testWriteMessageToFileReturnsFalseForUnwritablePath(): void
    {
        $className = $this->getMessageLogClass();

        $reflection = new ReflectionClass($className);
        $method = $reflection->getMethod('writeMessageToFile');
        $method->setAccessible(true);

        $result = $method->invoke(null, ['test' => 'data'], '/nonexistent/dir/log.txt');
        $this->assertFalse($result);
    }

    public function testWriteMessageToFileWritesToValidPath(): void
    {
        $className = $this->getMessageLogClass();

        $reflection = new ReflectionClass($className);
        $method = $reflection->getMethod('writeMessageToFile');
        $method->setAccessible(true);

        $tmpFile = tempnam(sys_get_temp_dir(), 'msglog_');
        $result = $method->invoke(null, 'Test log entry' . PHP_EOL, $tmpFile);

        $this->assertTrue($result);
        $this->assertStringContainsString('Test log entry', file_get_contents($tmpFile));
        unlink($tmpFile);
    }

    public function testWriteMessageToFileHandlesArrayData(): void
    {
        $className = $this->getMessageLogClass();

        $reflection = new ReflectionClass($className);
        $method = $reflection->getMethod('writeMessageToFile');
        $method->setAccessible(true);

        $tmpFile = tempnam(sys_get_temp_dir(), 'msglog_');
        $data = ['level' => 'error', 'code' => 500, 'message' => 'Test'];
        $result = $method->invoke(null, $data, $tmpFile);

        $this->assertTrue($result);
        $content = file_get_contents($tmpFile);
        $this->assertStringContainsString('level', $content);
        unlink($tmpFile);
    }

    public function testContentMessageReturnsString(): void
    {
        $className = $this->getMessageLogClass();
        $result = $className::contentMessage(404, 'Not Found', 'admin');

        $this->assertIsString($result);
        $this->assertStringContainsString('404', $result);
        $this->assertStringContainsString('Not Found', $result);
    }

    public function testContentMessageNonAdminReturnsGenericMessage(): void
    {
        $className = $this->getMessageLogClass();
        $result = $className::contentMessage(500, 'Server Error');

        $this->assertIsString($result);
        $this->assertStringContainsString('error log', $result);
    }
}
