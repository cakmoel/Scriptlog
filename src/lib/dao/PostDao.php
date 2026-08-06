<?php

declare(strict_types=1);

namespace Scriptlog\Dao;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\Dao;
use Scriptlog\Core\DbException;
use Scriptlog\Core\LogError;
use Scriptlog\Core\Sanitize;

/**
 * class PostDao extends Dao
 *
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 */
class PostDao extends Dao
{
    /**
     * Columns allowed in ORDER BY clauses to prevent SQL injection.
     *
     * @var array
     */
    private const ALLOWED_SORT_COLUMNS = ['ID', 'post_date', 'post_title', 'post_modified'];

    /**
     * Shared WHERE fragment filtering to published, public blog posts
     * (used by the paginated/archive SELECT queries that alias tbl_posts as p).
     *
     * @var string
     */
    private const PUBLISHED_FILTER = "p.post_status = 'publish' AND p.post_visibility = 'public'";

    /**
     * Shared SELECT column list for paginated published-post queries.
     *
     * @var string
     */
    private const SELECT_PUBLISHED_COLUMNS = "p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                   p.post_title, p.post_slug, p.post_summary, p.post_status,
                   p.post_visibility, p.post_tags, p.post_type, p.comment_status,
                   u.user_login as author_login, u.user_fullname as author_name";

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * findPosts
     * Retrieving all records from table posts
     *
     * @param string $orderBy
     * @param int|null $author
     * @param bool $onlyPublished
     * @return array
     * @throws DbException
     */
    public function findPosts(string $orderBy = 'ID', ?int $author = null, bool $onlyPublished = true): array
    {
        $sortColumn = $this->resolveSortColumn($orderBy);

        $sql = "SELECT p.ID,
            p.media_id,
            p.post_author,
            p.post_date,
            p.post_modified,
            p.post_title,
            p.post_slug,
            p.post_content,
            p.post_status,
            p.post_visibility,
            p.post_password,
            p.post_tags,
            p.post_headlines,
            p.post_type,
            p.post_locale,
            p.passphrase,
            u.user_login
FROM tbl_posts AS p
INNER JOIN tbl_users AS u ON p.post_author = u.ID
WHERE p.post_type = 'blog'";

        $data = [];

        if (!is_null($author)) {
            $sql .= " AND p.post_author = ?";
            $data[] = $author;
        }

        if ($onlyPublished) {
            $sql .= " AND " . self::PUBLISHED_FILTER;
        }

        $sql .= " ORDER BY p.$sortColumn DESC";

        $this->setSQL($sql);

        $posts = $this->findAll($data);

        return (empty($posts)) ? [] : $posts;
    }

    /**
     * Find published posts with pagination for API endpoints.
     *
     * @param int $limit
     * @param int $offset
     * @param string $sortBy
     * @param string $sortOrder
     * @param int|null $author
     * @return array
     * @throws DbException
     */
    public function findPublishedPostsPaginated(int $limit, int $offset, string $sortBy = 'ID', string $sortOrder = 'DESC', ?int $author = null): array
    {
        $sortColumn = $this->resolveSortColumn($sortBy);
        $sortDir = $this->resolveSortDirection($sortOrder);

        $sql = "SELECT " . self::SELECT_PUBLISHED_COLUMNS . "
                FROM tbl_posts p
                LEFT JOIN tbl_users u ON p.post_author = u.ID
                WHERE " . self::PUBLISHED_FILTER . "
                AND p.post_type = 'blog'";

        $data = [];

        if ($author !== null) {
            $sql .= " AND p.post_author = ?";
            $data[] = $author;
        }

        $sql .= " ORDER BY p.$sortColumn $sortDir";
        $sql .= " LIMIT ? OFFSET ?";
        $data[] = $limit;
        $data[] = $offset;

        $this->setSQL($sql);

        $posts = $this->findAll($data);

        return empty($posts) ? [] : $posts;
    }

