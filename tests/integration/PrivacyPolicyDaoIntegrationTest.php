<?php
/**
 * PrivacyPolicyDao Integration Test
 * 
 * Tests for the PrivacyPolicyDao class with actual database
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

class PrivacyPolicyDaoIntegrationTest extends TestCase
{
    private static $db;
    private static $dao;
    private static $testPolicyIds = [];

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
            require_once __DIR__ . '/../../lib/dao/PrivacyPolicyDao.php';
            
            // Initialize DAO
            self::$dao = new PrivacyPolicyDao();
            
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
        
        // Delete test policies (not the default 'en')
        $stmt = self::$db->prepare("DELETE FROM tbl_privacy_policies WHERE locale LIKE 'test_%'");
        $stmt->execute();
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!self::$dao) {
            $this->markTestSkipped('PrivacyPolicyDao not available');
        }
    }

    public function testCreatePolicy()
    {
        $locale = 'test_' . time();
        
        $id = self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'Test Policy Title',
            'policy_content' => 'Test policy content',
            'is_default' => 0,
        ]);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        self::$testPolicyIds[] = $id;
    }

    public function testFindById()
    {
        $locale = 'test_find_' . time();
        
        $id = self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'Find Test Policy',
            'policy_content' => 'Find test content',
        ]);
        
        self::$testPolicyIds[] = $id;
        
        $policy = self::$dao->findById($id);
        
        $this->assertIsArray($policy);
        $this->assertEquals($locale, $policy['locale']);
        $this->assertEquals('Find Test Policy', $policy['policy_title']);
    }

    public function testFindByIdReturnsNullForNonexistent()
    {
        $policy = self::$dao->findById(999999);
        
        $this->assertNull($policy);
    }

    public function testFindByLocale()
    {
        $locale = 'test_locale_' . time();
        
        self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'Locale Test',
            'policy_content' => 'Locale test content',
        ]);
        
        $policy = self::$dao->findByLocale($locale);
        
        $this->assertIsArray($policy);
        $this->assertEquals($locale, $policy['locale']);
    }

    public function testFindByLocaleReturnsNullForNonexistent()
    {
        $policy = self::$dao->findByLocale('nonexistent_locale');
        
        $this->assertNull($policy);
    }

    public function testFindDefault()
    {
        // First create a default policy
        $locale = 'test_default_' . time();
        
        $id = self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'Default Policy',
            'policy_content' => 'Default content',
            'is_default' => 1,
        ]);
        
        self::$testPolicyIds[] = $id;
        
        $default = self::$dao->findDefault();
        
        $this->assertIsArray($default);
        $this->assertEquals(1, $default['is_default']);
    }

    public function testFindAllPolicies()
    {
        $policies = self::$dao->findAllPolicies();
        
        $this->assertIsArray($policies);
    }

    public function testUpdatePolicy()
    {
        $locale = 'test_update_' . time();
        
        $id = self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'Original Title',
            'policy_content' => 'Original content',
        ]);
        
        self::$testPolicyIds[] = $id;
        
        self::$dao->updatePolicy($id, [
            'policy_title' => 'Updated Title',
            'policy_content' => 'Updated content',
        ]);
        
        $updated = self::$dao->findById($id);
        
        $this->assertEquals('Updated Title', $updated['policy_title']);
        $this->assertEquals('Updated content', $updated['policy_content']);
    }

    public function testDeletePolicy()
    {
        $locale = 'test_delete_' . time();
        
        $id = self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'Delete Test',
            'policy_content' => 'Delete content',
        ]);
        
        self::$dao->deletePolicy($id);
        
        $deleted = self::$dao->findById($id);
        
        $this->assertNull($deleted);
    }

    public function testSetDefaultPolicy()
    {
        // Create first policy as default
        $locale1 = 'test_default1_' . time();
        $id1 = self::$dao->createPolicy([
            'locale' => $locale1,
            'policy_title' => 'First Default',
            'policy_content' => 'First content',
            'is_default' => 1,
        ]);
        
        // Create second policy
        $locale2 = 'test_default2_' . time();
        $id2 = self::$dao->createPolicy([
            'locale' => $locale2,
            'policy_title' => 'Second Default',
            'policy_content' => 'Second content',
            'is_default' => 0,
        ]);
        
        self::$testPolicyIds[] = $id1;
        self::$testPolicyIds[] = $id2;
        
        // Set second as default
        self::$dao->setDefaultPolicy($id2);
        
        $policy1 = self::$dao->findById($id1);
        $policy2 = self::$dao->findById($id2);
        
        $this->assertEquals(0, $policy1['is_default']);
        $this->assertEquals(1, $policy2['is_default']);
    }

    public function testClearDefaultPolicy()
    {
        // Create a default policy
        $locale = 'test_clear_' . time();
        $id = self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'Clear Test',
            'policy_content' => 'Clear content',
            'is_default' => 1,
        ]);
        
        self::$testPolicyIds[] = $id;
        
        // Clear default
        self::$dao->clearDefaultPolicy();
        
        $policy = self::$dao->findById($id);
        
        $this->assertEquals(0, $policy['is_default']);
    }

    public function testFetchAll()
    {
        $all = self::$dao->fetchAll();
        
        $this->assertIsArray($all);
    }

    public function testSetAsDefaultPolicy()
    {
        $locale = 'test_asdefault_' . time();
        
        $id = self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'As Default Test',
            'policy_content' => 'As default content',
            'is_default' => 0,
        ]);
        
        self::$testPolicyIds[] = $id;
        
        self::$dao->setAsDefaultPolicy($id);
        
        $policy = self::$dao->findById($id);
        
        $this->assertEquals(1, $policy['is_default']);
    }

    public function testPolicyExists()
    {
        $locale = 'test_exists_' . time();
        
        self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'Exists Test',
            'policy_content' => 'Exists content',
        ]);
        
        $exists = self::$dao->policyExists($locale);
        
        $this->assertTrue($exists);
        
        $notExists = self::$dao->policyExists('nonexistent_locale_' . time());
        
        $this->assertFalse($notExists);
    }

    public function testCreatePolicyWithMinimalData()
    {
        $locale = 'test_minimal_' . time();
        
        $id = self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'Minimal Test',
            'policy_content' => 'Minimal content',
        ]);
        
        self::$testPolicyIds[] = $id;
        
        $this->assertIsInt($id);
        
        $policy = self::$dao->findById($id);
        
        $this->assertEquals(0, $policy['is_default']); // Default
    }

    public function testCreateDuplicateLocaleThrowsException()
    {
        $locale = 'test_duplicate_' . time();
        
        self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'First Policy',
            'policy_content' => 'First content',
        ]);
        
        // Second create will fail due to UNIQUE constraint on locale
        // The DAO method doesn't enforce this, so it will throw at DB level
        $this->expectException(PDOException::class);
        
        self::$dao->createPolicy([
            'locale' => $locale,
            'policy_title' => 'Second Policy',
            'policy_content' => 'Second content',
        ]);
    }
}