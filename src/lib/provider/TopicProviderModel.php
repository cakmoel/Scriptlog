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
 
 $sql = "SELECT ID, topic_title, topic_slug, topic_status FROM  tbl_topics 
         WHERE topic_status = 'Y' ORDER BY topic_title DESC";

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
 * getPostTopic
 *
 * @param int $postId
 * @param object $sanitize
 * @param PDO::FETCH_ASSOC|PDO::FETCH_OBJECT $fetchMode
 * @return array
 * 
 */
public function createLinkTopic($postId, $sanitize)
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
 * getAllPublishPostsByTopic
 *
 * retrieves all posts published based topic
 * 
 * @param int $topicId
 * @param object $sanitize
 * @param Paginator $perPage
 * @return array
 * 
 */
public function getAllPublishedPostsByTopic($topicId, $sanitize, Paginator $perPage)
{

$pagination = null;

$this->linkPosts = $perPage;

// get number records
$count_topic = "SELECT tbl_posts.ID 
                FROM tbl_posts, tbl_post_topic 
                WHERE tbl_posts.ID = tbl_post_topic.post_id = tbl_post_topic.topic_id = :topicId";

$this->setSQL($count_topic);

$this->linkPosts->set_total($this->checkCountValue([':topicId' => $topicId]));

$sql = "SELECT tbl_posts.ID, tbl_posts.post_author, tbl_posts.post_date, tbl_posts.post_modified, 
               tbl_posts.post_title, tbl_posts.post_slug, tbl_posts.post_content, tbl_posts.post_summary, 
               tbl_posts.post_keyword, tbl_posts.post_tags, tbl_posts.post_status, tbl_posts.post_sticky, 
               tbl_posts.post_type, tbl_posts.comment_status, tbl_users.user_fullname, 
               tbl_users.user_login, tbl_users.user_level
    FROM tbl_posts, tbl_post_topic, tbl_users
    WHERE tbl_posts.ID = tbl_post_topic.post_id 
    AND tbl_post_topic.topic_id = :topic_id
    AND tbl_posts.post_author = tbl_users.ID
    AND tbl_posts.post_status = 'publish' 
    AND tbl_posts.post_type = 'blog'
    AND tbl_users.user_banned = '0' 
    ORDER BY tbl_posts.ID DESC " . $this->linkPosts->get_limit($sanitize);

$this->setSQL($sql);

$postsByTopic = $this->findAll([':topic_id' => $topicId]);

$pagination = $this->linkPosts->page_links($sanitize);

return (empty($postsByTopic)) ?: ['postsByTopic' => $postsByTopic, 'paginationLink' => $pagination];

}

}