    /**
     * Count published posts.
     *
     * @param int|null $author
     * @return int
     * @throws DbException
     */
    public function countPublishedPosts(?int $author = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM tbl_posts
                WHERE post_status = 'publish'
                AND post_type = 'blog'
                AND post_visibility = 'public'";

        $data = [];

        if ($author !== null) {
            $sql .= " AND post_author = ?";
            $data[] = $author;
        }

        $this->setSQL($sql);

        $result = $this->findRow($data);

        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Find a single published post by ID.
     *
     * @param int $postId
     * @return array|null
     * @throws DbException
     */
    public function findPublishedPostById(int $postId): ?array
    {
        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                       p.post_title, p.post_slug, p.post_content, p.post_summary, p.post_status,
                       p.post_visibility, p.post_password, p.post_tags, p.post_headlines,
                       p.post_type, p.comment_status, p.passphrase, p.post_locale,
                       u.user_login as author_login, u.user_fullname as author_name
                FROM tbl_posts p
                LEFT JOIN tbl_users u ON p.post_author = u.ID
                WHERE p.ID = ?
                AND p.post_type = 'blog'
                AND " . self::PUBLISHED_FILTER;

        $this->setSQL($sql);

        $result = $this->findRow([$postId]);

        return empty($result) ? null : $result;
    }

    /**
     * Retrieve a single post by its ID regardless of status/visibility.
     *
     * Used by the API layer so callers never reach into the DAO base
     * class internals (setSQL/findRow) to read a post.
     *
     * @param int $postId
     * @return array|null
     * @throws DbException
     */
    public function getPostById(int $postId): ?array
    {
        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                       p.post_title, p.post_slug, p.post_content, p.post_summary, p.post_status,
                       p.post_visibility, p.post_password, p.post_tags, p.post_headlines,
                       p.post_type, p.comment_status, p.passphrase, p.post_locale,
                       u.user_login as author_login, u.user_fullname as author_name
                FROM tbl_posts p
                LEFT JOIN tbl_users u ON p.post_author = u.ID
                WHERE p.ID = ?
                AND p.post_type = 'blog'
                LIMIT 1";

        $this->setSQL($sql);

        $result = $this->findRow([$postId]);

        return empty($result) ? null : $result;
    }

    /**
     * Find the adjacent published blog post relative to the given ID.
     *
     * Used by the theme previous/next post navigation. When $direction is
     * 'previous' it returns the post with the largest ID below $postId;
     * when 'next' it returns the post with the smallest ID above $postId.
     *
     * @param int $postId The reference post ID.
     * @param string $direction 'previous' or 'next'.
     * @return array|null Adjacent post row (ID, post_title, post_slug) or null.
     * @throws DbException
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes prev/next
     *                 nav helpers, outside the Psalm scan tree (lib/ only).
     */
    public function findAdjacentPost(int $postId, string $direction = 'previous'): ?array
    {
        $operator = ($direction === 'next') ? '>' : '<';
        $order = ($direction === 'next') ? 'ASC' : 'DESC';

        $sql = "SELECT ID, post_title, post_slug
                FROM tbl_posts
                WHERE ID " . $operator . " ? AND post_status = 'publish' AND post_type = 'blog'
                ORDER BY ID " . $order . " LIMIT 1";

        $this->setSQL($sql);

        $result = $this->findRow([$postId]);

        return empty($result) ? null : $result;
    }

    /**
     * findPost()
     *
     * Retrieving a single post record by its ID.
     *
     * @param int $ID
     * @param Sanitize $sanitize
     * @param int|null $author
     * @param bool $onlyPublished
     * @return array|null
     * @throws DbException
     * @throws \InvalidArgumentException
     */
    public function findPost(int $ID, Sanitize $sanitize, ?int $author = null, bool $onlyPublished = true): ?array
    {
        $idsanitized = $this->filteringId($sanitize, (string)$ID, 'sql');

        $sql = "SELECT ID,
            media_id,
            post_author,
            post_date,
            post_modified,
            post_title,
            post_slug,
            post_content,
            post_summary,
            post_status,
            post_visibility,
            post_password,
            post_tags,
            post_headlines,
            post_locale,
            comment_status,
            passphrase
FROM tbl_posts
WHERE ID = ? AND post_type = 'blog'";

        $data = [$idsanitized];

        if (!is_null($author)) {
            $sql .= " AND post_author = ?";
            $data[] = $author;
        }

        if ($onlyPublished) {
            $sql .= " AND post_status = 'publish' AND post_visibility = 'public'";
        }

        $this->setSQL($sql);

        $postDetail = $this->findRow($data);

        return (empty($postDetail)) ? null : $postDetail;
    }

    /**
     * createPost
     *
     * Insert a new post record together with its topic relationships.
     *
     * @param array $bind
     * @param int|array $topicId
     * @return int
     * @throws \InvalidArgumentException
     */
    public function createPost(array $bind, $topicId): int
    {
        $data = [
           'post_author' => $bind['post_author'],
           'post_date' => $bind['post_date'],
           'post_title' => $bind['post_title'],
           'post_slug' => $bind['post_slug'],
           'post_content' => $bind['post_content'],
           'post_summary' => $bind['post_summary'],
           'post_status' => $bind['post_status'],
           'post_visibility' => $bind['post_visibility'],
           'post_password' => $bind['post_password'],
           'post_tags' => $bind['post_tags'],
           'post_headlines' => $bind['post_headlines'],
           'post_locale' => $bind['post_locale'] ?? 'en',
           'comment_status' => $bind['comment_status'],
           'passphrase' => $bind['passphrase']
        ];

        if (!empty($bind['media_id'])) {
            $data['media_id'] = $bind['media_id'];
        }

        $this->create("tbl_posts", $data);

        $postId = (int)$this->lastId();

        foreach ((array)$topicId as $topic_id) {
            $this->create("tbl_post_topic", [
              'post_id' => $postId,
              'topic_id' => $topic_id]);
        }

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }

        return $postId;
    }

