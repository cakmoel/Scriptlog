<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * ThemeDao Integration Test
 * 
 * Tests for theme CRUD operations with database.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class ThemeDaoIntegrationTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static ?int $themeId = null;
    
    private const TEST_TITLE = 'Test Theme';
    private const TEST_DIRECTORY = 'test-theme';

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
        $tables = self::$pdo->query("SHOW TABLES LIKE 'tbl_themes'")->fetchAll();
        if (empty($tables)) {
            self::markTestSkipped('tbl_themes table does not exist');
        }
        
        // Clean up existing test themes
        self::$pdo->exec("DELETE FROM tbl_themes WHERE theme_directory LIKE 'test-%'");
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$themeId) {
            self::$pdo->exec("DELETE FROM tbl_themes WHERE ID = " . self::$themeId);
        }
        
        if (self::$pdo) {
            self::$pdo = null;
        }
    }
    
    protected function setUp(): void
    {
        self::$themeId = null;
    }

    public function testInsertTheme(): void
    {
        $directory = self::TEST_DIRECTORY . '-' . time();
        
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_themes (theme_title, theme_desc, theme_designer, theme_directory, theme_status)
            VALUES (?, 'Test theme description', 'Test Designer', ?, 'Y')
        ");
        
        $result = $stmt->execute([
            self::TEST_TITLE,
            $directory
        ]);
        
        $this->assertTrue($result);
        
        self::$themeId = (int)self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$themeId);
    }
    
    public function testSelectThemeById(): void
    {
        if (!self::$themeId) {
            $this->testInsertTheme();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_themes WHERE ID = ?");
        $stmt->execute([self::$themeId]);
        $theme = $stmt->fetch();
        
        $this->assertIsArray($theme);
        $this->assertEquals(self::TEST_TITLE, $theme['theme_title']);
    }
    
    public function testSelectThemeByDirectory(): void
    {
        if (!self::$themeId) {
            $this->testInsertTheme();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_themes WHERE theme_directory LIKE 'test-theme-%'");
        $stmt->execute();
        $themes = $stmt->fetchAll();
        
        $this->assertIsArray($themes);
        $this->assertNotEmpty($themes);
    }
    
    public function testUpdateTheme(): void
    {
        if (!self::$themeId) {
            $this->testInsertTheme();
        }
        
        $newTitle = 'Updated Theme Title';
        
        $stmt = self::$pdo->prepare("UPDATE tbl_themes SET theme_title = ? WHERE ID = ?");
        $result = $stmt->execute([$newTitle, self::$themeId]);
        
        $this->assertTrue($result);
        
        // Verify update
        $stmt = self::$pdo->prepare("SELECT theme_title FROM tbl_themes WHERE ID = ?");
        $stmt->execute([self::$themeId]);
        $theme = $stmt->fetch();
        
        $this->assertEquals($newTitle, $theme['theme_title']);
    }
    
    public function testDeleteTheme(): void
    {
        if (!self::$themeId) {
            $this->testInsertTheme();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_themes WHERE ID = ?");
        $result = $stmt->execute([self::$themeId]);
        
        $this->assertTrue($result);
        
        // Verify deletion
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_themes WHERE ID = ?");
        $stmt->execute([self::$themeId]);
        $theme = $stmt->fetch();
        
        $this->assertFalse($theme);
        self::$themeId = null;
    }
    
    public function testSelectActiveThemes(): void
    {
        $stmt = self::$pdo->query("SELECT * FROM tbl_themes WHERE theme_status = 'Y'");
        $themes = $stmt->fetchAll();
        
        $this->assertIsArray($themes);
    }
    
    public function testCountThemes(): void
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) as total FROM tbl_themes");
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['total']);
    }
}
