<?php

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
     * findPost()
     *
     * Retrieving a single post records by it's Id
     *
     * @param integer $postId
     * @param object $sanitize
     * @param integer|null $author
     * @param bool $onlyPublished
     * @return boolean|array|object
     *
     */
    public function findPost($id, $sanitize, $author = null, $onlyPublished = true)
    {

        $idsanitized = $this->filteringId($sanitize, $id, 'sql');

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
     * @param string $tagId
     *
     */
    public function createPost($bind, $topicId)
    {

        $this->setSQL("SET SQL_MODE='ALLOW_INVALID_DATE'");

        if (!empty($bind['media_id'])) {
            $this->create("tbl_posts", [
                'media_id' => $bind['media_id'],
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
                ]);
        } else {
            $this->create("tbl_posts", [
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
            ]);
        }

        $postId = $this->lastId();

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }

        if ((is_array($topicId)) && (!empty($postId))) {
            foreach ($_POST['catID'] as $topicId) {
                $this->create("tbl_post_topic", [
                  'post_id' => $postId,
                  'topic_id' => $topicId]);
            }
        } else {
            $this->create("tbl_post_topic", [
              'post_id' => $postId,
              'topic_id' => $topicId]);
        }

        return $postId;
    }

    /**
     * updatePost
     *
     * updating an existing post record
     *
     * @param array $bind
     * @param integer $id
     * @param integer $topicId
     *
     */
    public function updatePost($sanitize, $bind, $ID, $topicId)
    {

        $cleanId = $this->filteringId($sanitize, $ID, 'sql');

        try {
            // transaction
            $this->callTransaction();

            // Build update data - only include password/passphrase if not empty
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
            
            // Only update password and passphrase if they're not empty
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

            // delete all post_topic by post_id
            $post_id = isset($ID) ? purify_dirty_html((int)$ID) : "";

            $this->deleteRecord("tbl_post_topic", ['post_id' => $post_id]);

            if ((is_array($topicId)) && (isset($_POST['catID']))) {
                foreach ($_POST['catID'] as $topicId) {
                    $this->create("tbl_post_topic", [
                        'post_id' => $cleanId,
                        'topic_id' => $topicId
                    ]);
                }
            }

            $this->callCommit();

            if (function_exists('page_cache_clear')) {
                page_cache_clear();
            }
        } catch (\Throwable $th) {
            $this->callRollBack();
            $this->error = LogError::setStatusCode(http_response_code(500));
            $this->error = LogError::exceptionHandler($th);
        } catch (DbException $e) {
            $this->callRollBack();
            $this->error = LogError::setStatusCode(http_response_code(500));
            $this->error = LogError::exceptionHandler($e);
        }
    }

    /**
     * DeletePost
     *
     * @param integer $id
     * @param object $sanitizing
     *
     */
    public function deletePost($id, $sanitize)
    {
        $cleanId = $this->filteringId($sanitize, $id, 'sql');
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
     * @param integer $id
     * @param object $sanitizing
     * @return numeric
     *
     */
    public function checkPostId($id, $sanitizing)
    {
        $sql = "SELECT ID FROM tbl_posts WHERE ID = ? AND post_type = 'blog'";
        $idsanitized = $this->filteringId($sanitizing, $id, 'sql');
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

        $dropdown = null;

        $name = "visibility";

        $dropdown .= '<div class="form-group">';
        $dropdown .= '<label for="visibility">Post visibility</label>';
        $dropdown .= '<select name="' . $name . '" class="form-control" onchange="checkVisibilitySelection();" id="visibility.system">' . PHP_EOL;

        $this->selected = $selected;

        $visibility_list = ['public' => 'Public', 'private' => 'Private', 'protected' => 'Protected'];

        foreach ($visibility_list as $key => $visibility) {
            $select = $this->selected === $key ? ' selected' : null;

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
        } else {
            $dropdown .= '<div id="protected" style="display:none">';
            $dropdown .= '<br />';
            $dropdown .= '<label for="protected">Password:</label>';
            $dropdown .= '<input type="password" class="form-control" name="post_password" value="" placeholder="Use a secure password">';
            $dropdown .= '<p class="help-block">Protected with a password you choose. Only those with the password can view this post.</p>';
        }

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

        if (!empty($data)) {
            $sql = "SELECT ID FROM tbl_posts WHERE post_author = ? AND post_type = 'blog'";
        } else {
            $sql = "SELECT ID FROM tbl_posts WHERE post_type = 'blog'";
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
}
