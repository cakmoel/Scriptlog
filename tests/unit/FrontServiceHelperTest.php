<?php

use PHPUnit\Framework\TestCase;
use Scriptlog\Core\Registry;
use Scriptlog\Service\FrontService;

/**
 * Front Service Helper Tests
 *
 * Covers front_service() added in src/lib/utility/front-service.php which
 * retrieves the shared FrontService instance from the global Registry.
 */
class FrontServiceHelperTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/lib/utility/front-service.php';
        require_once __DIR__ . '/../../src/lib/service/FrontService.php';

        if (Registry::isKeySet('frontService')) {
            Registry::set('frontService', null);
        }
    }

    public function testReturnsNullWhenServiceNotRegistered(): void
    {
        if (Registry::isKeySet('frontService')) {
            Registry::set('frontService', null);
        }
        $this->assertNull(front_service());
    }

    public function testReturnsFrontServiceWhenRegistered(): void
    {
        $service = new FrontService();
        Registry::set('frontService', $service);

        $this->assertInstanceOf(FrontService::class, front_service());
    }

    public function testReturnsNullWhenRegistryHoldsNonServiceValue(): void
    {
        Registry::set('frontService', new stdClass());
        $this->assertNull(front_service());
    }
}
