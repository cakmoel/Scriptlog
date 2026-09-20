<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;
use Scriptlog\Core\MessageLog;

class MessageLogTest extends TestCase
{
    private $tempLog;

    protected function setUp(): void
    {
        $this->tempLog = tempnam(sys_get_temp_dir(), 'msglog_test_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempLog)) {
            unlink($this->tempLog);
        }
    }

    public function testWriteMessageToFileWithValidPath(): void
    {
        $data = ['test' => 'value', 'code' => 500];

        $result = MessageLog::messageError(500, 'Test error', $this->tempLog, '/test.php', 42);

        $this->assertTrue($result, 'messageError should return true when log file is writable');
        $this->assertGreaterThan(0, filesize($this->tempLog), 'Log file should have content written');
    }

    public function testWriteMessageToFileWithUnwritablePath(): void
    {
        $data = ['test' => 'value', 'code' => 500];

        $result = MessageLog::messageError(500, 'Test error', '/nonexistent/path/to/log.log', '/test.php', 42);

        $this->assertFalse($result, 'messageError should return false when log file cannot be opened');
    }

    public function testWriteMessageToFileWithNullPath(): void
    {
        $result = MessageLog::messageError(500, 'Test error', null, '/test.php', 42);

        $this->assertFalse($result, 'messageError should return false when log path is null');
    }

    public function testErrorCodeMessageReturnsCorrectValues(): void
    {
        $reflection = new ReflectionClass(MessageLog::class);
        $method = $reflection->getMethod('errorCodeMessage');
        $method->setAccessible(true);

        $result = $method->invoke(null, E_WARNING);
        $this->assertEquals('Warning', $result[0]);
        $this->assertEquals(LOG_WARNING, $result[1]);

        $result = $method->invoke(null, E_NOTICE);
        $this->assertEquals('Notice', $result[0]);
        $this->assertEquals(LOG_NOTICE, $result[1]);

        $result = $method->invoke(null, E_PARSE);
        $this->assertEquals('Fatal Error', $result[0]);
        $this->assertEquals(LOG_ERR, $result[1]);
    }
}
