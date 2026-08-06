<?php

use PHPUnit\Framework\TestCase;

class IServiceThrowableTest extends TestCase
{
    private string $interfacePath;

    protected function setUp(): void
    {
        $this->interfacePath = __DIR__ . '/../../src/lib/core/IServiceThrowable.php';
    }

    public function testInterfaceExists(): void
    {
        require_once $this->interfacePath;
        $this->assertTrue(interface_exists('IServiceThrowable'));
    }

    public function testInterfaceExtendsIThrowable(): void
    {
        require_once $this->interfacePath;
        $reflection = new ReflectionClass('IServiceThrowable');
        $interfaces = $reflection->getInterfaceNames();
        $this->assertContains('Scriptlog\Core\IThrowable', $interfaces);
    }

    public function testServiceExceptionImplementsIServiceThrowable(): void
    {
        require_once $this->interfacePath;
        require_once __DIR__ . '/../../src/lib/core/IThrowable.php';
        require_once __DIR__ . '/../../src/lib/core/ServiceException.php';
        $reflection = new ReflectionClass('ServiceException');
        $this->assertTrue($reflection->implementsInterface('IServiceThrowable'));
    }
}
