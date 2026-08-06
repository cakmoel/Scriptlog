<?php

namespace Scriptlog\Dao;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class TopicDao extends Dao
 *
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

use Scriptlog\Core\Dao;

class TopicDao extends Dao
{
    /**
     * overrides Dao constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Find All Topics
     *
     * @param integer $position
     * @param integer $limit
     * @param string $orderBy
     * @return mixed
     */
    public function findTopics($orderBy = 'ID')
    {
        $sql = "SELECT ID, topic_title, topic_slug, topic_status, topic_locale FROM tbl_topics ORDER BY '$orderBy' DESC";

        $this->setSQL($sql);

        $topics = $this->findAll([]);

        return (empty($topics)) ?: $topics;
    }

    /**
     * Find active topics with pagination and post count for API
     *
     * @param integer $limit
     * @param integer $offset
     * @param string $sortBy
     * @param string $sortOrder
     * @return array
     */
    public function findActiveTopicsPaginated($limit, $offset, $sortBy = 'ID', $sortOrder = 'DESC')
    {
        $allowedColumns = ['ID', 'topic_title', 'topic_slug'];
        $sortColumn = in_array($sortBy, $allowedColumns) ? $sortBy : 'ID';
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT t.ID, t.topic_title, t.topic_slug, t.topic_status,
                       (SELECT COUNT(*) FROM tbl_post_topic pt
                        INNER JOIN tbl_posts p ON pt.post_id = p.ID
                        WHERE pt.topic_id = t.ID
                        AND p.post_status = 'publish'
                        AND p.post_type = 'blog') as post_count
                FROM tbl_topics t
                WHERE t.topic_status = 'Y'
                ORDER BY t.$sortColumn $sortDir
                LIMIT ? OFFSET ?";

        $this->setSQL($sql);
        $topics = $this->findAll([(int)$limit, (int)$offset]);

        return empty($topics) ? [] : $topics;
    }

