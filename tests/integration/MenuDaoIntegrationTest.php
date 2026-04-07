<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * MenuDao Integration Test
 * 
 * Tests for menu CRUD operations with database.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class MenuDaoIntegrationTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static ?int $menuId = null;
    
    private const TEST_LABEL = 'Test Menu';
    private const TEST_LINK = '/test-menu';

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO(
            'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Verify table exists
        $tables = self::$pdo->query("SHOW TABLES LIKE 'tbl_menu'")->fetchAll();
        if (empty($tables)) {
            self::markTestSkipped('tbl_menu table does not exist');
        }
        
        // Clean up existing test menus
        self::$pdo->exec("DELETE FROM tbl_menu WHERE menu_link LIKE '%test-%'");
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$menuId) {
            self::$pdo->exec("DELETE FROM tbl_menu WHERE ID = " . self::$menuId);
        }
        
        if (self::$pdo) {
            self::$pdo = null;
        }
    }
    
    protected function setUp(): void
    {
        self::$menuId = null;
    }

    public function testInsertMenu(): void
    {
        $link = self::TEST_LINK . '-' . time();
        
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_menu (menu_label, menu_link, menu_status, menu_visibility, menu_sort)
            VALUES (?, ?, 'Y', 'public', 0)
        ");
        
        $result = $stmt->execute([
            self::TEST_LABEL,
            $link
        ]);
        
        $this->assertTrue($result);
        
        self::$menuId = (int)self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$menuId);
    }
    
    public function testSelectMenuById(): void
    {
        if (!self::$menuId) {
            $this->testInsertMenu();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_menu WHERE ID = ?");
        $stmt->execute([self::$menuId]);
        $menu = $stmt->fetch();
        
        $this->assertIsArray($menu);
        $this->assertEquals(self::TEST_LABEL, $menu['menu_label']);
    }
    
    public function testUpdateMenu(): void
    {
        if (!self::$menuId) {
            $this->testInsertMenu();
        }
        
        $newLabel = 'Updated Menu Label';
        
        $stmt = self::$pdo->prepare("UPDATE tbl_menu SET menu_label = ? WHERE ID = ?");
        $result = $stmt->execute([$newLabel, self::$menuId]);
        
        $this->assertTrue($result);
        
        // Verify update
        $stmt = self::$pdo->prepare("SELECT menu_label FROM tbl_menu WHERE ID = ?");
        $stmt->execute([self::$menuId]);
        $menu = $stmt->fetch();
        
        $this->assertEquals($newLabel, $menu['menu_label']);
    }
    
    public function testDeleteMenu(): void
    {
        if (!self::$menuId) {
            $this->testInsertMenu();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_menu WHERE ID = ?");
        $result = $stmt->execute([self::$menuId]);
        
        $this->assertTrue($result);
        
        // Verify deletion
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_menu WHERE ID = ?");
        $stmt->execute([self::$menuId]);
        $menu = $stmt->fetch();
        
        $this->assertFalse($menu);
        self::$menuId = null;
    }
    
    public function testSelectMenusByStatus(): void
    {
        $stmt = self::$pdo->query("SELECT * FROM tbl_menu WHERE menu_status = 'Y'");
        $menus = $stmt->fetchAll();
        
        $this->assertIsArray($menus);
    }
    
    public function testCountMenus(): void
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) as total FROM tbl_menu");
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['total']);
    }
}
