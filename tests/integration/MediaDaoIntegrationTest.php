<?php
/**
 * MediaDao Integration Test
 * 
 * Tests for MediaDao class including findMediaMetaValue edge case
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

class MediaDaoIntegrationTest extends TestCase
{
    private static $db;
    private static $dao;
    private static $testMediaIds = [];
    private static $testMetaIds = [];

    public static function setUpBeforeClass(): void
    {
        try {
            self::$db = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            require_once __DIR__ . '/../../lib/core/Dao.php';
            require_once __DIR__ . '/../../lib/dao/MediaDao.php';
            
            self::$dao = new MediaDao();
            self::cleanupTestData();
            
        } catch (PDOException $e) {
            self::markTestSkipped('Cannot connect to test database: ' . $e->getMessage());
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupTestData();
        if (self::$db) {
            self::$db = null;
        }
    }

    private static function cleanupTestData()
    {
        if (!self::$db) return;
        
        // Clean up test media and metadata using actual IDs
        foreach (self::$testMetaIds as $metaId) {
            self::$db->exec("DELETE FROM tbl_mediameta WHERE ID = " . (int)$metaId);
        }
        foreach (self::$testMediaIds as $mediaId) {
            self::$db->exec("DELETE FROM tbl_media WHERE ID = " . (int)$mediaId);
        }
        self::$testMediaIds = [];
        self::$testMetaIds = [];
    }

    private function createTestMedia($filename)
    {
        $stmt = self::$db->prepare("
            INSERT INTO tbl_media (media_filename, media_caption, media_type, media_target, media_user, media_access, media_status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $filename,
            'Test Media',
            'image/jpeg',
            'blog',
            'admin',
            'public',
            1
        ]);
        $mediaId = (int)self::$db->lastInsertId();
        self::$testMediaIds[] = $mediaId;
        return $mediaId;
    }

    private function createTestMediaMeta($mediaId, $metaKey, $metaValue)
    {
        $stmt = self::$db->prepare("
            INSERT INTO tbl_mediameta (media_id, meta_key, meta_value)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$mediaId, $metaKey, $metaValue]);
        $metaId = (int)self::$db->lastInsertId();
        self::$testMetaIds[] = $metaId;
        return $metaId;
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!self::$dao) {
            $this->markTestSkipped('MediaDao not available');
        }
    }

    /**
     * Test: Media with metadata can be retrieved
     * This is the happy path - media record exists AND has metadata
     */
    public function testFindMediaMetaValueWithExistingMetadata()
    {
        $filename = 'test_image_' . time() . '.jpg';
        $mediaId = $this->createTestMedia($filename);
        
        $this->createTestMediaMeta($mediaId, $filename, json_encode(['Origin' => 'test.jpg', 'File type' => 'image/jpeg', 'File size' => '100 KB']));
        
        $result = self::$dao->findMediaMetaValue($mediaId, $filename, new Sanitize());
        
        $this->assertIsArray($result, 'Result should be an array when metadata exists');
        $this->assertArrayHasKey('ID', $result, 'Result should have ID key');
        $this->assertArrayHasKey('media_id', $result, 'Result should have media_id key');
        $this->assertArrayHasKey('meta_key', $result, 'Result should have meta_key key');
        $this->assertArrayHasKey('meta_value', $result, 'Result should have meta_value key');
    }

    /**
     * Test: Media WITHOUT metadata should return null
     * This is the edge case that caused the bug at line 320 in MediaController.php
     */
    public function testFindMediaMetaValueWithNoMetadata()
    {
        $filename = 'test_no_metadata_' . time() . '.jpg';
        $mediaId = $this->createTestMedia($filename);
        
        // Verify NO metadata exists for this media
        $checkMeta = self::$db->prepare("SELECT COUNT(*) as cnt FROM tbl_mediameta WHERE media_id = ?");
        $checkMeta->execute([$mediaId]);
        $metaCount = $checkMeta->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals(0, $metaCount['cnt'], 'Verify no metadata exists before testing');
        
        // Test DAO method - should return null, NOT boolean
        $result = self::$dao->findMediaMetaValue($mediaId, $filename, new Sanitize());
        
        // Critical assertion: must return null, not boolean true/false
        $this->assertNull($result, 'Result should be null when no metadata exists');
        $this->assertNotTrue(is_bool($result) && $result === true, 'Result must NOT be boolean true');
    }

    /**
     * Test: Media with non-matching filename should return null
     */
    public function testFindMediaMetaValueWithWrongFilename()
    {
        $mediaId = $this->createTestMedia('actual_file.jpg');
        
        // Insert metadata with ACTUAL filename
        $this->createTestMediaMeta($mediaId, 'actual_file.jpg', json_encode(['Origin' => 'actual.jpg']));
        
        // Test DAO with DIFFERENT filename - should return null
        $result = self::$dao->findMediaMetaValue($mediaId, 'wrong_filename.jpg', new Sanitize());
        
        $this->assertNull($result, 'Result should be null when filename does not match');
    }

    /**
     * Test: Non-existent media ID should return null
     */
    public function testFindMediaMetaValueWithNonExistentId()
    {
        $result = self::$dao->findMediaMetaValue(999999999, 'nonexistent.jpg', new Sanitize());
        
        $this->assertNull($result, 'Result should be null for non-existent media');
    }

    /**
     * Test: Media with multiple metadata entries returns first match
     */
    public function testFindMediaMetaValueWithMultipleMetadata()
    {
        $mediaId = $this->createTestMedia('multi_file.jpg');
        
        // Insert multiple metadata records with same filename
        $this->createTestMediaMeta($mediaId, 'multi_file.jpg', json_encode(['Type' => 'First']));
        $this->createTestMediaMeta($mediaId, 'multi_file.jpg', json_encode(['Type' => 'Second']));
        
        // Test - should return first match
        $result = self::$dao->findMediaMetaValue($mediaId, 'multi_file.jpg', new Sanitize());
        
        $this->assertIsArray($result, 'Should return array when multiple matches exist');
    }

    /**
     * Test: findMediaMetaValue with empty filename
     */
    public function testFindMediaMetaValueWithEmptyFilename()
    {
        $mediaId = $this->createTestMedia('empty_file.jpg');
        
        // Should return null for empty filename
        $result = self::$dao->findMediaMetaValue($mediaId, '', new Sanitize());
        
        $this->assertNull($result, 'Result should be null for empty filename');
    }

    /**
     * Test: findMediaMetaValue returns valid array structure for MediaController consumption
     * This simulates the exact usage at MediaController.php line 318-325
     */
    public function testMediaControllerUsageScenario()
    {
        $mediaId = $this->createTestMedia('controller_test.jpg');
        
        $this->createTestMediaMeta($mediaId, 'controller_test.jpg', json_encode(['Origin' => 'test.jpg', 'Dimension' => '800x600']));
        
        // Simulate MediaController usage (lines 316-325)
        $getMediaMeta = self::$dao->findMediaMetaValue($mediaId, 'controller_test.jpg', new Sanitize());
        
        // This should NOT throw "Trying to access array offset on true" error
        $media_properties = [
            'ID' => $getMediaMeta['ID'],
            'media_id' => $getMediaMeta['media_id'],
            'meta_key' => $getMediaMeta['meta_key'],
            'meta_value' => $getMediaMeta['meta_value']
        ];
        
        $this->assertArrayHasKey('ID', $media_properties);
        $this->assertArrayHasKey('media_id', $media_properties);
        $this->assertArrayHasKey('meta_key', $media_properties);
        $this->assertArrayHasKey('meta_value', $media_properties);
    }

    /**
     * Test: MediaController failure scenario (no metadata)
     * This should return null and NOT cause array offset error
     */
    public function testMediaControllerFailureScenario()
    {
        $mediaId = $this->createTestMedia('controller_fail.jpg');
        
        // Simulate MediaController call
        $getMediaMeta = self::$dao->findMediaMetaValue($mediaId, 'controller_fail.jpg', new Sanitize());
        
        // This should be null (the fix), not boolean
        if ($getMediaMeta === null) {
            // This is the expected behavior after fix
            $this->assertTrue(true, 'Correctly returns null for missing metadata');
        } else {
            // If not null, it should be an array (metadata exists)
            $this->assertIsArray($getMediaMeta, 'If not null, must be array');
        }
        
        // The critical test: should NOT cause "array offset on true" error
        if (is_array($getMediaMeta)) {
            $id = $getMediaMeta['ID']; // Should work
            $this->assertIsInt($id);
        }
    }
}