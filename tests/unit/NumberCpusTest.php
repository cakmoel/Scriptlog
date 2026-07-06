<?php

use PHPUnit\Framework\TestCase;

class NumberCpusTest extends TestCase
{
    private string $utilityPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->utilityPath = __DIR__ . '/../../src/lib/utility/number-cpus.php';

        if (!function_exists('number_cpus')) {
            require_once $this->utilityPath;
        }
    }

    public function testFunctionExists(): void
    {
        $this->assertTrue(function_exists('number_cpus'));
    }

    public function testReturnsIntegerOrNumeric(): void
    {
        $result = number_cpus();
        $this->assertTrue(is_numeric($result) || is_int($result) || is_float($result));
    }

    public function testReturnsAtLeastOne(): void
    {
        $result = number_cpus();
        $this->assertGreaterThanOrEqual(1, (int)$result);
    }

    public function testSourceUsesIsResourceGuard(): void
    {
        $source = file_get_contents($this->utilityPath);

        $this->assertStringContainsString('is_resource($process)', $source);
        $this->assertStringNotContainsString('false !== $process', $source);
    }

    public function testSourceUsesPopenWithFunctionExistsGuard(): void
    {
        $source = file_get_contents($this->utilityPath);

        preg_match_all('/function_exists\(\'popen\'\)/', $source, $matches);
        $this->assertGreaterThanOrEqual(1, count($matches[0]), 'Should guard popen with function_exists');
    }

    public function testProcCpuinfoPathForLinux(): void
    {
        $source = file_get_contents($this->utilityPath);

        $this->assertStringContainsString('/proc/cpuinfo', $source);
        $this->assertStringContainsString("strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'", $source);
    }

    public function testWindowsBranch(): void
    {
        $source = file_get_contents($this->utilityPath);

        $this->assertStringContainsString('getenv("NUMBER_OF_PROCESSORS")', $source);
    }

    public function testMacBranch(): void
    {
        $source = file_get_contents($this->utilityPath);

        $this->assertStringContainsString("sysctl -n hw.ncpu", $source);
    }

    public function testDefaultCpuCoreToOne(): void
    {
        $reflection = new ReflectionFunction('number_cpus');
        $startLine = $reflection->getStartLine();

        $source = file_get_contents($this->utilityPath);
        $lines = explode("\n", $source);

        $functionLines = array_slice($lines, $startLine - 1, 5);
        $functionStart = implode("\n", $functionLines);

        $this->assertStringContainsString('$cpu_core = 1', $source);
    }

    public function testReturnsWhenCpuInfoIsAvailable(): void
    {
        $result = number_cpus();
        $this->assertIsNotBool($result);
    }

    public function testFileIsValidPhpSyntax(): void
    {
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg($this->utilityPath) . ' 2>&1', $output, $returnCode);
        $this->assertEquals(0, $returnCode, 'PHP syntax check failed: ' . implode("\n", $output));
    }
}
