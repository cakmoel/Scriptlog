<?php
/**
 * Medooin Integration Tests
 * 
 * Integration tests for medooin.php utility functions
 * Tests actual database operations with test database
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../lib/common.php';
require_once __DIR__ . '/../../lib/utility/medooin.php';

class MedooinIntegrationTest extends TestCase
{
    private static $dbc;
    private $testsRun = 0;

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
        
        $stmt->execute(['name' => 'test_site_name_int', 'value' => 'Integration Test Site']);
        $stmt->execute(['name' => 'test_site_tagline_int', 'value' => 'An integration test tagline']);
        $stmt->execute(['name' => 'test_key1_int', 'value' => 'int_value1']);
        $stmt->execute(['name' => 'test_key2_int', 'value' => 'int_value2']);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$dbc) {
            $stmt = self::$dbc->prepare("DELETE FROM tbl_settings WHERE setting_name LIKE 'test_%_int'");
            $stmt->execute();
        }
    }

    protected function setUp(): void
    {
        $this->testsRun++;
    }

    public function testDatabaseConnection()
    {
        $this->assertNotNull(self::$dbc);
        $this->assertInstanceOf(PDO::class, self::$dbc);
    }

    public function testDbBuildWhere()
    {
        $where = ['setting_name' => 'test_site_name_int', 'ID' => 1];
        $result = db_build_where($where);

        $this->assertStringContainsString('WHERE', $result['sql']);
        $this->assertStringContainsString('`setting_name` = ?', $result['sql']);
        $this->assertCount(2, $result['params']);
    }

    public function testDbBuildWhereWithMultipleConditions()
    {
        $where = [
            'setting_name' => 'test_key1_int',
            'ID >=' => 1
        ];
        $result = db_build_where($where);

        $this->assertStringContainsString('`ID` >= ?', $result['sql']);
        $this->assertStringContainsString(' AND ', $result['sql']);
    }

    public function testDbBuildWhereEmpty()
    {
        $result = db_build_where([]);

        $this->assertEquals('', $result['sql']);
        $this->assertEquals([], $result['params']);
    }

    public function testDirectQuerySiteName()
    {
        $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings WHERE `setting_name` = ?";
        $stmt = self::$dbc->prepare($sql);
        $stmt->execute(['test_site_name_int']);
        $result = $stmt->fetch();

        $this->assertNotFalse($result);
        $this->assertEquals('test_site_name_int', $result['setting_name']);
        $this->assertEquals('Integration Test Site', $result['setting_value']);
    }

    public function testDirectQuerySiteTagline()
    {
        $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings WHERE `setting_name` = ?";
        $stmt = self::$dbc->prepare($sql);
        $stmt->execute(['test_site_tagline_int']);
        $result = $stmt->fetch();

        $this->assertNotFalse($result);
        $this->assertEquals('test_site_tagline_int', $result['setting_name']);
        $this->assertEquals('An integration test tagline', $result['setting_value']);
    }

    public function testIsDbDatabaseWithRealDbObject()
    {
        $mockDb = new class {
            public function dbSelect($sql, $params = []) { return []; }
        };

        $this->assertTrue(is_db_database($mockDb));
        $this->assertFalse(is_db_database(null));
        $this->assertFalse(is_db_database('string'));
    }

    public function testIsMedooDatabase()
    {
        $mockDb = new class {
            public function dbSelect($sql, $params = []) { return []; }
        };

        $this->assertTrue(is_medoo_database($mockDb));
        $this->assertFalse(is_medoo_database(null));
        $this->assertFalse(is_medoo_database(false));
    }

    public function testMedooGetWhereLogic()
    {
        $table = 'tbl_settings';
        $columns = ['ID', 'setting_name', 'setting_value'];
        $where = ['setting_name' => 'test_site_name_int'];

        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $whereClause = db_build_where($where);
        $sql = "SELECT {$cols} FROM {$table}" . $whereClause['sql'];

        $stmt = self::$dbc->prepare($sql);
        $stmt->execute($whereClause['params']);
        $result = $stmt->fetch();

        $this->assertNotFalse($result);
        $this->assertEquals('test_site_name_int', $result['setting_name']);
        $this->assertEquals('Integration Test Site', $result['setting_value']);
    }

    public function testMedooColumnWhereLogic()
    {
        $table = 'tbl_settings';
        $columns = ['setting_name', 'setting_value'];
        $where = ['setting_name' => 'test_key2_int'];

        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $whereClause = db_build_where($where);
        $sql = "SELECT {$cols} FROM {$table}" . $whereClause['sql'];

        $stmt = self::$dbc->prepare($sql);
        $stmt->execute($whereClause['params']);
        $results = $stmt->fetchAll();

        $this->assertGreaterThanOrEqual(1, count($results));
        $this->assertEquals('test_key2_int', $results[0]['setting_name']);
        $this->assertEquals('int_value2', $results[0]['setting_value']);
    }

    public function testMedooColumnLogic()
    {
        $table = 'tbl_settings';
        $columns = ['setting_name'];

        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "SELECT {$cols} FROM {$table}";

        $stmt = self::$dbc->query($sql);
        $results = $stmt->fetchAll();

        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));
    }
}
