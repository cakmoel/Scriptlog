<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class Psr4AutoloadVerificationTest extends TestCase
{
    public function testPsr4AutoloadTestClassExists(): void
    {
        $this->assertTrue(class_exists('Scriptlog\Core\Psr4AutoloadTest'));
    }

    public function testHelloReturnsCorrectMessage(): void
    {
        $this->assertEquals(
            'PSR-4 autoloading is working correctly',
            \Scriptlog\Core\Psr4AutoloadTest::hello()
        );
    }

    public function testFqcnReturnsCorrectClassName(): void
    {
        $this->assertEquals(
            'Scriptlog\Core\Psr4AutoloadTest',
            \Scriptlog\Core\Psr4AutoloadTest::fqcn()
        );
    }
}
