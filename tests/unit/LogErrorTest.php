<?php

use PHPUnit\Framework\TestCase;

class LogErrorTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/lib/core/MessageLog.php';
        require_once __DIR__ . '/../../src/lib/core/LogError.php';
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('LogError'));
    }

    public function testSetStatusCodeReturnsInt(): void
    {
        $result = LogError::setStatusCode(404);
        $this->assertIsInt($result);
        $this->assertEquals(404, $result);
    }

    public function testSetStatusCodeWithDifferentValues(): void
    {
        $this->assertEquals(200, LogError::setStatusCode(200));
        $this->assertEquals(403, LogError::setStatusCode(403));
        $this->assertEquals(500, LogError::setStatusCode(500));
    }

    public function testLogPathReturnsString(): void
    {
        $path = LogError::logPath();
        $this->assertIsString($path);
        $this->assertStringEndsWith('/log/', $path);
    }

    public function testErrorHandlerReturnsValue(): void
    {
        $result = LogError::errorHandler(E_NOTICE, 'Test notice', __FILE__, __LINE__);
        $this->assertIsBool($result);
    }

    public function testExceptionHandlerDoesNotThrow(): void
    {
        $exception = new Exception('Test exception');
        LogError::exceptionHandler($exception);
        $this->assertTrue(true);
    }

    public function testCustomErrorMessageAdminPrivilege(): void
    {
        LogError::setStatusCode(404);
        ob_start();
        LogError::customErrorMessage('admin');
        $output = ob_get_clean();
        $this->assertStringContainsString('404', $output);
    }

    public function testCustomErrorMessageNoPrivilege(): void
    {
        LogError::setStatusCode(500);
        ob_start();
        LogError::customErrorMessage();
        $output = ob_get_clean();
        $this->assertStringContainsString('error log', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testNewMessageReturnsStringOrVoid(): void
    {
        $exception = new Exception('Test exception message');
        $result = LogError::newMessage($exception);
        $this->assertTrue($result === null || is_string($result));
    }
}
