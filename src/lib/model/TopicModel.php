<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class TopicProviderModel extends Dao
 *
 * @category Model class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 *
 */
class TopicModel extends BaseModel
{
    /**
     * linkPosts
     * alias for link property from BaseModel
     *
     * @var object
     */
    private $linkPosts;

    /**
     * getActiveTopicsOnSidebar
     *
     * retrieve all active topics and display it on sidebar themes
     *
     * @return array
     *
     */
    public function getActiveTopicsOnSidebar()
    {

        $sql = "SELECT t.ID, t.topic_title, t.topic_slug, t.topic_status, 
                COUNT(p.ID) AS total_posts
         FROM  
             tbl_topics t, 
             tbl_posts p, tbl_post_topic pt  
         WHERE t.ID = pt.topic_id AND p.ID = pt.post_id
         AND t.topic_status = 'Y'
         GROUP BY t.ID, t.topic_title";

        $this->setSQL($sql);

        $active_topics = $this->findAll();

        return (empty($active_topics)) ?: $active_topics;
    }

    /**
     * getTopicBySlug
     *
     * retrieves topic record by it slug record
     *
     * @param string $slug
     * @param PDO::FETCH_ASSOC|PDO::FETCH_OBJECT $fetchMode
     * @return mixed
     *
     */
    public function getTopicBySlug($slug, $fetchMode = null)
    {

        $sql = "SELECT ID, topic_title, topic_slug, topic_status FROM tbl_topics WHERE topic_slug = :topic_slug AND topic_status = 'Y'";

        $slug_sanitized = Sanitize::severeSanitizer($slug);

        $this->setSQL($sql);

        $topicBySlug = is_null($fetchMode) ? $this->findRow([':topic_slug' => $slug_sanitized]) : $this->findRow([':topic_slug' => $slug_sanitized], $fetchMode);

        return (empty($topicBySlug)) ?: $topicBySlug;
    }

    /**
     * getTopicById
     *
     * @param int|num $id
     * @param object $sanitize
     * @param PDO::FETCH_ASSOC|PDO::FETCH_OBJECT $fetchMode
     * @return mixed
     */
    public function getTopicById($id, $fetchMode = null)
    {
        $sql = "SELECT ID, topic_title, topic_slug, topic_status FROM tbl_topics WHERE ID = :topic_id AND topic_status = 'Y'";

        $idsanitized = Sanitize::severeSanitizer($id);

        $this->setSQL($sql);

        $topicById = (is_null($fetchMode)) ? $this->findRow([':topic_id' => $idsanitized]) : $this->findRow([':topic_id' => $idsanitized], $fetchMode);

        return (empty($topicById)) ?: $topicById;
    }

    /**
     * getLinkTopic
     *
     * @param int $postId
     * @param object $sanitize
     * @param PDO::FETCH_ASSOC|PDO::FETCH_OBJECT $fetchMode
     * @return array
     *
     */
    public function getLinkTopic($postId, $sanitize)
    {

        $link = array();

        $idsanitized = $this->filteringId($sanitize, $postId, 'sql');

        $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, 
               tbl_topics.topic_slug, tbl_topics.topic_status
        FROM tbl_topics, tbl_post_topic 
        WHERE tbl_topics.ID = tbl_post_topic.topic_id
        AND tbl_topics.topic_status = 'Y' 
        AND tbl_post_topic.post_id = :post_id ";

        $this->setSQL($sql);

        $data = array(':post_id' => $idsanitized);

        $topics =  $this->findAll($data);

        foreach ((array)$topics as $topic) {
            $link[] = '<a href="' . permalinks($topic['ID'])['cat'] . '" title="' . escape_html($topic['topic_title']) . '">' . escape_html($topic['topic_title']) . '</a>';
        }

        return implode("", $link);
    }

    /**
     * getAllPublishedPostsByTopic
     *
     * retrieves all posts published based on topic
     * requested and display it on category section
     *
     * @param int $topicId
     * @param object $sanitize
     * @param Paginator $perPage
     * @return array
     *
     */
    public function getPostsPublishedByTopic($topicId, $sanitize, Paginator $perPage)
    {

        $entries = [];

        $this->linkPosts = $perPage;

        // get number records
        $count_topic = "SELECT tbl_posts.ID 
                FROM tbl_posts, tbl_post_topic 
                WHERE tbl_posts.ID = tbl_post_topic.post_id AND tbl_post_topic.topic_id = :topicId";

        $this->setSQL($count_topic);

        $this->linkPosts->set_total($this->checkCountValue([':topicId' => $topicId]));

        $sql = "SELECT p.ID, p.media_id, p.post_author, 
        p.post_date AS created_at, 
        p.post_modified AS modified_at, 
        p.post_title, p.post_slug, p.post_content, p.post_summary, 
        p.post_keyword, p.post_tags, p.post_status, p.post_sticky, 
        p.post_type, p.comment_status, u.user_fullname, 
        u.user_login, u.user_level,
        m.media_filename, m.media_caption,
        (SELECT COUNT(c.ID) FROM " . $this->table('tbl_comments') . " c WHERE c.comment_post_id = p.ID AND c.comment_status = 'approved') AS total_comments,
        (SELECT GROUP_CONCAT(CONCAT(t.ID, ':', t.topic_title, ':', t.topic_slug) SEPARATOR '|') 
         FROM " . $this->table('tbl_post_topic') . " pt 
         JOIN " . $this->table('tbl_topics') . " t ON pt.topic_id = t.ID 
         WHERE pt.post_id = p.ID AND t.topic_status = 'Y') AS topics_data
    FROM " . $this->table('tbl_posts') . " AS p, " . $this->table('tbl_post_topic') . " AS pt, " . $this->table('tbl_users') . " AS u, " . $this->table('tbl_media') . " AS m
    WHERE p.ID = pt.post_id 
    AND pt.topic_id = :topicId
    AND p.post_author = u.ID
    AND p.post_status = 'publish'
    AND p.post_type = 'blog'
    AND u.user_banned = '0'
    AND p.media_id = m.ID
    ORDER BY p.post_date DESC " . $this->linkPosts->get_limit($sanitize);

        $this->setSQL($sql);

        $entries = $this->findAll([':topicId' => $topicId]);

        $this->pagination = $this->linkPosts->page_links($sanitize);

        return ['entries' => $entries, 'paginationLink' => $this->pagination];
    }
}
