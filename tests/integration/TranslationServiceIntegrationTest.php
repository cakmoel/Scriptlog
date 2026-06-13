<?php
defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * TranslationService Integration Test
 * 
 * Tests for the TranslationService class with actual database
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

class TranslationServiceIntegrationTest extends TestCase
{
    private static $db;
    private static $languageDao;
    private static $translationDao;
    private static $service;
    private static $testLangId;
    private static $testLangCode = 'test_' . 'tr_' . 1;

    public static function setUpBeforeClass(): void
    {
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
            require_once __DIR__ . '/../../lib/dao/TranslationDao.php';
            require_once __DIR__ . '/../../lib/core/TranslationLoader.php';
            require_once __DIR__ . '/../../lib/service/TranslationService.php';
             require_once __DIR__ . '/../../lib/core/FormValidator.php';
             require_once __DIR__ . '/../../lib/core/Sanitize.php';
            
            // Initialize DAOs
            self::$languageDao = new LanguageDao();
            self::$translationDao = new TranslationDao();
            
            // Create test language if it doesn't exist
            self::setupTestLanguage();
            
            // Initialize service
            self::$service = new TranslationService(
                self::$translationDao,
                self::$languageDao,
                null, // TranslationLoader - can be null for basic tests
                new FormValidator(),
                new Sanitize()
            );
            
            // Clean up test translation data
            self::cleanupTestData();
            
        } catch (PDOException $e) {
            self::markTestSkipped('Cannot connect to test database: ' . $e->getMessage());
        } catch (Exception $e) {
            self::markTestSkipped('Setup error: ' . $e->getMessage());
        }
    }

    private static function setupTestLanguage()
    {
        // Check if test language exists
        $existing = self::$languageDao->findLanguageByCode(self::$testLangCode);
        
        if (!$existing) {
            self::$testLangId = self::$languageDao->createLanguage([
                'lang_code' => self::$testLangCode,
                'lang_name' => 'Test Translation Language',
                'lang_native' => 'Test Native',
                'lang_direction' => 'ltr',
                'lang_sort' => 100,
                'lang_is_default' => 0,
                'lang_is_active' => 1,
            ]);
        } else {
            self::$testLangId = $existing['ID'];
        }
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up test data
        self::cleanupTestData();
        
        // Delete test language
        if (self::$languageDao && self::$testLangId) {
            try {
                self::$languageDao->deleteLanguage(self::$testLangId);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
        
        if (self::$db) {
            self::$db = null;
        }
    }

    private static function cleanupTestData()
    {
        if (!self::$translationDao) return;
        
        // Delete test translations
        $stmt = self::$db->prepare("DELETE FROM tbl_translations WHERE translation_key LIKE 'test_%'");
        $stmt->execute();
    }

    public function testGetTranslationsReturnsArray()
    {
        $translations = self::$service->getTranslations(self::$testLangCode);
        
        $this->assertIsArray($translations);
    }

    public function testGetTranslationsByContext()
    {
        // First create a translation with context
        self::$translationDao->createTranslation([
            'lang_id' => self::$testLangId,
            'translation_key' => 'test_context_nav',
            'translation_value' => 'Navigation',
            'translation_context' => 'menu',
        ]);
        
        $translations = self::$service->getTranslationsByContext(self::$testLangCode, 'menu');
        
        $this->assertIsArray($translations);
        
        // Find our test translation
        $found = false;
        foreach ($translations as $t) {
            if ($t['translation_key'] === 'test_context_nav') {
                $found = true;
                $this->assertEquals('Navigation', $t['translation_value']);
                break;
            }
        }
    }

    public function testSearchTranslations()
    {
        // Create test translation
        self::$translationDao->createTranslation([
            'lang_id' => self::$testLangId,
            'translation_key' => 'test_search_' . time(),
            'translation_value' => 'Search Test Value',
            'translation_context' => 'search',
        ]);
        
        $results = self::$service->searchTranslations(self::$testLangCode, 'Search Test');
        
        $this->assertIsArray($results);
    }

    public function testCreateTranslation()
    {
        $key = 'test_create_' . time();
        
        $id = self::$service->createTranslation([
            'lang_code' => self::$testLangCode,
            'translation_key' => $key,
            'translation_value' => 'Created Translation',
            'translation_context' => 'test',
        ]);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function testUpdateTranslation()
    {
        // Create translation first
        $id = self::$translationDao->createTranslation([
            'lang_id' => self::$testLangId,
            'translation_key' => 'test_update_' . time(),
            'translation_value' => 'Original Value',
            'translation_context' => 'test',
        ]);
        
        // Update it
        self::$service->updateTranslation($id, [
            'translation_value' => 'Updated Value',
        ]);
        
        // Verify update
        $updated = self::$translationDao->findById($id);
        $this->assertEquals('Updated Value', $updated['translation_value']);
    }

    public function testDeleteTranslation()
    {
        // Create translation
        $id = self::$translationDao->createTranslation([
            'lang_id' => self::$testLangId,
            'translation_key' => 'test_delete_' . time(),
            'translation_value' => 'To Be Deleted',
            'translation_context' => 'test',
        ]);
        
        // Delete it
        self::$service->deleteTranslation($id);
        
        // Verify deletion
        $deleted = self::$translationDao->findById($id);
        $this->assertNull($deleted);
    }

    public function testExportToArray()
    {
        // Create some test translations
        self::$translationDao->createTranslation([
            'lang_id' => self::$testLangId,
            'translation_key' => 'test_export_1',
            'translation_value' => 'Export 1',
        ]);
        self::$translationDao->createTranslation([
            'lang_id' => self::$testLangId,
            'translation_key' => 'test_export_2',
            'translation_value' => 'Export 2',
        ]);
        
        $export = self::$service->exportToArray(self::$testLangCode);
        
        $this->assertIsArray($export);
        $this->assertArrayHasKey('test_export_1', $export);
        $this->assertArrayHasKey('test_export_2', $export);
        $this->assertEquals('Export 1', $export['test_export_1']);
    }

    public function testImportFromArray()
    {
        $translations = [
            'test_import_1' => 'Imported 1',
            'test_import_2' => 'Imported 2',
            'test_import_3' => 'Imported 3',
        ];
        
        $count = self::$service->importFromArray(self::$testLangCode, $translations);
        
        $this->assertEquals(3, $count);
        
        // Verify imports
        $export = self::$service->exportToArray(self::$testLangCode);
        $this->assertEquals('Imported 1', $export['test_import_1']);
        $this->assertEquals('Imported 2', $export['test_import_2']);
        $this->assertEquals('Imported 3', $export['test_import_3']);
    }

    public function testImportFromArrayUpdatesExisting()
    {
        // Create initial translation
        self::$translationDao->createTranslation([
            'lang_id' => self::$testLangId,
            'translation_key' => 'test_import_update',
            'translation_value' => 'Original',
        ]);
        
        // Import with same key but different value
        $translations = [
            'test_import_update' => 'Updated via Import',
        ];
        
        $count = self::$service->importFromArray(self::$testLangCode, $translations);
        
        $this->assertEquals(1, $count);
        
        // Verify update
        $export = self::$service->exportToArray(self::$testLangCode);
        $this->assertEquals('Updated via Import', $export['test_import_update']);
    }

    public function testGetContexts()
    {
        // Create translations with different contexts
        self::$translationDao->createTranslation([
            'lang_id' => self::$testLangId,
            'translation_key' => 'test_context_a',
            'translation_value' => 'Context A',
            'translation_context' => 'menu',
        ]);
        self::$translationDao->createTranslation([
            'lang_id' => self::$testLangId,
            'translation_key' => 'test_context_b',
            'translation_value' => 'Context B',
            'translation_context' => 'form',
        ]);
        
        $contexts = self::$service->getContexts();
        
        $this->assertIsArray($contexts);
    }

    public function testGetTranslationsWithNonexistentLanguage()
    {
        $translations = self::$service->getTranslations('nonexistent_language_code');
        
        $this->assertIsArray($translations);
        $this->assertEmpty($translations);
    }

    public function testValidateTranslationDataRequiresKey()
    {
        $this->expectException(ServiceException::class);
        
        self::$service->createTranslation([
            'lang_code' => self::$testLangCode,
            'translation_key' => '',
            'translation_value' => 'Value',
        ]);
    }

    public function testValidateTranslationDataRequiresValue()
    {
        $this->expectException(ServiceException::class);
        
        self::$service->createTranslation([
            'lang_code' => self::$testLangCode,
            'translation_key' => 'valid.key',
            'translation_value' => '',
        ]);
    }

    public function testValidateTranslationDataRequiresLanguage()
    {
        $this->expectException(ServiceException::class);
        
        self::$service->createTranslation([
            'lang_code' => '',
            'translation_key' => 'valid.key',
            'translation_value' => 'Value',
        ]);
    }

    public function testValidateTranslationKeyFormat()
    {
        $this->expectException(ServiceException::class);
        
        self::$service->createTranslation([
            'lang_code' => self::$testLangCode,
            'translation_key' => 'invalid key with spaces',
            'translation_value' => 'Value',
        ]);
    }
}
