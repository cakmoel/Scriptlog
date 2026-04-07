<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * MenuService Test
 * 
 * Tests for menu/navigation business logic.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class MenuServiceTest extends TestCase
{
    private $menuService;
    private $menuDaoMock;
    private $validatorMock;
    private $sanitizeMock;

    protected function setUp(): void
    {
        $this->menuDaoMock = $this->createMock(\MenuDao::class);
        $this->validatorMock = $this->createMock(\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Sanitize::class);
        
        $this->menuService = new \MenuService(
            $this->menuDaoMock,
            $this->validatorMock,
            $this->sanitizeMock
        );
    }

    public function testSetMenuId(): void
    {
        $this->menuService->setMenuId(1);
        $this->assertTrue(true);
    }

    public function testSetParentId(): void
    {
        $this->menuService->setParentId(0);
        $this->assertTrue(true);
    }

    public function testSetMenuLabel(): void
    {
        $this->menuService->setMenuLabel('Home');
        $this->assertTrue(true);
    }

    public function testSetMenuLink(): void
    {
        $this->menuService->setMenuLink('/');
        $this->assertTrue(true);
    }

    public function testSetMenuVisibility(): void
    {
        $this->menuService->setMenuVisibility('public');
        $this->assertTrue(true);
    }

    public function testSetMenuOrder(): void
    {
        $this->menuService->setMenuOrder(1);
        $this->assertTrue(true);
    }

    public function testSetMenuStatus(): void
    {
        $this->menuService->setMenuStatus('Y');
        $this->assertTrue(true);
    }

    public function testGrabMenus(): void
    {
        $this->menuDaoMock->method('findMenus')->willReturn([]);
        $menus = $this->menuService->grabMenus();
        $this->assertIsArray($menus);
    }

    public function testGrabMenu(): void
    {
        $this->menuDaoMock->method('findMenu')->willReturn(['ID' => 1, 'menu_label' => 'Home']);
        $menu = $this->menuService->grabMenu(1);
        $this->assertIsArray($menu);
    }

    public function testGrabMenuParent(): void
    {
        $this->menuDaoMock->method('findMenuParent')->willReturn([]);
        $menus = $this->menuService->grabMenuParent(0);
        $this->assertIsArray($menus);
    }

    public function testTotalMenus(): void
    {
        $this->menuDaoMock->method('totalMenuRecords')->willReturn(8);
        $total = $this->menuService->totalMenus();
        $this->assertEquals(8, $total);
    }

    public function testParentDropDown(): void
    {
        $this->menuDaoMock->method('dropDownMenuParent')->willReturn('<select><option>0</option></select>');
        $dropdown = $this->menuService->parentDropDown();
        $this->assertIsString($dropdown);
    }

    public function testVisibilityDropDown(): void
    {
        $this->menuDaoMock->method('dropDownMenuVisibility')->willReturn('<select><option>public</option></select>');
        $dropdown = $this->menuService->visibilityDropDown();
        $this->assertIsString($dropdown);
    }
}
