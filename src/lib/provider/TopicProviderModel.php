<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class TopicProviderModel extends Dao
 * 
 * @category Provider class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class TopicProviderModel extends Dao 
{

private $linkPosts;

private $pagination;

public function __construct()
{
 parent::__construct();
}

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
 * @param object $sanitize
 * @param PDO::FETCH_ASSOC|PDO::FETCH_OBJECT $fetchMode
 * @return array
 * 
 */
public function getTopicBySlug($slug, $sanitize, $fetchMode = null)
{

$sql = "SELECT ID, topic_title FROM tbl_topics WHERE topic_slug = :topic_slug AND topic_status = 'Y'";

$slug_sanitized = $this->filteringId($sanitize, $slug, 'xss');

$this->setSQL($sql);

$topicBySlug = (is_null($fetchMode)) ? $this->findRow([':topic_slug' => $slug_sanitized]) : $this->findRow([':topic_slug' => $slug_sanitized], $fetchMode);

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
public function getTopicById($id, $sanitize, $fetchMode = null)
{
    $sql = "SELECT ID, topic_title, topic_slug FROM tbl_topics WHERE ID = :topic_id AND topic_status = 'Y'";
    
    $idsanitized = $this->filteringId($sanitize, $id, 'sql');

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

$data = array(':post_id'=>$idsanitized);

$topics =  $this->findAll($data);

foreach ((array)$topics as $topic) {

 $link[] = '<a href="'.permalinks($topic['ID'])['cat'].'" title="'.escape_html($topic['topic_title']).'">'.escape_html($topic['topic_title']).'</a>';

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
public function getPostsPublishedByTopicId($topicId, $sanitize, Paginator $perPage)
{

$entries = [];

$this->linkPosts = $perPage;

// get number records
$count_topic = "SELECT tbl_posts.ID 
                FROM tbl_posts, tbl_post_topic 
                WHERE tbl_posts.ID = tbl_post_topic.post_id = tbl_post_topic.topic_id = :topicId";

$this->setSQL($count_topic);

$this->linkPosts->set_total($this->checkCountValue([':topicId' => $topicId]));

$sql = "SELECT tbl_posts.ID, tbl_posts.media_id, tbl_posts.post_author, 
        DATE_FORMAT(tbl_posts.post_date, '%e %b %Y at %H:%i') AS created_at, 
        DATE_FORMAT(tbl_posts.post_modified, '%e %b %Y at %H:%i') AS modified_at, 
               tbl_posts.post_title, tbl_posts.post_slug, tbl_posts.post_content, tbl_posts.post_summary, 
               tbl_posts.post_keyword, tbl_posts.post_tags, tbl_posts.post_status, tbl_posts.post_sticky, 
               tbl_posts.post_type, tbl_posts.comment_status, tbl_users.user_fullname, 
               tbl_users.user_login, tbl_users.user_level,
               tbl_media.media_filename, tbl_media.media_caption
    FROM tbl_posts, tbl_post_topic, tbl_users, tbl_media
    WHERE tbl_posts.ID = tbl_post_topic.post_id 
    AND tbl_post_topic.topic_id = :topic_id
    AND tbl_posts.post_author = tbl_users.ID
    AND tbl_posts.post_status = 'publish' 
    AND tbl_posts.post_type = 'blog'
    AND tbl_users.user_banned = '0',
    AND tbl_posts.media_id = tbl_media.ID
    ORDER BY tbl_posts.post_date DESC " . $this->linkPosts->get_limit($sanitize);

$this->setSQL($sql);

$entries = $this->findAll([':topic_id' => $topicId]);

$this->pagination = $this->linkPosts->page_links($sanitize);

return (empty($entries)) ?: ['postsByTopic' => $entries, 'paginationLink' => $this->pagination];

}

public function getPostsPublishedByTopicSlug($topicSlug, $sanitize, Paginator $perPage)
{

 $entries = [];

 $this->linkPosts = $perPage;

 // get number records
$count_topic = "SELECT tbl_posts.ID 
                FROM tbl_posts, tbl_post_topic 
                WHERE tbl_posts.ID = tbl_post_topic.post_id = tbl_post_topic.topic_id = :topicId";


 
}

}