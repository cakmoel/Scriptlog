<?php

namespace Scriptlog\Dao;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * class PostDao extends Dao
 *
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

use Scriptlog\Core\Dao;
use Scriptlog\Core\DbException;
use Scriptlog\Core\LogError;

class PostDao extends Dao
{
    private $selected;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * findPosts
     * Retrieving all records from table posts
     *
     * @param string $orderBy
     * @param integer|null $author
     * @param bool $onlyPublished
     * @return boolean|array|object
     *
     */
    public function findPosts($orderBy = 'ID', $author = null, $onlyPublished = true)
    {
        $allowedColumns = ['ID', 'post_date', 'post_title', 'post_modified'];
        $sortColumn = in_array($orderBy, $allowedColumns) ? $orderBy : 'ID';

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
            $data[] = (int)$author;
        }

        if ($onlyPublished) {
            $sql .= " AND p.post_status = 'publish' AND p.post_visibility = 'public'";
        }

        $sql .= " ORDER BY p.$sortColumn DESC";

        $this->setSQL($sql);

        $posts = $this->findAll($data);

        return (empty($posts)) ? [] : $posts;
    }

    /**
     * Find published posts with pagination for API endpoints
     *
     * @param integer $limit
     * @param integer $offset
     * @param string $sortBy
     * @param string $sortOrder
     * @param int|null $author
     * @return array
     */
    public function findPublishedPostsPaginated($limit, $offset, $sortBy = 'ID', $sortOrder = 'DESC', $author = null)
    {
        $allowedColumns = ['ID', 'post_date', 'post_title', 'post_modified'];
        $sortColumn = in_array($sortBy, $allowedColumns) ? $sortBy : 'ID';
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                       p.post_title, p.post_slug, p.post_summary, p.post_status,
                       p.post_visibility, p.post_tags, p.post_type, p.comment_status,
                       u.user_login as author_login, u.user_fullname as author_name
                FROM tbl_posts p
                LEFT JOIN tbl_users u ON p.post_author = u.ID
                WHERE p.post_status = 'publish'
                AND p.post_type = 'blog'
                AND p.post_visibility = 'public'";

        $data = [];

        if ($author !== null) {
            $sql .= " AND p.post_author = ?";
            $data[] = (int)$author;
        }

        $sql .= " ORDER BY p.$sortColumn $sortDir";
        $sql .= " LIMIT ? OFFSET ?";
        $data[] = (int)$limit;
        $data[] = (int)$offset;

        $this->setSQL($sql);

        $posts = $this->findAll($data);

        return empty($posts) ? [] : $posts;
    }

    /**
     * Count published posts
     *
     * @param int|null $author
     * @return integer
     */
    public function countPublishedPosts($author = null)
    {
        $sql = "SELECT COUNT(*) as total FROM tbl_posts
                WHERE post_status = 'publish'
                AND post_type = 'blog'
                AND post_visibility = 'public'";

        $data = [];

        if ($author !== null) {
            $sql .= " AND post_author = ?";
            $data[] = (int)$author;
        }

        $this->setSQL($sql);

        $result = $this->findRow($data);

        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Find a single published post by ID
     *
     * @param integer $postId
     * @return array|false
     */
    public function findPublishedPostById($postId)
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
                AND p.post_status = 'publish'
                AND p.post_visibility = 'public'";

        $this->setSQL($sql);

        $result = $this->findRow([(int)$postId]);

        return empty($result) ? false : $result;
    }

    /**
     * findPost()
     *
     * Retrieving a single post records by it's Id
     *
     * @param integer $ID
     * @param object $sanitize
     * @param integer|null $author
     * @param bool $onlyPublished
     * @return boolean|array|object
     *
     */
    public function findPost($ID, $sanitize, $author = null, $onlyPublished = true)
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
            $data[] = (int)$author;
        }

        if ($onlyPublished) {
            $sql .= " AND post_status = 'publish' AND post_visibility = 'public'";
        }

        $this->setSQL($sql);

        $postDetail = $this->findRow($data);

        return (empty($postDetail)) ? false : $postDetail;
    }

    /**
     * createPost
     *
     * insert new post record
     *
     * @param array $bind
     * @param integer $topicId
     *
     */
    public function createPost($bind, $topicId): int
    {

        $this->setSQL("SET SQL_MODE='ALLOW_INVALID_DATE'");

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

        $postId = $this->lastId();

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }

        if ((is_array($topicId)) && (!empty($postId))) {
            foreach ($_POST['catID'] as $topic_id) {
                $this->create("tbl_post_topic", [
                  'post_id' => $postId,
                  'topic_id' => $topic_id]);
            }

            return $postId;
        }

        $this->create("tbl_post_topic", [
          'post_id' => $postId,
          'topic_id' => $topicId]);

        return $postId;
    }

    /**
     * updatePost
     *
     * updating an existing post record
     *
     * @param object $sanitize
     * @param array $bind
     * @param integer $ID
     * @param integer $topicId
     *
     */
    public function updatePost($sanitize, $bind, $ID, $topicId): void
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

            if ((is_array($topicId)) && (isset($_POST['catID']))) {
                foreach ($_POST['catID'] as $topic_id) {
                    $this->create("tbl_post_topic", [
                        'post_id' => $cleanId,
                        'topic_id' => $topic_id
                    ]);
                }
            }

            $this->callCommit();

            if (function_exists('page_cache_clear')) {
                page_cache_clear();
            }
        } catch (DbException $e) {
            $this->callRollBack();
            $this->error = (string)LogError::setStatusCode(http_response_code(500));
            LogError::exceptionHandler($e);
        } catch (\Throwable $th) {
            $this->callRollBack();
            $this->error = (string)LogError::setStatusCode(http_response_code(500));
            LogError::exceptionHandler($th);
        }
    }

    /**
     * DeletePost
     *
     * @param integer $ID
     * @param object $sanitize
     *
     */
    public function deletePost($ID, $sanitize): void
    {
        $cleanId = $this->filteringId($sanitize, (string)$ID, 'sql');
        $this->deleteRecord("tbl_posts", ['ID' => $cleanId]);

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }
    }

    /**
     * Anonymize post author info
     * Used for GDPR data deletion (Right to be Forgotten)
     *
     * @param int $authorId
     * @return bool
     */
    public function anonymizePostAuthor($authorId)
    {
        $anonymousAuthor = 1;

        $sql = "UPDATE tbl_posts SET 
         post_author = ?
         WHERE post_author = ?";

        $this->setSQL($sql);
        $this->dbc->dbQuery($sql, [$anonymousAuthor, (int)$authorId]);

        return true;
    }

    /**
     * checkPostId
     *
     * @param integer $ID
     * @param object $sanitize
     * @return numeric
     *
     */
    public function checkPostId($ID, $sanitize)
    {
        $sql = "SELECT ID FROM tbl_posts WHERE ID = ? AND post_type = 'blog'";
        $idsanitized = $this->filteringId($sanitize, (string)$ID, 'sql');
        $this->setSQL($sql);
        $stmt = $this->checkCountValue([$idsanitized]);
        return $stmt > 0;
    }

    /**
     * Drop down post status
     * set post status
     *
     * @param string $selected
     *
     */
    public function dropDownPostStatus($selected = "")
    {

        $name = 'post_status';

        $posts_status = array('publish' => 'Publish', 'draft' => 'Draft');

        if ($selected !== '') {
            $this->selected = $selected;
        }

        return dropdown($name, $posts_status, $this->selected);
    }

    /**
     * Drop down Comment Status
     * set comment status
     *
     * @param string $name
     *
     */
    public function dropDownCommentStatus($selected = "")
    {

        $name = 'comment_status';

        $comment_status = array('open' => 'Open', 'closed' => 'Closed');

        if ($selected !== '') {
            $this->selected = $selected;
        }

        return dropdown($name, $comment_status, $this->selected);
    }

    /**
     * dropDownVisibility
     *
     * @param string $selected
     *
     */
    public function dropDownVisibility($selected = null, $postId = null)
    {

        $dropdown = '';

        $name = "visibility";

        $dropdown .= '<div class="form-group">';
        $dropdown .= '<label for="visibility">Post visibility</label>';
        $dropdown .= '<select name="' . $name . '" class="form-control" onchange="checkVisibilitySelection();" id="visibility.system">' . PHP_EOL;

        $this->selected = $selected;

        $visibility_list = ['public' => 'Public', 'private' => 'Private', 'protected' => 'Protected'];

        foreach ($visibility_list as $key => $visibility) {
            $select = $this->selected === $key ? ' selected' : '';

            $dropdown .= '<option value="' . $key . '"' . $select . '>' . $visibility . '</option>' . PHP_EOL;
        }

        $dropdown .= '</select>' . PHP_EOL;

        if (!is_null($postId)) {
            $idsanitized = sanitizer($postId, 'sql');
            $grab_post = medoo_column_where('tbl_posts', ['post_visibility', 'post_password'], ['ID' => $idsanitized]);

            $post_visibility = isset($grab_post['post_visibility']) ? safe_html($grab_post['post_visibility']) : "";
            $post_pwd = isset($grab_post['post_password']) ? safe_html($grab_post['post_password']) : "";

            $dropdown .= '<div id="' . $post_visibility . '" style="display:inline">';
            $dropdown .= '<br>';
            $dropdown .= '<label for="protected">Password:</label>';
            $dropdown .= '<input type="password" class="form-control" name="post_password" value="' . $post_pwd . '" placeholder="Use a secure password">';
            $dropdown .= '<p class="help-block">Protected with a password you choose. Only those with the password can view this post.</p>';
            $dropdown .= '</div>';
            $dropdown .= '</div>';
            $dropdown .= '<script>';
            $dropdown .= 'function checkVisibilitySelection() {' . PHP_EOL;
            $dropdown .= 'a = document.getElementById("visibility.system");' . PHP_EOL;
            $dropdown .= 'if (a.value == "protected")' . PHP_EOL;
            $dropdown .= 'document.getElementById("protected").setAttribute("style", "display:inline");' . PHP_EOL;
            $dropdown .= 'else' . PHP_EOL;
            $dropdown .= 'document.getElementById("protected").setAttribute("style", "display:none");' . PHP_EOL;
            $dropdown .= 'return a.value;' . PHP_EOL;
            $dropdown .= '}' . PHP_EOL;
            $dropdown .= '</script>';

            return $dropdown;
        }

        $dropdown .= '<div id="protected" style="display:none">';
        $dropdown .= '<br />';
        $dropdown .= '<label for="protected">Password:</label>';
        $dropdown .= '<input type="password" class="form-control" name="post_password" value="" placeholder="Use a secure password">';
        $dropdown .= '<p class="help-block">Protected with a password you choose. Only those with the password can view this post.</p>';
        $dropdown .= '</div>';
        $dropdown .= '</div>';
        $dropdown .= '<script>';
        $dropdown .= 'function checkVisibilitySelection() {' . PHP_EOL;
        $dropdown .= 'a = document.getElementById("visibility.system");' . PHP_EOL;
        $dropdown .= 'if (a.value == "protected")' . PHP_EOL;
        $dropdown .= 'document.getElementById("protected").setAttribute("style", "display:inline");' . PHP_EOL;
        $dropdown .= 'else' . PHP_EOL;
        $dropdown .= 'document.getElementById("protected").setAttribute("style", "display:none");' . PHP_EOL;
        $dropdown .= 'return a.value;' . PHP_EOL;
        $dropdown .= '}' . PHP_EOL;
        $dropdown .= '</script>';

        return $dropdown;
    }

    /**
     * Total posts records
     *
     * @param array $data
     * @return numeric
     *
     */
    public function totalPostRecords(array $data = []): ?int
    {

        $sql = "SELECT ID FROM tbl_posts WHERE post_type = 'blog'";

        if (!empty($data)) {
            $sql = "SELECT ID FROM tbl_posts WHERE post_author = ? AND post_type = 'blog'";
        }

        $this->setSQL($sql);

        return $this->checkCountValue($data) ?? 0;
    }

    /**
     * Drop down locale
     *
     * @param string $selected
     * @return string
     *
     */
    public function dropDownLocale($selected = "")
    {
        $name = 'post_locale';

        $locales = [
          'en' => 'English',
          'es' => 'Spanish',
          'fr' => 'French',
          'de' => 'German',
          'it' => 'Italian',
          'pt' => 'Portuguese',
          'ru' => 'Russian',
          'zh' => 'Chinese',
          'ja' => 'Japanese',
          'ko' => 'Korean',
          'ar' => 'Arabic',
          'hi' => 'Hindi',
          'id' => 'Indonesian',
          'ms' => 'Malay',
          'tr' => 'Turkish',
          'nl' => 'Dutch',
          'pl' => 'Polish',
          'vi' => 'Vietnamese',
          'th' => 'Thai',
          'he' => 'Hebrew'
        ];

        if ($selected !== '') {
            $this->selected = $selected;
        }

        return dropdown($name, $locales, $this->selected);
    }

    /**
     * Get distinct year-month combinations with post counts for archive index.
     *
     * @return array
     */
    public function findArchiveIndex()
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
     */
    public function findPostsByYear($year, $limit, $offset, $sortBy = 'ID', $sortOrder = 'DESC')
    {
        $allowedColumns = ['ID', 'post_date', 'post_title', 'post_modified'];
        $sortColumn = in_array($sortBy, $allowedColumns) ? $sortBy : 'ID';
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                       p.post_title, p.post_slug, p.post_summary, p.post_status,
                       p.post_visibility, p.post_tags, p.post_type, p.comment_status,
                       u.user_login as author_login, u.user_fullname as author_name
                FROM tbl_posts p
                LEFT JOIN tbl_users u ON p.post_author = u.ID
                WHERE YEAR(p.post_date) = ?
                AND p.post_status = 'publish'
                AND p.post_type = 'blog'
                AND p.post_visibility = 'public'
                ORDER BY p.$sortColumn $sortDir
                LIMIT ? OFFSET ?";

        $this->setSQL($sql);
        $posts = $this->findAll([(int)$year, (int)$limit, (int)$offset]);

        return empty($posts) ? [] : $posts;
    }

    /**
     * Count published posts for a given year.
     *
     * @param int $year
     * @return int
     */
    public function countPostsByYear($year)
    {
        $sql = "SELECT COUNT(*) as total
                FROM tbl_posts
                WHERE YEAR(post_date) = ?
                AND post_status = 'publish'
                AND post_type = 'blog'
                AND post_visibility = 'public'";

        $this->setSQL($sql);
        $result = $this->findRow([(int)$year]);

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
     */
    public function findPostsByYearMonth($year, $month, $limit, $offset, $sortBy = 'ID', $sortOrder = 'DESC')
    {
        $allowedColumns = ['ID', 'post_date', 'post_title', 'post_modified'];
        $sortColumn = in_array($sortBy, $allowedColumns) ? $sortBy : 'ID';
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                       p.post_title, p.post_slug, p.post_summary, p.post_status,
                       p.post_visibility, p.post_tags, p.post_type, p.comment_status,
                       u.user_login as author_login, u.user_fullname as author_name
                FROM tbl_posts p
                LEFT JOIN tbl_users u ON p.post_author = u.ID
                WHERE YEAR(p.post_date) = ?
                AND MONTH(p.post_date) = ?
                AND p.post_status = 'publish'
                AND p.post_type = 'blog'
                AND p.post_visibility = 'public'
                ORDER BY p.$sortColumn $sortDir
                LIMIT ? OFFSET ?";

        $this->setSQL($sql);
        $posts = $this->findAll([(int)$year, (int)$month, (int)$limit, (int)$offset]);

        return empty($posts) ? [] : $posts;
    }

    /**
     * Count published posts for a given year and month.
     *
     * @param int $year
     * @param int $month
     * @return int
     */
    public function countPostsByYearMonth($year, $month)
    {
        $sql = "SELECT COUNT(*) as total
                FROM tbl_posts
                WHERE YEAR(post_date) = ?
                AND MONTH(post_date) = ?
                AND post_status = 'publish'
                AND post_type = 'blog'
                AND post_visibility = 'public'";

        $this->setSQL($sql);
        $result = $this->findRow([(int)$year, (int)$month]);

        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Search posts with LIKE for API query endpoint.
     *
     * @param string $keyword
     * @param string $type 'blog', 'page', or 'all'
     * @param int $limit
     * @return array
     */
    public function searchPostsApi($keyword, $type = 'all', $limit = 50)
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
            return $this->findAll([$likeKeyword, $likeKeyword, (int)$limit]);
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
        $params[] = (int)$limit;
        return $this->findAll($params);
    }

    /**
     * Find topics/categories attached to a post.
     *
     * @param int $postId
     * @return array
     */
    public function findTopicsByPostId($postId)
    {
        $sql = "SELECT t.ID, t.topic_title, t.topic_slug
                FROM tbl_topics t
                INNER JOIN tbl_post_topic pt ON t.ID = pt.topic_id
                WHERE pt.post_id = ?";

        $this->setSQL($sql);
        $topics = $this->findAll([(int)$postId]);
        return empty($topics) ? [] : $topics;
    }

    /**
     * Delete all topic relationships for a post.
     *
     * @param int $postId
     * @return void
     */
    public function deletePostTopics($postId)
    {
        $this->deleteRecord("tbl_post_topic", ['post_id' => (int)$postId]);
    }

    /**
     * Delete all comments for a post.
     *
     * @param int $postId
     * @return void
     */
    public function deletePostComments($postId)
    {
        $this->deleteRecord("tbl_comments", ['comment_post_id' => (int)$postId]);
    }

    /**
     * Insert a post with the given data and return the new ID.
     *
     * @param array $data Column => value pairs
     * @return int
     */
    public function insertPostApi(array $data)
    {
        $this->create("tbl_posts", $data);
        return $this->lastId();
    }

    /**
     * Update specific post fields.
     *
     * @param int $postId
     * @param array $data Column => value pairs
     * @return void
     */
    public function updatePostApi($postId, array $data)
    {
        $this->modify("tbl_posts", $data, ['ID' => (int)$postId]);
    }
}
