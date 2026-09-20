<?php
/**
 * API Test Data Fixture
 *
 * Seeds the blogware_test database with API-specific test data and
 * provides a truncate helper so API integration tests run deterministically
 * regardless of pre-existing rows.
 *
 * Seeded data:
 *   - 1 administrator user (login "administrator")
 *   - 1 live API key for that user (ApiAuth::generateApiKey)
 *   - 4 posts across 2 locales (2 published blog, 1 draft blog, 1 published page)
 *   - 3 topics plus post<->topic links
 *   - 6 languages (en, ar, zh, fr, es, id)
 *   - 10 translations for the "en" locale
 *   - 3 comments on post #1 (approved, pending, spam)
 *   - 1 media entry
 *
 * @package Scriptlog\Tests
 */

class ApiDataFixture
{
    /**
     * @var \PDO|null Shared PDO connection to the test database.
     */
    private static $pdo = null;

    /**
     * @var bool|null Whether the test database is reachable.
     */
    private static $available = null;

    /**
     * @var int|null Administrator user ID.
     */
    private static $adminId = null;

    /**
     * @var string|null Plaintext API key generated for the admin user.
     */
    private static $apiKey = null;

    /**
     * @var array<int, int> IDs of the seeded posts.
     */
    private static $postIds = [];

    /**
     * @var array<int, int> IDs of the seeded topics.
     */
    private static $topicIds = [];

    /**
     * @var array<int, int> IDs of the seeded comments.
     */
    private static $commentIds = [];

    /**
     * @var array<int, int> IDs of the seeded translations.
     */
    private static $translationIds = [];

    /**
     * @var \Scriptlog\Core\Db|null Cached Db wrapper for Registry wiring.
     */
    private static $dbWrapper = null;

    /**
     * Connect to the test database and wire up the application Registry.
     *
     * Sets Registry 'dbc'/'db' to a Db wrapper so DAOs (which call
     * dbQuery()) can execute queries, and flags the test table prefix so
     * get_table_prefix() returns an empty prefix.
     *
     * The Registry is re-wired on every call (even when the PDO connection
     * is already cached) so tests that clear the Registry (e.g. ApiHelper
     * tests calling Registry::setAll([])) cannot silently break later tests
     * that expect Registry 'dbc' to be the test Db wrapper.
     *
     * @return \PDO|null PDO connection or null when the database is unavailable
     */
    public static function connect()
    {
        if (self::$pdo !== null) {
            if (self::$dbWrapper !== null && \Scriptlog\Core\Registry::get('dbc') === null) {
                \Scriptlog\Core\Registry::set('dbc', self::$dbWrapper);
                \Scriptlog\Core\Registry::set('db', self::$dbWrapper);
            }
            return self::$pdo;
        }

        if (!function_exists('get_test_config')) {
            require_once __DIR__ . '/../../bootstrap_integration.php';
        }

        $config = get_test_config();

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['db']['host'],
            $config['db']['port'],
            $config['db']['name']
        );

        try {
            $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $db = new \Scriptlog\Core\Db([$dsn, $config['db']['user'], $config['db']['pass']]);
            self::$dbWrapper = $db;

            \Scriptlog\Core\Registry::set('dbc', $db);
            \Scriptlog\Core\Registry::set('db', $db);
            $GLOBALS['__test_prefix'] = true;

            self::$pdo = $pdo;
            self::$available = true;
        } catch (\PDOException $e) {
            self::$pdo = null;
            self::$available = false;
        }

