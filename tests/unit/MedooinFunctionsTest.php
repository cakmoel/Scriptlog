<?php
/**
 * Medoo Utility Functions Test
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../lib/common.php';
require_once __DIR__ . '/../../lib/utility/medooin.php';

class MedooinFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testDbBuildWhere()
    {
        $where = ['setting_name' => 'site_name', 'ID' => 1];
        $result = db_build_where($where);

        $this->assertStringContainsString('WHERE', $result['sql']);
        $this->assertStringContainsString('`setting_name` = ?', $result['sql']);
        $this->assertStringContainsString('`ID` = ?', $result['sql']);
        $this->assertStringContainsString(' AND ', $result['sql']);
        $this->assertCount(2, $result['params']);
        $this->assertEquals('site_name', $result['params'][0]);
        $this->assertEquals(1, $result['params'][1]);
    }

    public function testDbBuildWhereWithOperators()
    {
        $where = [
            'post_date>=' => '2024-01-01',
            'post_status' => 'publish'
        ];
        $result = db_build_where($where);

        $this->assertStringContainsString('`post_date` >= ?', $result['sql']);
        $this->assertStringContainsString('`post_status` = ?', $result['sql']);
        $this->assertEquals('2024-01-01', $result['params'][0]);
    }

    public function testDbBuildWhereWithInClause()
    {
        $where = [
            'user_level' => ['administrator', 'editor', 'author']
        ];
        $result = db_build_where($where);

        $this->assertStringContainsString('IN (?, ?, ?)', $result['sql']);
        $this->assertCount(3, $result['params']);
        $this->assertEquals(['administrator', 'editor', 'author'], $result['params']);
    }

    public function testDbBuildWhereEmpty()
    {
        $result = db_build_where([]);

        $this->assertEquals('', $result['sql']);
        $this->assertEquals([], $result['params']);
    }

    public function testIsDbDatabase()
    {
        $dbMock = new MockDbObject();
        $this->assertTrue(is_db_database($dbMock));
        $this->assertFalse(is_db_database(null));
        $this->assertFalse(is_db_database('string'));
        $this->assertFalse(is_db_database(123));
    }

    public function testIsMedooDatabase()
    {
        $dbMock = new MockDbObject();
        $this->assertTrue(is_medoo_database($dbMock));
        $this->assertFalse(is_medoo_database(null));
        $this->assertFalse(is_medoo_database(false));
    }

    public function testAppSettingsFunctionsWithMock()
    {
        $table = 'tbl_settings';
        $columns = ['ID', 'setting_name', 'setting_value'];
        $where = ['setting_name' => 'site_name'];

        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $whereClause = db_build_where($where);
        $sql = "SELECT {$cols} FROM {$table}" . $whereClause['sql'];

        $this->assertStringContainsString('SELECT ID, setting_name, setting_value', $sql);
        $this->assertStringContainsString('FROM tbl_settings', $sql);
        $this->assertStringContainsString('WHERE `setting_name` = ?', $sql);
        $this->assertEquals('site_name', $whereClause['params'][0]);
    }

    public function testAppTaglineQueryBuild()
    {
        $table = 'tbl_settings';
        $columns = ['ID', 'setting_name', 'setting_value'];
        $where = ['setting_name' => 'site_tagline'];

        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $whereClause = db_build_where($where);
        $sql = "SELECT {$cols} FROM {$table}" . $whereClause['sql'];

        $this->assertStringContainsString('WHERE', $sql);
        $this->assertEquals('site_tagline', $whereClause['params'][0]);
    }
}

class MockDbObject
{
    public function dbSelect($sql, $params = [])
    {
        return [];
    }

    public function dbInsert($table, $data)
    {
        return true;
    }

    public function dbUpdate($table, $data, $where)
    {
        return 1;
    }

    public function dbDelete($table, $where)
    {
        return 1;
    }

    public function dbReplace($table, $params, $updateParams)
    {
        return true;
    }
}
