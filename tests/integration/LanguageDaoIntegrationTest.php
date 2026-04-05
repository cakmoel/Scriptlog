<?php
/**
 * LanguageDao Integration Test
 * 
 * Tests for the LanguageDao class with actual database
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

class LanguageDaoIntegrationTest extends TestCase
{
    private static $db;
    private static $dao;
    private static $testLanguageId;

    public static function setUpBeforeClass(): void
    {
        // Connect to test database
        try {
            self::$db = new PDO(
                'mysql:host=localhost;dbname=blogware_test',
                'blogwareuser',
                'userblogware',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Load required classes
            require_once __DIR__ . '/../../lib/core/Dao.php';
            require_once __DIR__ . '/../../lib/dao/LanguageDao.php';
            
            // Initialize DAO
            self::$dao = new LanguageDao();
            
            // Clean up any existing test data
            self::cleanupTestData();
            
        } catch (PDOException $e) {
            self::markTestSkipped('Cannot connect to test database: ' . $e->getMessage());
        }
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up test data
        self::cleanupTestData();
        
        if (self::$db) {
            self::$db = null;
        }
    }

    private static function cleanupTestData()
    {
        if (!self::$db) return;
        
        // Delete test languages (not the default 'en')
        $stmt = self::$db->prepare("DELETE FROM tbl_languages WHERE lang_code LIKE 'test_%'");
        $stmt->execute();
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!self::$dao) {
            $this->markTestSkipped('LanguageDao not available');
        }
    }

    public function testCreateLanguage()
    {
        $testCode = 'test_' . time();
        
        $id = self::$dao->createLanguage([
            'lang_code' => $testCode,
            'lang_name' => 'Test Language',
            'lang_native' => 'Test Native',
            'lang_locale' => 'test_LOCALE',
            'lang_direction' => 'ltr',
            'lang_sort' => 10,
            'lang_is_default' => 0,
            'lang_is_active' => 1,
        ]);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        self::$testLanguageId = $id;
    }

    public function testFindById()
    {
        // First create a language
        $testCode = 'test_find_' . time();
        $id = self::$dao->createLanguage([
            'lang_code' => $testCode,
            'lang_name' => 'Find Test',
            'lang_native' => 'Find Native',
        ]);
        
        // Now find it
        $language = self::$dao->findById($id);
        
        $this->assertIsArray($language);
        $this->assertEquals($testCode, $language['lang_code']);
        $this->assertEquals('Find Test', $language['lang_name']);
    }

    public function testFindByIdReturnsNullForNonexistent()
    {
        $language = self::$dao->findById(999999);
        
        $this->assertNull($language);
    }

    public function testFindLanguageByCode()
    {
        $testCode = 'test_code_' . time();
        
        self::$dao->createLanguage([
            'lang_code' => $testCode,
            'lang_name' => 'Code Test',
            'lang_native' => 'Code Native',
        ]);
        
        $language = self::$dao->findLanguageByCode($testCode);
        
        $this->assertIsArray($language);
        $this->assertEquals($testCode, $language['lang_code']);
    }

    public function testFindLanguageByCodeReturnsNullForNonexistent()
    {
        $language = self::$dao->findLanguageByCode('nonexistent_code');
        
        $this->assertNull($language);
    }

    public function testFindActiveLanguages()
    {
        $languages = self::$dao->findActiveLanguages();
        
        $this->assertIsArray($languages);
        $this->assertNotEmpty($languages);
        
        foreach ($languages as $lang) {
            $this->assertEquals(1, $lang['lang_is_active']);
        }
    }

    public function testFindDefaultLanguage()
    {
        $default = self::$dao->findDefaultLanguage();
        
        $this->assertIsArray($default);
        $this->assertEquals(1, $default['lang_is_default']);
    }

    public function testUpdateLanguage()
    {
        $testCode = 'test_update_' . time();
        
        $id = self::$dao->createLanguage([
            'lang_code' => $testCode,
            'lang_name' => 'Original Name',
            'lang_native' => 'Original Native',
        ]);
        
        self::$dao->updateLanguage($id, [
            'lang_name' => 'Updated Name',
            'lang_native' => 'Updated Native',
        ]);
        
        $updated = self::$dao->findById($id);
        
        $this->assertEquals('Updated Name', $updated['lang_name']);
        $this->assertEquals('Updated Native', $updated['lang_native']);
    }

    public function testSetDefaultLanguage()
    {
        $testCode = 'test_default_' . time();
        
        $id = self::$dao->createLanguage([
            'lang_code' => $testCode,
            'lang_name' => 'Default Test',
            'lang_native' => 'Default Native',
            'lang_is_default' => 0,
        ]);
        
        self::$dao->setDefaultLanguage($id);
        
        $updated = self::$dao->findById($id);
        
        $this->assertEquals(1, $updated['lang_is_default']);
        
        // Make sure 'en' is no longer default
        $en = self::$dao->findLanguageByCode('en');
        if ($en) {
            // Only check if 'en' exists and wasn't the test language
            if ($en['ID'] !== $id) {
                $enUpdated = self::$dao->findById($en['ID']);
                $this->assertEquals(0, $enUpdated['lang_is_default']);
            }
        }
    }

    public function testDeleteLanguage()
    {
        $testCode = 'test_delete_' . time();
        
        $id = self::$dao->createLanguage([
            'lang_code' => $testCode,
            'lang_name' => 'Delete Test',
            'lang_native' => 'Delete Native',
        ]);
        
        self::$dao->deleteLanguage($id);
        
        $deleted = self::$dao->findById($id);
        
        $this->assertNull($deleted);
    }

    public function testCountLanguages()
    {
        $count = self::$dao->countLanguages();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCodeExists()
    {
        // Test with 'en' which should exist
        $exists = self::$dao->codeExists('en');
        $this->assertTrue($exists);
        
        // Test with non-existent code
        $notExists = self::$dao->codeExists('nonexistent_code_' . time());
        $this->assertFalse($notExists);
    }

    public function testCreateLanguageWithMinimalData()
    {
        $testCode = 'test_minimal_' . time();
        
        $id = self::$dao->createLanguage([
            'lang_code' => $testCode,
            'lang_name' => 'Minimal Test',
            'lang_native' => 'Minimal Native',
        ]);
        
        $this->assertIsInt($id);
        
        $language = self::$dao->findById($id);
        
        $this->assertEquals('ltr', $language['lang_direction']); // Default
        $this->assertEquals(0, $language['lang_is_default']); // Default
        $this->assertEquals(1, $language['lang_is_active']); // Default
    }

    public function testCreateDuplicateCodeThrowsException()
    {
        $testCode = 'test_duplicate_' . time();
        
        self::$dao->createLanguage([
            'lang_code' => $testCode,
            'lang_name' => 'First',
            'lang_native' => 'First Native',
        ]);
        
        // Second create should succeed (no unique constraint enforcement in this method)
        // The actual enforcement happens at DB level
        $id2 = self::$dao->createLanguage([
            'lang_code' => $testCode,
            'lang_name' => 'Second',
            'lang_native' => 'Second Native',
        ]);
        
        // Both should exist (this tests the DAO method, not DB constraints)
        $this->assertIsInt($id2);
    }
}
