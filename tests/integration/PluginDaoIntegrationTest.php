<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PluginDao Integration Test
 * 
 * Tests for plugin CRUD operations with database.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PluginDaoIntegrationTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static ?int $pluginId = null;
    
    private const TEST_NAME = 'Test Plugin';
    private const TEST_LINK = 'test-plugin';
    private const TEST_DIRECTORY = 'test-plugin';

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
        $tables = self::$pdo->query("SHOW TABLES LIKE 'tbl_plugin'")->fetchAll();
        if (empty($tables)) {
            self::markTestSkipped('tbl_plugin table does not exist');
        }
        
        // Clean up existing test plugins
        self::$pdo->exec("DELETE FROM tbl_plugin WHERE plugin_directory LIKE 'test-%'");
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$pluginId) {
            self::$pdo->exec("DELETE FROM tbl_plugin WHERE ID = " . self::$pluginId);
        }
        
        if (self::$pdo) {
            self::$pdo = null;
        }
    }
    
    protected function setUp(): void
    {
        self::$pluginId = null;
    }

    public function testInsertPlugin(): void
    {
        $directory = self::TEST_DIRECTORY . '-' . time();
        
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_plugin (plugin_name, plugin_link, plugin_directory, plugin_desc, plugin_status, plugin_level, plugin_sort)
            VALUES (?, ?, ?, 'Test plugin description', 'Y', 'administrator', 0)
        ");
        
        $result = $stmt->execute([
            self::TEST_NAME,
            self::TEST_LINK,
            $directory
        ]);
        
        $this->assertTrue($result);
        
        self::$pluginId = (int)self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$pluginId);
    }
    
    public function testSelectPluginById(): void
    {
        if (!self::$pluginId) {
            $this->testInsertPlugin();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_plugin WHERE ID = ?");
        $stmt->execute([self::$pluginId]);
        $plugin = $stmt->fetch();
        
        $this->assertIsArray($plugin);
        $this->assertEquals(self::TEST_NAME, $plugin['plugin_name']);
    }
    
    public function testSelectPluginByDirectory(): void
    {
        if (!self::$pluginId) {
            $this->testInsertPlugin();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_plugin WHERE plugin_directory LIKE 'test-plugin-%'");
        $stmt->execute();
        $plugins = $stmt->fetchAll();
        
        $this->assertIsArray($plugins);
        $this->assertNotEmpty($plugins);
    }
    
    public function testUpdatePlugin(): void
    {
        if (!self::$pluginId) {
            $this->testInsertPlugin();
        }
        
        $newName = 'Updated Plugin Name';
        
        $stmt = self::$pdo->prepare("UPDATE tbl_plugin SET plugin_name = ? WHERE ID = ?");
        $result = $stmt->execute([$newName, self::$pluginId]);
        
        $this->assertTrue($result);
        
        // Verify update
        $stmt = self::$pdo->prepare("SELECT plugin_name FROM tbl_plugin WHERE ID = ?");
        $stmt->execute([self::$pluginId]);
        $plugin = $stmt->fetch();
        
        $this->assertEquals($newName, $plugin['plugin_name']);
    }
    
    public function testDeletePlugin(): void
    {
        if (!self::$pluginId) {
            $this->testInsertPlugin();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_plugin WHERE ID = ?");
        $result = $stmt->execute([self::$pluginId]);
        
        $this->assertTrue($result);
        
        // Verify deletion
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_plugin WHERE ID = ?");
        $stmt->execute([self::$pluginId]);
        $plugin = $stmt->fetch();
        
        $this->assertFalse($plugin);
        self::$pluginId = null;
    }
    
    public function testSelectActivePlugins(): void
    {
        $stmt = self::$pdo->query("SELECT * FROM tbl_plugin WHERE plugin_status = 'Y'");
        $plugins = $stmt->fetchAll();
        
        $this->assertIsArray($plugins);
    }
    
    public function testCountPlugins(): void
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) as total FROM tbl_plugin");
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['total']);
    }
}
