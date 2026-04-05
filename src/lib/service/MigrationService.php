<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * MigrationService
 *
 * Main service for handling content import from various platforms
 *
 * @category  Service Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class MigrationService
{
    private $dbc;
    private $sanitizer;

    private $authorId;
    private $importStats;

    private $categoryMap = [];
    private $postIdMap = [];

    public const SOURCE_WORDPRESS = 'wordpress';
    public const SOURCE_GHOST = 'ghost';
    public const SOURCE_BLOGSPOT = 'blogspot';
    public const SOURCE_SCRIPTLOG = 'scriptlog';

    public function __construct(Sanitize $sanitizer)
    {
        $this->dbc = Registry::get('dbc');
        $this->sanitizer = $sanitizer;

        $this->authorId = 1;
        $this->importStats = [
          'posts_created' => 0,
          'posts_updated' => 0,
          'posts_skipped' => 0,
          'pages_created' => 0,
          'categories_created' => 0,
          'comments_created' => 0,
          'comments_skipped' => 0,
          'errors' => []
        ];

        $this->categoryMap = [];
        $this->postIdMap = [];
    }

    /**
     * Set author ID for imported content
     *
     * @param int $authorId
     */
    public function setAuthorId($authorId)
    {
        $this->authorId = (int) $authorId;
    }

    /**
     * Get import statistics
     *
     * @return array
     */
    public function getStats()
    {
        return $this->importStats;
    }

    /**
     * Reset import statistics
     *
     * @return void
     */
    public function resetStats()
    {
        $this->importStats = [
          'posts_created' => 0,
          'posts_updated' => 0,
          'posts_skipped' => 0,
          'pages_created' => 0,
          'categories_created' => 0,
          'comments_created' => 0,
          'comments_skipped' => 0,
          'errors' => []
        ];

        $this->categoryMap = [];
        $this->postIdMap = [];
    }

    /**
     * Import content from WordPress WXR file
     *
     * @param string $wxrContent
     * @return array
     */
    public function importFromWordPress($wxrContent)
    {
        $this->resetStats();

        try {
            $importer = new WordPressImporter();
            $importer->parse($wxrContent);

            $categories = $importer->getCategories();
            $this->importCategories($categories);

            $posts = $importer->getPosts();
            $this->importPosts($posts);

            return [
              'success' => true,
              'source' => self::SOURCE_WORDPRESS,
              'stats' => $this->importStats,
              'site_info' => $importer->getSiteInfo()
            ];
        } catch (ImportException $e) {
            return [
              'success' => false,
              'error' => $e->getMessage(),
              'source' => self::SOURCE_WORDPRESS
            ];
        }
    }

    /**
     * Import content from Ghost JSON file
     *
     * @param string $jsonContent
     * @return array
     */
    public function importFromGhost($jsonContent)
    {
        $this->resetStats();

        try {
            $importer = new GhostImporter();
            $importer->parse($jsonContent);

            $categories = $importer->getCategories();
            $this->importCategories($categories);

            $posts = $importer->getPosts();
            $this->importPosts($posts);

            return [
              'success' => true,
              'source' => self::SOURCE_GHOST,
              'stats' => $this->importStats,
              'site_info' => $importer->getSiteInfo()
            ];
        } catch (ImportException $e) {
            return [
              'success' => false,
              'error' => $e->getMessage(),
              'source' => self::SOURCE_GHOST
            ];
        }
    }

    /**
     * Import content from Blogspot XML file
     *
     * @param string $xmlContent
     * @return array
     */
    public function importFromBlogspot($xmlContent)
    {
        $this->resetStats();

        try {
            $importer = new BlogspotImporter();
            $importer->parse($xmlContent);

            $categories = $importer->getCategories();
            $this->importCategories($categories);

            $posts = $importer->getPosts();
            $this->importPosts($posts);

            $pages = $importer->getPages();
            $this->importPages($pages);

            return [
              'success' => true,
              'source' => self::SOURCE_BLOGSPOT,
              'stats' => $this->importStats,
              'site_info' => $importer->getSiteInfo()
            ];
        } catch (ImportException $e) {
            return [
              'success' => false,
              'error' => $e->getMessage(),
              'source' => self::SOURCE_BLOGSPOT
            ];
        }
    }

    /**
     * Import content from Scriptlog native JSON file
     *
     * @param string $jsonContent
     * @return array
     */
    public function importFromScriptlog($jsonContent)
    {
        $this->resetStats();

        try {
            $importer = new ScriptlogImporter();
            $importer->parse($jsonContent);

            // Import topics (categories)
            $topics = $importer->getTopics();
            $this->importScriptlogTopics($topics);

            // Import posts
            $posts = $importer->getPosts();
            $this->importScriptlogPosts($posts);

            // Import pages
            $pages = $importer->getPages();
            $this->importScriptlogPages($pages);

            // Import comments
            $comments = $importer->getComments();
            $this->importScriptlogComments($comments);

            // Import menus
            $menus = $importer->getMenus();
            $this->importScriptlogMenus($menus);

            // Import settings
            $settings = $importer->getSettings();
            $this->importScriptlogSettings($settings);

            return [
              'success' => true,
              'source' => self::SOURCE_SCRIPTLOG,
              'stats' => $this->importStats,
              'site_info' => $importer->getSiteInfo()
            ];
        } catch (ImportException $e) {
            return [
              'success' => false,
              'error' => $e->getMessage(),
              'source' => self::SOURCE_SCRIPTLOG
            ];
        }
    }

    /**
     * Import topics from Scriptlog export
     *
     * @param array $topics
     */
    private function importScriptlogTopics($topics)
    {
        if (empty($topics)) {
            return;
        }

        foreach ($topics as $topic) {
            $name = $this->sanitizeInput($topic['topic_title']);
            $slug = $this->sanitizeInput($topic['topic_slug']);

            if (empty($name)) {
                continue;
            }

            $existingTopic = $this->findTopicBySlug($slug);

            if ($existingTopic) {
                $this->categoryMap[$topic['ID']] = $existingTopic['ID'];
            } else {
                $topicId = $this->createTopic($name, $slug);

                if ($topicId) {
                    $this->categoryMap[$topic['ID']] = $topicId;
                    $this->importStats['categories_created']++;
                }
            }
        }
    }

    /**
     * Import posts from Scriptlog export
     *
     * @param array $posts
     */
    private function importScriptlogPosts($posts)
    {
        if (empty($posts)) {
            return;
        }

        foreach ($posts as $post) {
            try {
                $originalPostId = $post['ID'];
                $slug = $this->sanitizeInput($post['post_slug'] ?? make_slug($post['post_title']));
                $title = $this->sanitizeInput($post['post_title'] ?? 'Untitled');
                $content = purify_dirty_html($post['post_content'] ?? '');
                $excerpt = $this->sanitizeInput($post['post_summary'] ?? '');

                $existingPost = $this->findPostBySlug($slug);

                if ($existingPost) {
                    $this->importStats['posts_skipped']++;
                    $this->postIdMap[$originalPostId] = $existingPost['ID'];
                } else {
                    $postData = [
                      'post_author' => $this->authorId,
                      'post_date' => $this->formatDate($post['post_date'] ?? date('Y-m-d H:i:s')),
                      'post_modified' => $this->formatDate($post['post_modified'] ?? date('Y-m-d H:i:s')),
                      'post_title' => $title,
                      'post_slug' => $this->ensureUniqueSlug($slug),
                      'post_content' => $content,
                      'post_summary' => $excerpt,
                      'post_status' => $this->mapStatus($post['post_status'] ?? 'publish'),
                      'post_visibility' => $post['post_visibility'] ?? 'public',
                      'post_password' => $post['post_password'] ?? '',
                      'post_tags' => $this->sanitizeInput($post['post_tags'] ?? ''),
                      'post_type' => $post['post_type'] ?? 'blog',
                      'comment_status' => $post['comment_status'] ?? 'open',
                      'media_id' => 0
                    ];

                    $postId = $this->createPost($postData);

                    if ($postId) {
                        $this->postIdMap[$originalPostId] = $postId;
                        $this->importStats['posts_created']++;
                    }
                }
            } catch (\Throwable $e) {
                $this->importStats['errors'][] = 'Error importing post: ' . ($post['post_title'] ?? 'Unknown') . ' - ' . $e->getMessage();
            }
        }
    }

    /**
     * Import pages from Scriptlog export
     *
     * @param array $pages
     */
    private function importScriptlogPages($pages)
    {
        if (empty($pages)) {
            return;
        }

        foreach ($pages as $page) {
            try {
                $originalPageId = $page['ID'];
                $slug = $this->sanitizeInput($page['post_slug'] ?? make_slug($page['post_title']));
                $title = $this->sanitizeInput($page['post_title'] ?? 'Untitled');
                $content = purify_dirty_html($page['post_content'] ?? '');

                $existingPost = $this->findPostBySlug($slug);

                if ($existingPost) {
                    $this->postIdMap[$originalPageId] = $existingPost['ID'];
                } else {
                    $postData = [
                      'post_author' => $this->authorId,
                      'post_date' => $this->formatDate($page['post_date'] ?? date('Y-m-d H:i:s')),
                      'post_modified' => $this->formatDate($page['post_modified'] ?? date('Y-m-d H:i:s')),
                      'post_title' => $title,
                      'post_slug' => $this->ensureUniqueSlug($slug),
                      'post_content' => $content,
                      'post_summary' => '',
                      'post_status' => $this->mapStatus($page['post_status'] ?? 'publish'),
                      'post_visibility' => $page['post_visibility'] ?? 'public',
                      'post_password' => '',
                      'post_tags' => '',
                      'post_type' => 'page',
                      'comment_status' => 'closed',
                      'media_id' => 0
                    ];

                    $pageId = $this->createPost($postData);

                    if ($pageId) {
                        $this->postIdMap[$originalPageId] = $pageId;
                        $this->importStats['pages_created']++;
                    }
                }
            } catch (\Throwable $e) {
                $this->importStats['errors'][] = 'Error importing page: ' . ($page['post_title'] ?? 'Unknown') . ' - ' . $e->getMessage();
            }
        }
    }

    /**
     * Import comments from Scriptlog export
     *
     * @param array $comments
     */
    private function importScriptlogComments($comments)
    {
        if (empty($comments)) {
            return;
        }

        foreach ($comments as $comment) {
            try {
                $originalPostId = $comment['comment_post_id'] ?? 0;
                $postId = $this->postIdMap[$originalPostId] ?? 0;

                if ($postId === 0) {
                    $this->importStats['comments_skipped']++;
                    continue;
                }

                $commentData = [
                  'comment_post_id' => $postId,
                  'comment_parent_id' => 0,
                  'comment_author_name' => $this->sanitizeInput($comment['comment_author_name'] ?? 'Anonymous'),
                  'comment_author_email' => $this->sanitizeInput($comment['comment_author_email'] ?? ''),
                  'comment_author_ip' => $this->sanitizeInput($comment['comment_author_ip'] ?? '127.0.0.1'),
                  'comment_content' => $this->sanitizeInput($comment['comment_content'] ?? ''),
                  'comment_status' => 'approved',
                  'comment_date' => $this->formatDate($comment['comment_date'] ?? date('Y-m-d H:i:s'))
                ];

                $commentId = $this->createComment($commentData);

                if ($commentId) {
                    $this->importStats['comments_created']++;
                }
            } catch (\Throwable $e) {
                $this->importStats['comments_skipped']++;
            }
        }
    }

    /**
     * Import menus from Scriptlog export
     *
     * @param array $menus
     */
    private function importScriptlogMenus($menus)
    {
        if (empty($menus)) {
            return;
        }

        foreach ($menus as $menu) {
            try {
                $menuData = [
                  'menu_label' => $this->sanitizeInput($menu['menu_label'] ?? ''),
                  'menu_link' => $this->sanitizeInput($menu['menu_link'] ?? ''),
                  'menu_status' => $menu['menu_status'] ?? 'Y',
                  'menu_visibility' => $menu['menu_visibility'] ?? 'everyone',
                  'parent_id' => $menu['parent_id'] ?? 0,
                  'menu_sort' => $menu['menu_sort'] ?? 0
                ];

                $this->dbc->dbInsert('tbl_menu', $menuData);
            } catch (\Throwable $e) {
                $this->importStats['errors'][] = 'Error importing menu: ' . ($menu['menu_label'] ?? 'Unknown');
            }
        }
    }

    /**
     * Import settings from Scriptlog export
     *
     * @param array $settings
     */
    private function importScriptlogSettings($settings)
    {
        if (empty($settings)) {
            return;
        }

        foreach ($settings as $name => $value) {
            try {
                $stmt = $this->dbc->dbQuery(
                    "SELECT ID FROM tbl_settings WHERE setting_name = ?",
                    [$name]
                );
                $existing = $stmt->fetch();

                if ($existing) {
                    $this->dbc->dbUpdate(
                        'tbl_settings',
                        ['setting_value' => $value],
                        ['setting_name' => $name]
                    );
                } else {
                    $this->dbc->dbInsert('tbl_settings', [
                      'setting_name' => $name,
                      'setting_value' => $value
                    ]);
                }
            } catch (\Throwable $e) {
                $this->importStats['errors'][] = 'Error importing setting: ' . $name;
            }
        }
    }

    /**
     * Preview import data without importing
     *
     * @param string $content
     * @param string $source
     * @return array
     */
    public function previewImport($content, $source)
    {
        try {
            switch ($source) {
                case self::SOURCE_WORDPRESS:
                    $importer = new WordPressImporter();
                    $importer->parse($content);
                    $posts = $importer->getPosts();
                    $categories = $importer->getCategories();
                    $tags = $importer->getTags();

                    return [
                      'success' => true,
                      'posts_count' => count($posts),
                      'categories_count' => count($categories),
                      'tags_count' => count($tags),
                      'site_info' => $importer->getSiteInfo(),
                      'posts' => array_slice($posts, 0, 10), // Return first 10 for preview
                      'categories' => array_slice($categories, 0, 10)
                    ];

                case self::SOURCE_GHOST:
                    $importer = new GhostImporter();
                    $importer->parse($content);
                    $posts = $importer->getPosts();
                    $categories = $importer->getCategories();
                    $tags = $importer->getTags();

                    return [
                      'success' => true,
                      'posts_count' => count($posts),
                      'categories_count' => count($categories),
                      'tags_count' => count($tags),
                      'site_info' => $importer->getSiteInfo(),
                      'posts' => array_slice($posts, 0, 10),
                      'categories' => array_slice($categories, 0, 10)
                    ];

                case self::SOURCE_BLOGSPOT:
                    $importer = new BlogspotImporter();
                    $importer->parse($content);
                    $posts = $importer->getPosts();
                    $pages = $importer->getPages();
                    $categories = $importer->getCategories();

                    return [
                      'success' => true,
                      'posts_count' => count($posts),
                      'pages_count' => count($pages),
                      'categories_count' => count($categories),
                      'site_info' => $importer->getSiteInfo(),
                      'posts' => array_slice($posts, 0, 10),
                      'pages' => array_slice($pages, 0, 10),
                      'categories' => array_slice($categories, 0, 10)
                    ];

                default:
                    throw new ImportException('Unknown source: ' . $source);
            }
        } catch (ImportException $e) {
            return [
              'success' => false,
              'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Import categories/topics
     *
     * @param array $categories
     */
    private function importCategories($categories)
    {
        if (empty($categories)) {
            return;
        }

        foreach ($categories as $category) {
            $name = $this->sanitizeInput($category['name']);
            $slug = $this->sanitizeInput($category['slug'] ?? make_slug($category['name']));

            if (empty($name)) {
                continue;
            }

            $existingTopic = $this->findTopicBySlug($slug);

            if ($existingTopic) {
                $this->categoryMap[$slug] = $existingTopic['ID'];
            } else {
                $topicId = $this->createTopic($name, $slug);

                if ($topicId) {
                    $this->categoryMap[$slug] = $topicId;
                    $this->importStats['categories_created']++;
                }
            }
        }
    }

    /**
     * Import posts
     *
     * @param array $posts
     */
    private function importPosts($posts)
    {
        if (empty($posts)) {
            return;
        }

        foreach ($posts as $post) {
            try {
                $slug = $this->sanitizeInput($post['slug'] ?? make_slug($post['title']));
                $title = $this->sanitizeInput($post['title'] ?? 'Untitled');
                $content = purify_dirty_html($post['content'] ?? '');
                $excerpt = $this->sanitizeInput($post['excerpt'] ?? '');

                $existingPost = $this->findPostBySlug($slug);

                if ($existingPost) {
                    $this->importStats['posts_skipped']++;
                    $originalPostId = $existingPost['ID'];
                } else {
                    $postData = [
                      'post_author' => $this->authorId,
                      'post_date' => $this->formatDate($post['date'] ?? date('Y-m-d H:i:s')),
                      'post_title' => $title,
                      'post_slug' => $this->ensureUniqueSlug($slug),
                      'post_content' => $content,
                      'post_summary' => $excerpt,
                      'post_status' => $this->mapStatus($post['status'] ?? 'publish'),
                      'post_visibility' => 'public',
                      'post_password' => '',
                      'post_tags' => implode(',', $post['tags'] ?? []),
                      'post_type' => $post['type'] ?? 'blog',
                      'comment_status' => $post['comment_status'] ?? 'open'
                    ];

                    $postId = $this->createPost($postData);

                    if ($postId) {
                        $this->postIdMap[$post['id'] ?? $postId] = $postId;

                        if (!empty($post['categories'])) {
                            $this->assignCategories($postId, $post['categories']);
                        }

                        if ($post['type'] === 'page') {
                            $this->importStats['pages_created']++;
                        } else {
                            $this->importStats['posts_created']++;
                        }

                        $originalPostId = $postId;
                    } else {
                        $this->importStats['errors'][] = 'Failed to create post: ' . $title;
                        continue;
                    }
                }

                if (!empty($post['comments'])) {
                    $this->importComments($post['comments'], $originalPostId);
                }
            } catch (\Throwable $e) {
                $this->importStats['errors'][] = 'Error importing post: ' . ($post['title'] ?? 'Unknown') . ' - ' . $e->getMessage();
            }
        }
    }

    /**
     * Import pages
     *
     * @param array $pages
     */
    private function importPages($pages)
    {
        if (empty($pages)) {
            return;
        }

        foreach ($pages as $page) {
            try {
                $slug = $this->sanitizeInput($page['slug'] ?? make_slug($page['title']));
                $title = $this->sanitizeInput($page['title'] ?? 'Untitled');
                $content = purify_dirty_html($page['content'] ?? '');

                $postData = [
                  'post_author' => $this->authorId,
                  'post_date' => $this->formatDate($page['date'] ?? date('Y-m-d H:i:s')),
                  'post_title' => $title,
                  'post_slug' => $this->ensureUniqueSlug($slug),
                  'post_content' => $content,
                  'post_summary' => '',
                  'post_status' => $this->mapStatus($page['status'] ?? 'publish'),
                  'post_visibility' => 'public',
                  'post_password' => '',
                  'post_tags' => '',
                  'post_type' => 'page',
                  'comment_status' => 'closed'
                ];

                $postId = $this->createPost($postData);

                if ($postId) {
                    $this->importStats['pages_created']++;
                }
            } catch (\Throwable $e) {
                $this->importStats['errors'][] = 'Error importing page: ' . ($page['title'] ?? 'Unknown') . ' - ' . $e->getMessage();
            }
        }
    }

    /**
     * Import comments
     *
     * @param array $comments
     * @param int $postId
     */
    private function importComments($comments, $postId)
    {
        if (empty($comments)) {
            return;
        }

        foreach ($comments as $comment) {
            try {
                $content = $this->sanitizeInput($comment['content'] ?? '');

                if (empty($content)) {
                    $this->importStats['comments_skipped']++;
                    continue;
                }

                $commentData = [
                  'comment_post_id' => $postId,
                  'comment_parent_id' => !empty($comment['parent']) ? ($this->postIdMap[$comment['parent']] ?? 0) : 0,
                  'comment_author_name' => $this->sanitizeInput($comment['author_name'] ?? 'Anonymous'),
                  'comment_author_email' => $this->sanitizeInput($comment['author_email'] ?? ''),
                  'comment_author_url' => $this->sanitizeInput($comment['author_url'] ?? ''),
                  'comment_author_ip' => $this->sanitizeInput($comment['author_ip'] ?? '127.0.0.1'),
                  'comment_content' => $content,
                  'comment_status' => $this->mapCommentStatus($comment['status'] ?? 'approved'),
                  'comment_date' => $this->formatDate($comment['date'] ?? date('Y-m-d H:i:s'))
                ];

                $commentId = $this->createComment($commentData);

                if ($commentId) {
                    $this->importStats['comments_created']++;
                }
            } catch (\Throwable $e) {
                $this->importStats['comments_skipped']++;
            }
        }
    }

    /**
     * Assign categories to post
     *
     * @param int $postId
     * @param array $categories
     */
    private function assignCategories($postId, $categories)
    {
        if (empty($categories)) {
            return;
        }

        $topicIds = [];

        foreach ($categories as $category) {
            $slug = $category['slug'] ?? make_slug($category['name'] ?? '');

            if (isset($this->categoryMap[$slug])) {
                $topicIds[] = $this->categoryMap[$slug];
            }
        }

        if (!empty($topicIds)) {
            foreach ($topicIds as $topicId) {
                $this->dbc->dbInsert('tbl_post_topic', [
                  'post_id' => $postId,
                  'topic_id' => $topicId
                ]);
            }
        }
    }

    /**
     * Find topic by slug
     *
     * @param string $slug
     * @return array|null
     */
    private function findTopicBySlug($slug)
    {
        $sql = "SELECT ID, topic_title, topic_slug FROM tbl_topics WHERE topic_slug = ?";
        $stmt = $this->dbc->dbQuery($sql, [$slug]);
        return $stmt->fetch();
    }

    /**
     * Find post by slug
     *
     * @param string $slug
     * @return array|null
     */
    private function findPostBySlug($slug)
    {
        $sql = "SELECT ID, post_title, post_slug FROM tbl_posts WHERE post_slug = ?";
        $stmt = $this->dbc->dbQuery($sql, [$slug]);
        return $stmt->fetch();
    }

    /**
     * Create topic
     *
     * @param string $name
     * @param string $slug
     * @return int|false
     */
    private function createTopic($name, $slug)
    {
        $result = $this->dbc->dbInsert('tbl_topics', [
          'topic_title' => $name,
          'topic_slug' => $slug
        ]);

        if ($result) {
            return (int) $this->dbc->dbLastInsertId();
        }

        return false;
    }

    /**
     * Create post
     *
     * @param array $data
     * @return int|false
     */
    private function createPost($data)
    {
        $result = $this->dbc->dbInsert('tbl_posts', $data);

        if ($result) {
            return (int) $this->dbc->dbLastInsertId();
        }

        return false;
    }

    /**
     * Create comment
     *
     * @param array $data
     * @return int|false
     */
    private function createComment($data)
    {
        $result = $this->dbc->dbInsert('tbl_comments', $data);

        if ($result) {
            return (int) $this->dbc->dbLastInsertId();
        }

        return false;
    }

    /**
     * Ensure unique slug
     *
     * @param string $slug
     * @return string
     */
    private function ensureUniqueSlug($slug)
    {
        $originalSlug = $slug;
        $counter = 1;

        while ($this->findPostBySlug($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Sanitize input
     *
     * @param string $input
     * @return string
     */
    private function sanitizeInput($input)
    {
        if (empty($input)) {
            return '';
        }

        return prevent_injection($input);
    }

    /**
     * Map post status
     *
     * @param string $status
     * @return string
     */
    private function mapStatus($status)
    {
        $statusMap = [
          'publish' => 'publish',
          'published' => 'publish',
          'draft' => 'draft',
          'pending' => 'pending',
          'private' => 'private'
        ];

        return $statusMap[$status] ?? 'draft';
    }

    /**
     * Map comment status
     *
     * @param string $status
     * @return string
     */
    private function mapCommentStatus($status)
    {
        $statusMap = [
          'approved' => 'approved',
          '1' => 'approved',
          'published' => 'approved',
          'pending' => 'pending',
          '0' => 'pending',
          'spam' => 'spam'
        ];

        return $statusMap[$status] ?? 'pending';
    }

    /**
     * Format date
     *
     * @param string $date
     * @return string
     */
    private function formatDate($date)
    {
        if (empty($date)) {
            return date('Y-m-d H:i:s');
        }

        $timestamp = strtotime($date);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
    }
}
