<?php
/**
 * Integration Tests for Settings
 * 
 * Tests database operations for settings table
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class SettingsIntegrationTest extends TestCase
{
    private static $pdo;
    
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
    }
    
    public function testInsertSetting(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_settings (setting_name, setting_value)
            VALUES (?, ?)
        ");
        
        $result = $stmt->execute([
            'test_setting',
            'test_value'
        ]);
        
        $this->assertTrue($result);
        
        $id = self::$pdo->lastInsertId();
        
        // Cleanup
        self::$pdo->exec("DELETE FROM tbl_settings WHERE ID = " . $id);
    }
    
    public function testSelectSetting(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_settings (setting_name, setting_value)
            VALUES (?, ?)
        ");
        
        $stmt->execute(['test_setting_select', 'test_value']);
        $id = self::$pdo->lastInsertId();
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_settings WHERE ID = ?");
        $stmt->execute([$id]);
        $setting = $stmt->fetch();
        
        $this->assertIsArray($setting);
        $this->assertEquals('test_setting_select', $setting['setting_name']);
        
        // Cleanup
        self::$pdo->exec("DELETE FROM tbl_settings WHERE ID = " . $id);
    }
    
    public function testUpdateSetting(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_settings (setting_name, setting_value)
            VALUES (?, ?)
        ");
        
        $stmt->execute(['test_setting_update', 'old_value']);
        $id = self::$pdo->lastInsertId();
        
        $stmt = self::$pdo->prepare("UPDATE tbl_settings SET setting_value = ? WHERE ID = ?");
        $stmt->execute(['new_value', $id]);
        
        $stmt = self::$pdo->prepare("SELECT setting_value FROM tbl_settings WHERE ID = ?");
        $stmt->execute([$id]);
        $setting = $stmt->fetch();
        
        $this->assertEquals('new_value', $setting['setting_value']);
        
        // Cleanup
        self::$pdo->exec("DELETE FROM tbl_settings WHERE ID = " . $id);
    }
    
    public function testSelectAllSettings(): void
    {
        $stmt = self::$pdo->query("SELECT * FROM tbl_settings");
        $settings = $stmt->fetchAll();
        
        $this->assertIsArray($settings);
    }
}