    /**
     * updatePost
     *
     * Updating an existing post record together with its topic relationships.
     *
     * @param Sanitize $sanitize
     * @param array $bind
     * @param int $ID
     * @param int|array $topicId
     * @return void
     * @throws \InvalidArgumentException
     */
    public function updatePost(Sanitize $sanitize, array $bind, int $ID, $topicId): void
    {
        $cleanId = $this->filteringId($sanitize, (string)$ID, 'sql');

        try {
            $this->callTransaction();

            $updateData = [
                'post_author' => $bind['post_author'],
                'post_modified' => $bind['post_modified'],
                'post_title' => $bind['post_title'],
                'post_slug' => $bind['post_slug'],
                'post_content' => $bind['post_content'],
                'post_summary' => $bind['post_summary'],
                'post_status' => $bind['post_status'],
                'post_visibility' => $bind['post_visibility'],
                'post_tags' => $bind['post_tags'],
                'post_headlines' => $bind['post_headlines'],
                'post_locale' => $bind['post_locale'] ?? 'en',
                'comment_status' => $bind['comment_status']
            ];

            if (!empty($bind['post_password'])) {
                $updateData['post_password'] = $bind['post_password'];
            }
            if (!empty($bind['passphrase'])) {
                $updateData['passphrase'] = $bind['passphrase'];
            }

            if (!empty($bind['media_id'])) {
                $updateData['media_id'] = $bind['media_id'];
            }

            $this->modify("tbl_posts", $updateData, ['ID' => (int)$cleanId]);

            $this->deleteRecord("tbl_post_topic", ['post_id' => (int)$cleanId], null);

            foreach ((array)$topicId as $topic_id) {
                $this->create("tbl_post_topic", [
                    'post_id' => $cleanId,
                    'topic_id' => $topic_id
                ]);
            }

            $this->callCommit();

            if (function_exists('page_cache_clear')) {
                page_cache_clear();
            }
        } catch (DbException $e) {
            $this->callRollBack();
            $this->error = (string)LogError::setStatusCode(500);
            LogError::exceptionHandler($e);
        } catch (\Throwable $th) {
            $this->callRollBack();
            $this->error = (string)LogError::setStatusCode(500);
            LogError::exceptionHandler($th);
        }
    }

