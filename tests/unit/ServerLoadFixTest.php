<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class ServerLoadFixTest extends TestCase
{
    public function testNumberCpusReturnsInteger(): void
    {
        if (!function_exists('number_cpus')) {
            $this->markTestSkipped('number_cpus function not found');
        }

        $result = number_cpus();
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(1, $result);
    }

    public function testGetServerLoadDoesNotThrowTypeError(): void
    {
        if (!function_exists('get_server_load')) {
            $this->markTestSkipped('get_server_load function not found');
        }

        try {
            get_server_load();
            $this->assertTrue(true);
        } catch (\TypeError $e) {
            $this->fail('get_server_load() threw TypeError: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testNumberCpusUsesIsResourceInAllBranches(): void
    {
        if (!function_exists('number_cpus')) {
            $this->markTestSkipped('number_cpus function not found');
        }

        $ref = new ReflectionFunction('number_cpus');
        $file = $ref->getFileName();
        $source = file_get_contents($file);

        $this->assertStringNotContainsString('false !== $process', $source,
            'number_cpus() must not use loose false !== $process check in any branch');
        $this->assertStringContainsString('is_resource($process)', $source,
            'number_cpus() must guard all popen results with is_resource()');
    }

    public function testApplySecurityCatchesTypeErrorViaThrowable(): void
    {
        $ref = new ReflectionMethod('Bootstrap', 'applySecurity');
        $ref->setAccessible(true);

        $file = $ref->getFileName();
        $source = file_get_contents($file);

        $this->assertStringContainsString('catch (\\Throwable $e)', $source,
            'Bootstrap::applySecurity() must catch \\Throwable to handle TypeError from get_server_load()');
    }

}