    /**
     * Count active topics
     *
     * @return integer
     */
    public function countActiveTopics()
    {
        $sql = "SELECT COUNT(*) as total FROM tbl_topics WHERE topic_status = 'Y'";
        $this->setSQL($sql);
        $result = $this->findRow([]);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Find a single topic with post count
     *
     * @param integer $topicId
     * @return array|false
     */
    public function findTopicWithPostCount($topicId)
    {
        $sql = "SELECT t.*,
                       (SELECT COUNT(*) FROM tbl_post_topic pt
                        INNER JOIN tbl_posts p ON pt.post_id = p.ID
                        WHERE pt.topic_id = t.ID
                        AND p.post_status = 'publish'
                        AND p.post_type = 'blog') as post_count
                FROM tbl_topics t
                WHERE t.ID = ?";

        $this->setSQL($sql);
        $result = $this->findRow([(int)$topicId]);
        return empty($result) ? false : $result;
    }

    /**
     * Find published posts by topic with pagination
     *
     * @param integer $topicId
     * @param integer $limit
     * @param integer $offset
     * @param string $sortBy
     * @param string $sortOrder
     * @return array
     */
    public function findPostsByTopicPaginated($topicId, $limit, $offset, $sortBy = 'ID', $sortOrder = 'DESC')
    {
        $allowedColumns = ['ID', 'post_date', 'post_title', 'post_modified'];
        $sortColumn = in_array($sortBy, $allowedColumns) ? $sortBy : 'ID';
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                       p.post_title, p.post_slug, p.post_summary, p.post_status,
                       p.post_visibility, p.post_tags, p.post_type, p.comment_status,
                       u.user_login as author_login, u.user_fullname as author_name
                FROM tbl_posts p
                INNER JOIN tbl_post_topic pt ON p.ID = pt.post_id
                LEFT JOIN tbl_users u ON p.post_author = u.ID
                WHERE pt.topic_id = ?
                AND p.post_status = 'publish'
                AND p.post_type = 'blog'
                AND p.post_visibility = 'public'
                ORDER BY p.$sortColumn $sortDir
                LIMIT ? OFFSET ?";

        $this->setSQL($sql);
        $posts = $this->findAll([(int)$topicId, (int)$limit, (int)$offset]);
        return empty($posts) ? [] : $posts;
    }

    /**
     * Count published posts by topic
     *
     * @param integer $topicId
     * @return integer
     */
    public function countPostsByTopic($topicId)
    {
        $sql = "SELECT COUNT(*) as total
                FROM tbl_posts p
                INNER JOIN tbl_post_topic pt ON p.ID = pt.post_id
                WHERE pt.topic_id = ?
                AND p.post_status = 'publish'
                AND p.post_type = 'blog'
                AND p.post_visibility = 'public'";

        $this->setSQL($sql);
        $result = $this->findRow([(int)$topicId]);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Find Topic by ID
     *
     * @param integer $topicId
     * @param object $sanitize
     * @param int|null $fetchMode
     * @return mixed
     */
    public function findTopicById($topicId, $sanitize, $fetchMode = null)
    {
        $cleanId = $this->filteringId($sanitize, $topicId, 'sql');

        $sql = "SELECT ID, topic_title, topic_slug, topic_status, topic_locale
		        FROM tbl_topics WHERE ID = ?";

        $this->setSQL($sql);

        $topicById = (is_null($fetchMode)) ? $this->findRow([$cleanId]) : $this->findRow([$cleanId], $fetchMode);

        return (empty($topicById)) ?: $topicById;
    }

    /**
      * findPostTopic
      *
      * @param integer $topicId
      * @param integer $postId
      * @return boolean|array|object
      */
    public function findPostTopic($topicId, $postId)
    {

        $sql = "SELECT topic_id FROM tbl_post_topic WHERE topic_id = ? AND post_id = ?";

        $this->setSQL($sql);

        $post_topic = $this->findRow([$topicId, $postId]);

        return (empty($post_topic)) ?: $post_topic;
    }

    /**
     * Insert a new records
     *
     * @method createCategory
     * @param string $title
     * @param string $slug
     */
    public function createTopic($bind)
    {

        $this->create("tbl_topics", [
            'topic_title' => $bind['topic_title'],
            'topic_slug' => $bind['topic_slug'],
            'topic_locale' => $bind['topic_locale'] ?? 'en'
        ]);

        return $this->lastId();
    }

    /**
     * Update an existing records
     *
     * @param string $title
     * @param string $slug
     * @param string $status
     * @param integer $topicId
     */
    public function updateTopic($sanitize, $bind, $topicId)
    {

        $cleanId = $this->filteringId($sanitize, $topicId, 'sql');

        $this->modify("tbl_topics", [
            'topic_title' => $bind['topic_title'],
            'topic_slug' => $bind['topic_slug'],
            'topic_status' => $bind['topic_status'],
            'topic_locale' => $bind['topic_locale'] ?? 'en'
        ], ["ID" => (int)$cleanId]);
    }

    /**
     * Delete an existing records
     *
     * @param integer $topicId
     * @param string $sanitizing
     */
    public function deleteTopic($topicId, $sanitize)
    {
        $cleanId = $this->filteringId($sanitize, $topicId, 'sql');

        $this->deleteRecord("tbl_topics", ["ID" => (int)$cleanId]);
    }

    /**
     * Set topic
     * post category
     *
     * @param int|string|null $postId
     * @param array $checked
     * @return string
     */
    public function setCheckBoxTopic($postId = null, $checked = null)
    {

        if (is_null($checked)) {
            $checked = "checked='checked'";
        }

        $html = '<div class="form-group">';
        $html .= '<label for="category">Category</label>';

        $items = $this->findTopics('topic_title');

        $checked = "";

        if (empty($postId)) {
            if (!is_array($items)) {
                $html .= '<div class="checkbox">';
                $html .= '<label>';
                $html .= '<input type="checkbox" name="catID" value="0" checked>Uncategorized';
                $html .= '</label>';
                $html .= '</div>';
                $html .= '</div>';

                return $html;
            }

            foreach ($items as $item) {
                $checked = (isset($_POST['catID']) && in_array($item['ID'], $_POST['catID']))
                    ? "checked='checked'"
                    : null;

                $html .= '<div class="checkbox">';
                $html .= '<label>';
                $html .= '<input type="checkbox" name="catID[]" value="' . $item['ID'] . '" ' . $checked . '>' . $item['topic_title'];
                $html .= '</label>';
                $html .= '</div>';
            }

            $html .= '</div>';

            return $html;
        }

        if (is_array($items)) {
            foreach ($items as $item) {
                $post_topic = $this->findPostTopic($item['ID'], (int)$postId);

                $checked = (isset($post_topic['topic_id']) && $post_topic['topic_id'] == $item['ID'])
                    ? "checked='checked'"
                    : null;

                $html .= '<div class="checkbox">';
                $html .= '<label>';
                $html .= '<input type="checkbox" name="catID[]" value="' . $item['ID'] . '" ' . $checked . '>' . $item['topic_title'];
                $html .= '</label>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Check Id'topic
     *
     * @method public checkTopicId()
     * @param integer $id
     * @param object $sanitize
     * @return numeric
     *
     */
    public function checkTopicId($id, $sanitizing)
    {
        $sql = "SELECT ID FROM tbl_topics WHERE ID = ?";
        $cleanId = $this->filteringId($sanitizing, $id, 'sql');
        $this->setSQL($sql);
        $stmt = $this->checkCountValue([$cleanId]);
        return $stmt > 0;
    }

    /**
     * Total topic records
     *
     * @param array $data
     * @return numeric|int|null
     *
     */
    /**
     * Find a topic by slug (for duplicate checking).
     *
     * @param string $slug
     * @return array|false
     */
    public function findTopicBySlug($slug)
    {
        $sql = "SELECT ID FROM tbl_topics WHERE topic_slug = ?";
        $this->setSQL($sql);
        return $this->findRow([$slug]);
    }

    /**
     * Insert a topic with the given data and return the new ID.
     *
     * @param array $data
     * @return int
     */
    public function insertTopicApi(array $data)
    {
        $this->create("tbl_topics", $data);
        return $this->lastId();
    }

    /**
     * Update specific topic fields.
     *
     * @param int $topicId
     * @param array $data
     * @return void
     */
    public function updateTopicApi($topicId, array $data)
    {
        $this->modify("tbl_topics", $data, ['ID' => (int)$topicId]);
    }

    /**
     * Delete topic relationships then the topic itself.
     *
     * @param int $topicId
     * @return void
     */
    public function deleteTopicCascade($topicId)
    {
        $this->deleteRecord("tbl_post_topic", ['topic_id' => (int)$topicId]);
        $this->deleteRecord("tbl_topics", ['ID' => (int)$topicId]);
    }

    public function totalTopicRecords(array $data = []): ?int
    {
        $sql = "SELECT ID FROM tbl_topics";
        $this->setSQL($sql);
        return $this->checkCountValue($data);
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
        $name = 'topic_locale';

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

        return dropdown($name, $locales, $selected);
    }
}