    /**
     * DeletePost
     *
     * @param int $ID
     * @param Sanitize $sanitize
     * @return void
     * @throws \InvalidArgumentException
     */
    public function deletePost(int $ID, Sanitize $sanitize): void
    {
        $cleanId = $this->filteringId($sanitize, (string)$ID, 'sql');
        $this->deleteRecord("tbl_posts", ['ID' => $cleanId]);

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }
    }

    /**
     * Anonymize post author info.
     *
     * Used for GDPR data deletion (Right to be Forgotten).
     *
     * @param int $authorId
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function anonymizePostAuthor(int $authorId): bool
    {
        $this->modify("tbl_posts", ['post_author' => 1], ['post_author' => $authorId]);

        return true;
    }

    /**
     * checkPostId
     *
     * @param int $ID
     * @param Sanitize $sanitize
     * @return bool
     * @throws \InvalidArgumentException
     * @throws DbException
     */
    public function checkPostId(int $ID, Sanitize $sanitize): bool
    {
        $idsanitized = $this->filteringId($sanitize, (string)$ID, 'sql');

        $sql = "SELECT ID FROM tbl_posts WHERE ID = ? AND post_type = 'blog'";

        $this->setSQL($sql);

        $stmt = $this->checkCountValue([$idsanitized]);

        return $stmt > 0;
    }

    /**
     * Total posts records.
     *
     * @param int|null $author
     * @return int
     * @throws DbException
     */
    public function totalPostRecords(?int $author = null): int
    {
        $sql = "SELECT ID FROM tbl_posts WHERE post_type = 'blog'";

        $data = [];

        if (!is_null($author)) {
            $sql = "SELECT ID FROM tbl_posts WHERE post_author = ? AND post_type = 'blog'";
            $data[] = $author;
        }

        $this->setSQL($sql);

        return $this->checkCountValue($data);
    }

    /**
     * Get distinct year-month combinations with post counts for archive index.
     *
     * @return array
     * @throws DbException
     */
    public function findArchiveIndex(): array
    {
        $sql = "SELECT
                    YEAR(post_date) as year,
                    MONTH(post_date) as month,
                    COUNT(*) as post_count
                FROM tbl_posts
                WHERE post_status = 'publish'
                AND post_type = 'blog'
                AND post_visibility = 'public'
                GROUP BY YEAR(post_date), MONTH(post_date)
                ORDER BY year DESC, month DESC";

        $this->setSQL($sql);
        $results = $this->findAll();

        return empty($results) ? [] : $results;
    }

    /**
     * Find published posts by year with pagination.
     *
     * @param int $year
     * @param int $limit
     * @param int $offset
     * @param string $sortBy
     * @param string $sortOrder
     * @return array
     * @throws DbException
     */
    public function findPostsByYear(int $year, int $limit, int $offset, string $sortBy = 'ID', string $sortOrder = 'DESC'): array
    {
        $sortColumn = $this->resolveSortColumn($sortBy);
        $sortDir = $this->resolveSortDirection($sortOrder);

        $sql = "SELECT " . self::SELECT_PUBLISHED_COLUMNS . "
                FROM tbl_posts p
                LEFT JOIN tbl_users u ON p.post_author = u.ID
                WHERE YEAR(p.post_date) = ?
                AND " . self::PUBLISHED_FILTER . "
                AND p.post_type = 'blog'
                ORDER BY p.$sortColumn $sortDir
                LIMIT ? OFFSET ?";

        $this->setSQL($sql);
        $posts = $this->findAll([$year, $limit, $offset]);

        return empty($posts) ? [] : $posts;
    }

    /**
     * Count published posts for a given year.
     *
     * @param int $year
     * @return int
     * @throws DbException
     */
    public function countPostsByYear(int $year): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM tbl_posts
                WHERE YEAR(post_date) = ?
                AND post_status = 'publish'
                AND post_type = 'blog'
                AND post_visibility = 'public'";

        $this->setSQL($sql);
        $result = $this->findRow([$year]);

        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Find published posts by year and month with pagination.
     *
     * @param int $year
     * @param int $month
     * @param int $limit
     * @param int $offset
     * @param string $sortBy
     * @param string $sortOrder
     * @return array
     * @throws DbException
     */
    public function findPostsByYearMonth(int $year, int $month, int $limit, int $offset, string $sortBy = 'ID', string $sortOrder = 'DESC'): array
    {
        $sortColumn = $this->resolveSortColumn($sortBy);
        $sortDir = $this->resolveSortDirection($sortOrder);

        $sql = "SELECT " . self::SELECT_PUBLISHED_COLUMNS . "
                FROM tbl_posts p
                LEFT JOIN tbl_users u ON p.post_author = u.ID
                WHERE YEAR(p.post_date) = ?
                AND MONTH(p.post_date) = ?
                AND " . self::PUBLISHED_FILTER . "
                AND p.post_type = 'blog'
                ORDER BY p.$sortColumn $sortDir
                LIMIT ? OFFSET ?";

        $this->setSQL($sql);
        $posts = $this->findAll([$year, $month, $limit, $offset]);

        return empty($posts) ? [] : $posts;
    }

    /**
     * Count published posts for a given year and month.
     *
     * @param int $year
     * @param int $month
     * @return int
     * @throws DbException
     */
    public function countPostsByYearMonth(int $year, int $month): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM tbl_posts
                WHERE YEAR(post_date) = ?
                AND MONTH(post_date) = ?
                AND post_status = 'publish'
                AND post_type = 'blog'
                AND post_visibility = 'public'";

        $this->setSQL($sql);
        $result = $this->findRow([$year, $month]);

        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Search posts with LIKE for API query endpoint.
     *
     * @param string $keyword
     * @param string $type 'blog', 'page', or 'all'
     * @param int $limit
     * @return array
     * @throws DbException
     */
    public function searchPostsApi(string $keyword, string $type = 'all', int $limit = 50): array
    {
        $likeKeyword = '%' . $keyword . '%';

        if ($type === 'page') {
            $sql = "SELECT ID, post_title, post_slug, post_date, post_content,
                           post_summary, post_status, post_type
                    FROM tbl_posts
                    WHERE post_status = 'publish'
                    AND post_type = 'page'
                    AND (post_title LIKE ? OR post_content LIKE ?)
                    ORDER BY post_date DESC
                    LIMIT ?";
            $this->setSQL($sql);
            return $this->findAll([$likeKeyword, $likeKeyword, $limit]);
        }

        $sql = "SELECT ID, post_title, post_slug, post_date, post_content,
                       post_summary, post_status, post_type
                FROM tbl_posts
                WHERE post_status = 'publish'
                AND (post_title LIKE ? OR post_content LIKE ?" .
                ($type === 'all' ? " OR post_tags LIKE ?" : "") . ")
                " . ($type === 'blog' ? "AND post_type = 'blog'" : "") . "
                ORDER BY post_date DESC
                LIMIT ?";

        $this->setSQL($sql);
        $params = [$likeKeyword, $likeKeyword];
        if ($type === 'all') {
            $params[] = $likeKeyword;
        }
        $params[] = $limit;
        return $this->findAll($params);
    }

    /**
     * Find topics/categories attached to a post.
     *
     * @param int $postId
     * @return array
     * @throws DbException
     */
    public function findTopicsByPostId(int $postId): array
    {
        $sql = "SELECT t.ID, t.topic_title, t.topic_slug
                FROM tbl_topics t
                INNER JOIN tbl_post_topic pt ON t.ID = pt.topic_id
                WHERE pt.post_id = ?";

        $this->setSQL($sql);
        $topics = $this->findAll([$postId]);

        return empty($topics) ? [] : $topics;
    }

    /**
     * Find active topics/categories attached to a post.
     *
     * Same as findTopicsByPostId() but only returns topics whose
     * topic_status flag is 'Y' (visible on the frontend).
     *
     * @param int $postId
     * @return array
     * @throws DbException
     *
     * @psalm-suppress PossiblyUnusedMethod -- called by public/themes topic
     *                 helpers, outside the Psalm scan tree (lib/ only).
     */
    public function findActiveTopicsByPostId(int $postId): array
    {
        $sql = "SELECT t.ID, t.topic_title, t.topic_slug
                FROM tbl_topics t
                INNER JOIN tbl_post_topic pt ON t.ID = pt.topic_id
                WHERE pt.post_id = ? AND t.topic_status = 'Y'";

        $this->setSQL($sql);
        $topics = $this->findAll([$postId]);

        return empty($topics) ? [] : $topics;
    }

    /**
     * Delete all topic relationships for a post.
     *
     * @param int $postId
     * @return void
     */
    public function deletePostTopics(int $postId): void
    {
        $this->deleteRecord("tbl_post_topic", ['post_id' => $postId], null);
    }

    /**
     * Replace all topic relationships for a post.
     *
     * Deletes existing relationships, then inserts the given topic IDs.
     * Used by the API layer to persist a post's category assignments.
     *
     * @param int $postId
     * @param array $topicIds List of topic IDs.
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setPostTopics(int $postId, array $topicIds): void
    {
        $this->deleteRecord("tbl_post_topic", ['post_id' => $postId], null);

        foreach ($topicIds as $topicId) {
            $this->create("tbl_post_topic", [
                'post_id' => $postId,
                'topic_id' => $topicId
            ]);
        }
    }

    /**
     * Delete all comments for a post.
     *
     * @param int $postId
     * @return void
     */
    public function deletePostComments(int $postId): void
    {
        $this->deleteRecord("tbl_comments", ['comment_post_id' => $postId], null);
    }

    /**
     * Insert a post with the given data and return the new ID.
     *
     * @param array $data Column => value pairs
     * @return int
     * @throws \InvalidArgumentException
     */
    public function insertPostApi(array $data): int
    {
        $this->create("tbl_posts", $data);

        return (int)$this->lastId();
    }

    /**
     * Update specific post fields.
     *
     * @param int $postId
     * @param array $data Column => value pairs
     * @return void
     * @throws \InvalidArgumentException
     */
    public function updatePostApi(int $postId, array $data): void
    {
        $this->modify("tbl_posts", $data, ['ID' => $postId]);
    }

    /**
     * Resolve a safe ORDER BY column from a user-supplied sort key.
     *
     * Returns a fallback of 'ID' when the requested column is not whitelisted.
     *
     * @param string $sortBy
     * @return string
     */
    private function resolveSortColumn(string $sortBy): string
    {
        $allowedColumns = self::ALLOWED_SORT_COLUMNS;

        return in_array($sortBy, $allowedColumns) ? $sortBy : 'ID';
    }

    /**
     * Resolve a safe ORDER BY direction ('ASC' or 'DESC').
     *
     * Any value other than ASC falls back to DESC.
     *
     * @param string $sortOrder
     * @return string
     */
    private function resolveSortDirection(string $sortOrder): string
    {
        return strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    }
}