        return self::$pdo;
    }

    /**
     * Whether the test database is reachable.
     *
     * @return bool
     */
    public static function isAvailable()
    {
        self::connect();
        return self::$available === true;
    }

    /**
     * Toggle the permalink rewrite setting for the API tests.
     *
     * rewrite_status() reads app_info()['permalink_setting'], which is
     * resolved from the tbl_settings table when a Registry Db connection is
     * available. Upserting the row here lets tests exercise both the
     * permalink-enabled and the ?p=/?cat=/?pg= fallback branches of the
     * API DTOs without hitting the production database.
     *
     * @param string $status "yes" to enable pretty permalinks, "no" otherwise
     * @return void
     */
    public static function setRewriteStatus($status)
    {
        $dbc = \Scriptlog\Core\Registry::get('dbc');

        if ($status === 'yes') {
            $dbc->dbQuery(
                'INSERT INTO tbl_settings (setting_name, setting_value)
                 VALUES ("permalink_setting", ?)
                 ON DUPLICATE KEY UPDATE setting_value = ?',
                [json_encode(['rewrite' => 'yes']), json_encode(['rewrite' => 'yes'])]
            );

            // app_settings()/app_info() are memoized per PHP process; forget the
            // cached map so the next read reflects the upserted row.
            if (function_exists('reset_app_settings_cache')) {
                reset_app_settings_cache();
            }

            return;
        }

        $dbc->dbQuery('DELETE FROM tbl_settings WHERE setting_name = "permalink_setting"');

        if (function_exists('reset_app_settings_cache')) {
            reset_app_settings_cache();
        }
    }

    /**
     * Delete all API-related content rows (idempotent, safe to re-run).
     *
     * tbl_users is preserved so the setup_test_db administrator remains.
     *
     * @return void
     */
    public static function truncateApiTables()
    {
        $pdo = self::connect();
        if (!$pdo) {
            return;
        }

        $tables = [
            'tbl_post_topic',
            'tbl_comments',
            'tbl_translations',
            'tbl_languages',
            'tbl_api_keys',
            'tbl_mediameta',
            'tbl_media',
            'tbl_posts',
            'tbl_topics',
            'tbl_consents',
        ];

        foreach ($tables as $table) {
            $pdo->exec('DELETE FROM `' . $table . '`');
        }

        self::$adminId = null;
        self::$apiKey = null;
        self::$postIds = [];
        self::$topicIds = [];
    }

    /**
     * Seed the API test data.
     *
     * Idempotent: truncateApiTables() preserves tbl_users, so the
     * administrator row is removed and re-created here to keep the seeded
     * admin ID and API key deterministic across repeated seed() calls.
     *
     * @return array{admin_id: int|null, api_key: string|null, post_ids: array, topic_ids: array} Seeded identifiers
     */
    public static function seed()
    {
        self::connect();
        if (self::$pdo === null) {
            return [
                'admin_id' => null,
                'api_key' => null,
                'post_ids' => [],
                'topic_ids' => [],
            ];
        }

        self::truncateApiTables();

        $pdo = self::$pdo;

        // --- Administrator user -------------------------------------------------
        $pdo->exec("DELETE FROM tbl_users WHERE user_login = 'administrator'");
        $stmt = $pdo->prepare(
            'INSERT INTO tbl_users
             (user_login, user_email, user_pass, user_level, user_fullname, user_session, user_registered)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            'administrator',
            'api-admin@test.com',
            password_hash('Admin123!', PASSWORD_BCRYPT),
            'administrator',
            'API Administrator',
            '',
        ]);
        self::$adminId = (int)$pdo->lastInsertId();

        // --- API key ------------------------------------------------------------
        self::$apiKey = \Scriptlog\Core\ApiAuth::generateApiKey(self::$adminId, 'phpunit');

        // --- Posts --------------------------------------------------------------
        $postStmt = $pdo->prepare(
            'INSERT INTO tbl_posts
             (post_author, post_date, post_modified, post_title, post_slug, post_content, post_summary,
              post_status, post_visibility, post_tags, post_type, comment_status, post_locale)
             VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $posts = [
            [
                'title' => 'API Published Post One',
                'slug' => 'api-published-post-one',
                'content' => 'Content of the first published API post.',
                'summary' => 'Summary of API post one',
                'status' => 'publish',
                'visibility' => 'public',
                'tags' => 'php,api',
                'type' => 'blog',
                'comment' => 'open',
                'locale' => 'en',
            ],
            [
                'title' => 'API Published Post Two',
                'slug' => 'api-published-post-two',
                'content' => 'Content of the second published API post.',
                'summary' => '',
                'status' => 'publish',
                'visibility' => 'public',
                'tags' => '',
                'type' => 'blog',
                'comment' => 'open',
                'locale' => 'ar',
            ],
            [
                'title' => 'API Draft Post',
                'slug' => 'api-draft-post',
                'content' => 'Draft content that must not appear in the public list.',
                'summary' => '',
                'status' => 'draft',
                'visibility' => 'public',
                'tags' => '',
                'type' => 'blog',
                'comment' => 'closed',
                'locale' => 'en',
            ],
            [
                'title' => 'API Static Page',
                'slug' => 'api-static-page',
                'content' => 'Static page content rendered by the pages endpoint.',
                'summary' => '',
                'status' => 'publish',
                'visibility' => 'public',
                'tags' => '',
                'type' => 'page',
                'comment' => 'closed',
                'locale' => 'ar',
            ],
        ];

        self::$postIds = [];
        foreach ($posts as $post) {
            $postStmt->execute([
                self::$adminId,
                date('Y-m-d H:i:s'),
                $post['title'],
                $post['slug'],
                $post['content'],
                $post['summary'],
                $post['status'],
                $post['visibility'],
                $post['tags'],
                $post['type'],
                $post['comment'],
                $post['locale'],
            ]);
            self::$postIds[] = (int)$pdo->lastInsertId();
        }

        // --- Topics -------------------------------------------------------------
        $topicStmt = $pdo->prepare(
            'INSERT INTO tbl_topics (topic_title, topic_slug, topic_status, topic_locale) VALUES (?, ?, ?, ?)'
        );

        $topics = [
            ['API Technology', 'api-technology', 'Y', 'en'],
            ['API Lifestyle', 'api-lifestyle', 'Y', 'en'],
            ['API News', 'api-news', 'Y', 'en'],
        ];

        self::$topicIds = [];
        foreach ($topics as $topic) {
            $topicStmt->execute($topic);
            self::$topicIds[] = (int)$pdo->lastInsertId();
        }

        // Link post #1 to topics #1 and #2.
        $linkStmt = $pdo->prepare('INSERT INTO tbl_post_topic (post_id, topic_id) VALUES (?, ?)');
        $linkStmt->execute([self::$postIds[0], self::$topicIds[0]]);
        $linkStmt->execute([self::$postIds[0], self::$topicIds[1]]);

        // --- Languages ----------------------------------------------------------
        $langStmt = $pdo->prepare(
            'INSERT INTO tbl_languages
             (lang_code, lang_name, lang_native, lang_locale, lang_direction, lang_sort, lang_is_default, lang_is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $languages = [
            ['en', 'English', 'English', 'en_US', 'ltr', 1, 1, 1],
            ['ar', 'Arabic', 'العربية', 'ar_SA', 'rtl', 2, 0, 1],
            ['zh', 'Chinese', '中文', 'zh_CN', 'ltr', 3, 0, 1],
            ['fr', 'French', 'Français', 'fr_FR', 'ltr', 4, 0, 1],
            ['es', 'Spanish', 'Español', 'es_ES', 'ltr', 5, 0, 1],
            ['id', 'Indonesian', 'Bahasa Indonesia', 'id_ID', 'ltr', 6, 0, 1],
        ];

        foreach ($languages as $language) {
            $langStmt->execute($language);
        }

        $langIdStmt = $pdo->prepare("SELECT ID FROM tbl_languages WHERE lang_code = 'en' LIMIT 1");
        $langIdStmt->execute();
        $enLangId = (int)$langIdStmt->fetchColumn();

        // --- Translations (10 for "en") -----------------------------------------
        $transStmt = $pdo->prepare(
            'INSERT INTO tbl_translations (lang_id, translation_key, translation_value, translation_context, is_html)
             VALUES (?, ?, ?, ?, 0)'
        );

        $translations = [
            ['nav.dashboard', 'Dashboard', 'navigation'],
            ['nav.posts', 'Posts', 'navigation'],
            ['nav.categories', 'Categories', 'navigation'],
            ['nav.media', 'Media', 'navigation'],
            ['nav.users', 'Users', 'navigation'],
            ['nav.comments', 'Comments', 'navigation'],
            ['form.save', 'Save', 'forms'],
            ['form.cancel', 'Cancel', 'forms'],
            ['form.delete', 'Delete', 'forms'],
            ['home.welcome', 'Welcome to the blog', 'home'],
        ];

        self::$translationIds = [];
        foreach ($translations as $translation) {
            $transStmt->execute([$enLangId, $translation[0], $translation[1], $translation[2]]);
            self::$translationIds[] = (int)$pdo->lastInsertId();
        }

        // --- Comments (3 on post #1) --------------------------------------------
        $commentStmt = $pdo->prepare(
            'INSERT INTO tbl_comments
             (comment_post_id, comment_parent_id, comment_author_name, comment_author_ip,
              comment_author_email, comment_content, comment_status, comment_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );

        self::$commentIds = [];

        $comments = [
            ['Alice', '127.0.0.1', 'alice@example.com', 'An approved comment.', 'approved'],
            ['Bob', '127.0.0.1', 'bob@example.com', 'A pending comment awaiting moderation.', 'pending'],
            ['Mallory', '127.0.0.1', 'mallory@example.com', 'This is spam.', 'spam'],
        ];

        foreach ($comments as $comment) {
            $commentStmt->execute([
                self::$postIds[0],
                0,
                $comment[0],
                $comment[1],
                $comment[2],
                $comment[3],
                $comment[4],
            ]);
            self::$commentIds[] = (int)$pdo->lastInsertId();
        }

        // --- Media --------------------------------------------------------------
        $mediaStmt = $pdo->prepare(
            'INSERT INTO tbl_media
             (media_filename, media_caption, media_type, media_target, media_user, media_access, media_status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $mediaStmt->execute([
            'api-sample-image.jpg',
            'API sample image',
            'image',
            'blog',
            'administrator',
            'public',
            1,
        ]);

        return [
            'admin_id' => self::$adminId,
            'api_key' => self::$apiKey,
            'post_ids' => self::$postIds,
            'topic_ids' => self::$topicIds,
        ];
    }

    /**
     * Get the administrator user ID (after seed()).
     *
     * @return int|null
     */
    public static function getAdminId()
    {
        return self::$adminId;
    }

    /**
     * Get the plaintext API key (after seed()).
     *
     * @return string|null
     */
    public static function getApiKey()
    {
        return self::$apiKey;
    }

    /**
     * Get the seeded post IDs (after seed()).
     *
     * @return array
     */
    public static function getPostIds()
    {
        return self::$postIds;
    }

    /**
     * Get the seeded topic IDs (after seed()).
     *
     * @return array
     */
    public static function getTopicIds()
    {
        return self::$topicIds;
    }

    /**
     * Get the seeded comment IDs (after seed()). The array contains one
     * entry per seeded comment in insertion order (approved, pending, spam).
     *
     * @return array<int, int>
     */
    public static function getCommentIds()
    {
        return self::$commentIds;
    }

    /**
     * Get the seeded translation IDs (after seed()).
     *
     * @return array<int, int>
     */
    public static function getTranslationIds()
    {
        return self::$translationIds;
    }
}
