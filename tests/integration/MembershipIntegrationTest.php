<?php
/**
 * Membership Integration Tests
 * 
 * Integration tests for membership.php utility functions
 * Tests actual database operations with test database
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/lib/common.php';
require_once __DIR__ . '/../../src/lib/utility/medooin.php';

class MembershipIntegrationTest extends TestCase
{
    private static $dbc;

    public static function setUpBeforeClass(): void
    {
        try {
            self::$dbc = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            self::$dbc = null;
            return;
        }

        self::setupTestData();
    }

    public static function setupTestData()
    {
        if (!self::$dbc) return;

        $stmt = self::$dbc->prepare("
            INSERT IGNORE INTO tbl_settings (setting_name, setting_value) 
            VALUES (:name, :value)
        ");
        
        $stmt->execute(['name' => 'membership_setting', 'value' => '{"user_can_register":"1","default_role":"subscriber"}']);
        $stmt->execute(['name' => 'test_membership_disabled', 'value' => '{"user_can_register":"0","default_role":"author"}']);
        $stmt->execute(['name' => 'test_membership_no_role', 'value' => '{"user_can_register":"1"}']);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$dbc) {
            $stmt = self::$dbc->prepare("DELETE FROM tbl_settings WHERE setting_name LIKE 'test_%' OR setting_name = 'membership_setting'");
            $stmt->execute();
        }
    }

    public function testDatabaseConnection()
    {
        $this->assertNotNull(self::$dbc);
    }

    public function testMembershipSettingQuery()
    {
        $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings WHERE setting_name = ?";
        $stmt = self::$dbc->prepare($sql);
        $stmt->execute(['membership_setting']);
        $result = $stmt->fetch();

        $this->assertNotFalse($result);
        $this->assertEquals('membership_setting', $result['setting_name']);
        
        $decoded = json_decode($result['setting_value'], true);
        $this->assertIsArray($decoded);
        $this->assertEquals('1', $decoded['user_can_register']);
    }

    public function testRegistrationEnabled()
    {
        $sql = "SELECT setting_value FROM tbl_settings WHERE setting_name = ?";
        $stmt = self::$dbc->prepare($sql);
        $stmt->execute(['membership_setting']);
        $result = $stmt->fetch();

        $canRegister = json_decode($result['setting_value'], true);
        $isEnabled = (isset($canRegister['user_can_register']) && $canRegister['user_can_register'] == '1');

        $this->assertTrue($isEnabled);
    }

    public function testRegistrationDisabled()
    {
        $sql = "SELECT setting_value FROM tbl_settings WHERE setting_name = ?";
        $stmt = self::$dbc->prepare($sql);
        $stmt->execute(['test_membership_disabled']);
        $result = $stmt->fetch();

        $canRegister = json_decode($result['setting_value'], true);
        $isEnabled = (isset($canRegister['user_can_register']) && $canRegister['user_can_register'] == '1');

        $this->assertFalse($isEnabled);
    }

    public function testDefaultRoleExtraction()
    {
        $sql = "SELECT setting_value FROM tbl_settings WHERE setting_name = ?";
        $stmt = self::$dbc->prepare($sql);
        $stmt->execute(['membership_setting']);
        $result = $stmt->fetch();

        $decoded = json_decode($result['setting_value'], true);
        $defaultRole = isset($decoded['default_role']) ? $decoded['default_role'] : '';

        $this->assertEquals('subscriber', $defaultRole);
    }

    public function testMedooGetWhereForMembership()
    {
        $table = 'tbl_settings';
        $columns = ['ID', 'setting_name', 'setting_value'];
        $where = ['setting_name' => 'membership_setting'];

        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $whereClause = db_build_where($where);
        $sql = "SELECT {$cols} FROM {$table}" . $whereClause['sql'];

        $stmt = self::$dbc->prepare($sql);
        $stmt->execute($whereClause['params']);
        $result = $stmt->fetch();

        $this->assertNotFalse($result);
        $this->assertEquals('membership_setting', $result['setting_name']);
        
        $decoded = json_decode($result['setting_value'], true);
        $this->assertEquals('1', $decoded['user_can_register']);
        $this->assertEquals('subscriber', $decoded['default_role']);
    }
}
