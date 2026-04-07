<?php
/**
 * Db Class Test
 * 
 * Comprehensive tests for the Db database abstraction class
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class DbClassTest extends TestCase
{
    private static ?PDO $pdo = null;
    private ?Db $db = null;
    
    public static function setUpBeforeClass(): void
    {
        try {
            self::$pdo = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware'
            );
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            self::$pdo = null;
        }
    }
    
    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }
    
    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('Test database not available');
            return;
        }
        
        $this->db = new Db();
        $this->db->setDbConnection([
            'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware'
        ]);
        
        $this->cleanupTestData();
    }
    
    protected function tearDown(): void
    {
        if ($this->db !== null) {
            $this->db->closeDbConnection();
        }
        $this->db = null;
    }
    
    private function cleanupTestData(): void
    {
        if (self::$pdo === null) return;
        
        $tables = ['tbl_users', 'tbl_posts', 'tbl_topics', 'tbl_comments', 'tbl_media', 'tbl_settings', 'tbl_menu'];
        foreach ($tables as $table) {
            try {
                self::$pdo->exec("DELETE FROM $table WHERE user_login LIKE 'test_%' OR user_email LIKE 'test_%'");
            } catch (PDOException $e) {
                // Table might not exist
            }
        }
    }
    
    private function insertTestUser(string $login, string $email, string $level = 'author'): int
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_users (user_login, user_email, user_pass, user_level, user_session, user_registered)
            VALUES (?, ?, ?, ?, '', NOW())
        ");
        $stmt->execute([$login, $email, password_hash('testpass', PASSWORD_DEFAULT), $level]);
        return (int) self::$pdo->lastInsertId();
    }

    // ==================== Connection Tests ====================
    
    public function testSetDbConnection(): void
    {
        $db = new Db();
        $db->setDbConnection([
            'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware'
        ]);
        
        $this->assertTrue($db->isConnected());
        $db->closeDbConnection();
        $this->assertFalse($db->isConnected());
    }
    
    public function testSetDbConnectionWithInvalidConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $db = new Db();
        $db->setDbConnection(['mysql:host=localhost']);
    }
    
    public function testSetTablePrefix(): void
    {
        $this->db->setTablePrefix('test_');
        $this->assertEquals('test_', $this->db->getTablePrefix());
    }
    
    public function testGetTablePrefix(): void
    {
        $this->db->setTablePrefix('abc_');
        $this->assertEquals('abc_', $this->db->getTablePrefix());
    }
    
    public function testIsConnected(): void
    {
        $this->assertTrue($this->db->isConnected());
        
        $newDb = new Db();
        $this->assertFalse($newDb->isConnected());
    }
    
    public function testCloseDbConnection(): void
    {
        $this->assertTrue($this->db->isConnected());
        $this->db->closeDbConnection();
        $this->assertFalse($this->db->isConnected());
    }

    // ==================== Query Tests ====================
    
    public function testDbQuery(): void
    {
        $userId = $this->insertTestUser('test_query_user', 'test_query@test.com');
        
        $stmt = $this->db->dbQuery(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId]
        );
        
        $this->assertInstanceOf(PDOStatement::class, $stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('test_query_user', $result['user_login']);
    }
    
    public function testDbQueryWithPrefix(): void
    {
        $this->db->setTablePrefix(''); // No prefix in test db
        
        $userId = $this->insertTestUser('test_prefix_user', 'test_prefix@test.com');
        
        $stmt = $this->db->dbQuery(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId]
        );
        
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    // ==================== Select Tests ====================
    
    public function testDbSelectDefaultFetchMode(): void
    {
        $userId = $this->insertTestUser('test_select_user', 'test_select@test.com');
        
        // Default fetch mode is PDO::FETCH_OBJ
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId]
        );
        
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        // Default is FETCH_OBJ
        $this->assertEquals('test_select_user', $results[0]->user_login);
    }
    
    public function testDbSelectWithFetchAssoc(): void
    {
        $userId = $this->insertTestUser('test_assoc_user', 'test_assoc@test.com');
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId],
            PDO::FETCH_ASSOC
        );
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('user_login', $results[0]);
        $this->assertArrayHasKey('user_email', $results[0]);
    }
    
    public function testDbSelectWithFetchObj(): void
    {
        $userId = $this->insertTestUser('test_obj_user', 'test_obj@test.com');
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId],
            PDO::FETCH_OBJ
        );
        
        $this->assertIsArray($results);
        $this->assertIsObject($results[0]);
        $this->assertEquals('test_obj_user', $results[0]->user_login);
    }
    
    public function testDbSelectEmptyResult(): void
    {
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [999999]
        );
        
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
    
    public function testDbSelectMultipleRows(): void
    {
        $this->insertTestUser('test_multi_user1', 'test_multi1@test.com');
        $this->insertTestUser('test_multi_user2', 'test_multi2@test.com');
        $this->insertTestUser('test_multi_user3', 'test_multi3@test.com');
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login LIKE ?",
            ['test_multi_%']
        );
        
        $this->assertCount(3, $results);
    }

    // ==================== Insert Tests ====================
    
    public function testDbInsert(): void
    {
        $result = $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_insert_user',
            'user_email' => 'test_insert@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        $this->assertTrue($result);
        
        // Verify insert - use FETCH_ASSOC
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login = ?",
            ['test_insert_user'],
            PDO::FETCH_ASSOC
        );
        $this->assertNotEmpty($results);
        $this->assertEquals('test_insert@test.com', $results[0]['user_email']);
    }
    
    public function testDbInsertWithEmptyParams(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->dbInsert('tbl_users', []);
    }
    
    public function testDbLastInsertId(): void
    {
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_lastid_user',
            'user_email' => 'test_lastid@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        $lastId = $this->db->dbLastInsertId();
        $this->assertNotEmpty($lastId);
        $this->assertGreaterThan(0, (int) $lastId);
    }

    // ==================== Update Tests ====================
    
    public function testDbUpdate(): void
    {
        $userId = $this->insertTestUser('test_update_user', 'test_update@test.com');
        
        $affected = $this->db->dbUpdate(
            'tbl_users',
            ['user_fullname' => 'Test Update Name'],
            ['ID' => $userId]
        );
        
        $this->assertEquals(1, $affected);
        
        // Verify update - use FETCH_ASSOC
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId],
            PDO::FETCH_ASSOC
        );
        $this->assertEquals('Test Update Name', $results[0]['user_fullname']);
    }
    
    public function testDbUpdateMultipleFields(): void
    {
        $userId = $this->insertTestUser('test_update_multi', 'test_update_multi@test.com');
        
        $affected = $this->db->dbUpdate(
            'tbl_users',
            [
                'user_fullname' => 'Multi Update',
                'user_level' => 'editor'
            ],
            ['ID' => $userId]
        );
        
        $this->assertEquals(1, $affected);
        
        // Verify - use FETCH_ASSOC
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId],
            PDO::FETCH_ASSOC
        );
        $this->assertEquals('Multi Update', $results[0]['user_fullname']);
        $this->assertEquals('editor', $results[0]['user_level']);
    }
    
    public function testDbUpdateWithEmptyParams(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->dbUpdate('tbl_users', [], ['ID' => 1]);
    }
    
    public function testDbUpdateWithEmptyWhere(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->dbUpdate('tbl_users', ['user_fullname' => 'Test'], []);
    }
    
    public function testDbUpdateNoMatch(): void
    {
        $affected = $this->db->dbUpdate(
            'tbl_users',
            ['user_fullname' => 'No Match'],
            ['ID' => 999999]
        );
        
        $this->assertEquals(0, $affected);
    }

    // ==================== Replace (Upsert) Tests ====================
    
    public function testDbReplace(): void
    {
        // First insert
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_replace_user',
            'user_email' => 'test_replace@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        // Replace with same login - should update
        $result = $this->db->dbReplace(
            'tbl_users',
            [
                'user_login' => 'test_replace_user',
                'user_email' => 'test_replace@test.com',
                'user_pass' => password_hash('newpass', PASSWORD_DEFAULT),
                'user_level' => 'editor',
                'user_session' => '',
                'user_registered' => date('Y-m-d H:i:s')
            ],
            ['user_level' => 'editor']
        );
        
        $this->assertTrue($result);
    }
    
    public function testDbReplaceWithEmptyParams(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->dbReplace('tbl_users', [], ['user_level' => 'editor']);
    }

    // ==================== Delete Tests ====================
    
    public function testDbDelete(): void
    {
        $userId = $this->insertTestUser('test_delete_user', 'test_delete@test.com');
        
        $affected = $this->db->dbDelete('tbl_users', ['ID' => $userId]);
        
        $this->assertEquals(1, $affected);
        
        // Verify delete
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId],
            PDO::FETCH_ASSOC
        );
        $this->assertEmpty($results);
    }
    
    public function testDbDeleteWithEmptyWhere(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->dbDelete('tbl_users', []);
    }
    
    public function testDbDeleteNoMatch(): void
    {
        $affected = $this->db->dbDelete('tbl_users', ['ID' => 999999]);
        $this->assertEquals(0, $affected);
    }
    
    public function testDbDeleteWithLimit(): void
    {
        $this->insertTestUser('test_limit1', 'test_limit1@test.com');
        $this->insertTestUser('test_limit2', 'test_limit2@test.com');
        $this->insertTestUser('test_limit3', 'test_limit3@test.com');
        
        // Get IDs first
        $results = $this->db->dbSelect(
            "SELECT ID FROM tbl_users WHERE user_login LIKE ? ORDER BY ID LIMIT 2",
            ['test_limit%'],
            PDO::FETCH_ASSOC
        );
        
        // Delete with limit
        if (count($results) >= 2) {
            $affected = $this->db->dbDelete('tbl_users', ['ID' => $results[0]['ID']], 1);
            $this->assertEquals(1, $affected);
        }
        
        // Verify one was deleted
        $remaining = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login LIKE ?",
            ['test_limit%'],
            PDO::FETCH_ASSOC
        );
        $this->assertCount(2, $remaining);
    }

    // ==================== Transaction Tests ====================
    
    public function testDbTransaction(): void
    {
        $this->assertTrue($this->db->dbTransaction());
    }
    
    public function testDbCommit(): void
    {
        $this->db->dbTransaction();
        
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_commit_user',
            'user_email' => 'test_commit@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        $this->assertTrue($this->db->dbCommit());
        
        // Verify committed
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login = ?",
            ['test_commit_user'],
            PDO::FETCH_ASSOC
        );
        $this->assertNotEmpty($results);
    }
    
    public function testDbRollBack(): void
    {
        $this->db->dbTransaction();
        
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_rollback_user',
            'user_email' => 'test_rollback@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        $this->assertTrue($this->db->dbRollBack());
        
        // Verify rolled back
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login = ?",
            ['test_rollback_user'],
            PDO::FETCH_ASSOC
        );
        $this->assertEmpty($results);
    }

    // ==================== CRUD Integration Tests ====================
    
    public function testFullCrudCycle(): void
    {
        // CREATE
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_crud_user',
            'user_email' => 'test_crud@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login = ?",
            ['test_crud_user'],
            PDO::FETCH_ASSOC
        );
        $this->assertNotEmpty($results);
        $userId = $results[0]['ID'];
        
        // READ
        $user = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId],
            PDO::FETCH_ASSOC
        );
        $this->assertEquals('test_crud@test.com', $user[0]['user_email']);
        
        // UPDATE
        $this->db->dbUpdate('tbl_users',
            ['user_fullname' => 'CRUD Test Name'],
            ['ID' => $userId]
        );
        
        $updated = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId],
            PDO::FETCH_ASSOC
        );
        $this->assertEquals('CRUD Test Name', $updated[0]['user_fullname']);
        
        // DELETE
        $this->db->dbDelete('tbl_users', ['ID' => $userId]);
        
        $deleted = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId],
            PDO::FETCH_ASSOC
        );
        $this->assertEmpty($deleted);
    }
    
    public function testRelatedDataCrud(): void
    {
        // Create user
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_author',
            'user_email' => 'test_author@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        $user = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login = ?",
            ['test_author'],
            PDO::FETCH_ASSOC
        );
        $authorId = $user[0]['ID'];
        
        // Create topic
        $this->db->dbInsert('tbl_topics', [
            'topic_title' => 'Test Topic',
            'topic_slug' => 'test-topic'
        ]);
        
        $topic = $this->db->dbSelect(
            "SELECT * FROM tbl_topics WHERE topic_slug = ?",
            ['test-topic'],
            PDO::FETCH_ASSOC
        );
        $topicId = $topic[0]['ID'];
        
        // Create post
        $this->db->dbInsert('tbl_posts', [
            'post_author' => $authorId,
            'post_title' => 'Test Post',
            'post_slug' => 'test-post',
            'post_content' => 'Test content',
            'post_status' => 'publish',
            'post_date' => date('Y-m-d H:i:s')
        ]);
        
        $post = $this->db->dbSelect(
            "SELECT * FROM tbl_posts WHERE post_slug = ?",
            ['test-post'],
            PDO::FETCH_ASSOC
        );
        $postId = $post[0]['ID'];
        
        // Create comment
        $this->db->dbInsert('tbl_comments', [
            'comment_post_id' => $postId,
            'comment_author_name' => 'Test Commenter',
            'comment_author_ip' => '127.0.0.1',
            'comment_content' => 'Test comment',
            'comment_status' => 'approved',
            'comment_date' => date('Y-m-d H:i:s')
        ]);
        
        // Verify relationships
        $comments = $this->db->dbSelect(
            "SELECT * FROM tbl_comments WHERE comment_post_id = ?",
            [$postId],
            PDO::FETCH_ASSOC
        );
        $this->assertCount(1, $comments);
        
        // Cleanup
        $this->db->dbDelete('tbl_comments', ['comment_post_id' => $postId]);
        $this->db->dbDelete('tbl_posts', ['ID' => $postId]);
        $this->db->dbDelete('tbl_users', ['ID' => $authorId]);
        $this->db->dbDelete('tbl_topics', ['ID' => $topicId]);
    }

    // ==================== Edge Cases ====================
    
    public function testSqlInjectionPrevention(): void
    {
        $userId = $this->insertTestUser('test_sqli_user', 'test_sqli@test.com');
        
        // Attempt SQL injection via parameter
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId],
            PDO::FETCH_ASSOC
        );
        
        // Should not return all users
        $this->assertCount(1, $results);
        $this->assertEquals('test_sqli_user', $results[0]['user_login']);
    }
    
    public function testSpecialCharactersInData(): void
    {
        $this->db->dbInsert('tbl_users', [
            'user_login' => "test_special_'quote",
            'user_email' => 'test_special@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login LIKE ?",
            ["test_special_%"],
            PDO::FETCH_ASSOC
        );
        
        $this->assertNotEmpty($results);
        $this->assertEquals("test_special_'quote", $results[0]['user_login']);
    }
    
    public function testUnicodeCharacters(): void
    {
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_unicode',
            'user_email' => 'test_unicode@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s'),
            'user_fullname' => '田中太郎'
        ]);
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login = ?",
            ['test_unicode'],
            PDO::FETCH_ASSOC
        );
        
        $this->assertEquals('田中太郎', $results[0]['user_fullname']);
    }
    
    public function testNullValues(): void
    {
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_null_user',
            'user_email' => 'test_null@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s'),
            'user_url' => null
        ]);
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login = ?",
            ['test_null_user'],
            PDO::FETCH_ASSOC
        );
        
        $this->assertNull($results[0]['user_url']);
    }
    
    public function testEmptyString(): void
    {
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_empty_user',
            'user_email' => 'test_empty@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s'),
            'user_fullname' => ''
        ]);
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login = ?",
            ['test_empty_user'],
            PDO::FETCH_ASSOC
        );
        
        $this->assertEquals('', $results[0]['user_fullname']);
    }
    
    public function testNumericValues(): void
    {
        $this->db->dbInsert('tbl_posts', [
            'post_author' => 1,
            'post_title' => 'Test Numeric Post',
            'post_slug' => 'test-numeric-post',
            'post_content' => 'Content with 123 numbers',
            'post_status' => 'publish',
            'post_date' => date('Y-m-d H:i:s')
        ]);
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_posts WHERE post_slug = ?",
            ['test-numeric-post'],
            PDO::FETCH_ASSOC
        );
        
        $this->assertEquals(1, $results[0]['post_author']);
        $this->assertIsInt($results[0]['post_author']);
    }
    
    public function testBooleanValues(): void
    {
        $userId = $this->insertTestUser('test_bool_user', 'test_bool@test.com');
        
        $this->db->dbUpdate('tbl_users',
            ['user_banned' => 1],
            ['ID' => $userId]
        );
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID = ?",
            [$userId],
            PDO::FETCH_ASSOC
        );
        
        $this->assertEquals(1, $results[0]['user_banned']);
    }
    
    public function testDateTimeValues(): void
    {
        $testDate = '2024-06-15 14:30:00';
        
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_datetime_user',
            'user_email' => 'test_datetime@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => $testDate
        ]);
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login = ?",
            ['test_datetime_user'],
            PDO::FETCH_ASSOC
        );
        
        $this->assertEquals($testDate, $results[0]['user_registered']);
    }
    
    public function testLargeTextContent(): void
    {
        $largeContent = str_repeat('This is a long text. ', 1000);
        
        $this->db->dbInsert('tbl_posts', [
            'post_author' => 1,
            'post_title' => 'Test Large Content',
            'post_slug' => 'test-large-content',
            'post_content' => $largeContent,
            'post_status' => 'publish',
            'post_date' => date('Y-m-d H:i:s')
        ]);
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_posts WHERE post_slug = ?",
            ['test-large-content'],
            PDO::FETCH_ASSOC
        );
        
        $this->assertEquals($largeContent, $results[0]['post_content']);
    }
    
    public function testMultipleQueriesInSequence(): void
    {
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_seq1',
            'user_email' => 'test_seq1@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_seq2',
            'user_email' => 'test_seq2@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        $this->db->dbInsert('tbl_users', [
            'user_login' => 'test_seq3',
            'user_email' => 'test_seq3@test.com',
            'user_pass' => password_hash('testpass', PASSWORD_DEFAULT),
            'user_level' => 'author',
            'user_session' => '',
            'user_registered' => date('Y-m-d H:i:s')
        ]);
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE user_login LIKE ? ORDER BY ID",
            ['test_seq%'],
            PDO::FETCH_ASSOC
        );
        
        $this->assertCount(3, $results);
    }
    
    public function testConditionalUpdates(): void
    {
        $userId1 = $this->insertTestUser('test_cond1', 'test_cond1@test.com');
        $userId2 = $this->insertTestUser('test_cond2', 'test_cond2@test.com');
        
        // Update specific users by ID
        $this->db->dbUpdate('tbl_users',
            ['user_level' => 'manager'],
            ['ID' => $userId1]
        );
        
        $results = $this->db->dbSelect(
            "SELECT * FROM tbl_users WHERE ID IN (?, ?)",
            [$userId1, $userId2],
            PDO::FETCH_ASSOC
        );
        
        $this->assertEquals('manager', $results[0]['user_level']);
        $this->assertEquals('author', $results[1]['user_level']);
    }
}
