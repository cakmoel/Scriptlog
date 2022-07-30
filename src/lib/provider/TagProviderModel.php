<?php
/**
 * class TagProviderModel extends Dao
 * 
 * @category Provider Class
 * 
 * 
 */
class TagProviderModel extends Dao
{

public function __construct()
{
  parent::__construct();
}

/**
 * getTagCloud()
 * 
 * @see https://www.longren.io/how-to-build-a-tag-cloud-with-php-mysql-and-css/
 * @see http://howto.philippkeller.com/2005/04/24/Tags-Database-schemas/
 * @see http://howto.philippkeller.com/2005/06/19/Tagsystems-performance-tests/
 * @see https://snook.ca/archives/php/how_i_added_tag
 * 
 */
public function getTagCloud($postId, $sanitize) 
{

$idsanitized = $this->filteringId($sanitize, $postId, 'sql');

$sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_status, COUNT(tbl_posts.ID) 
        AS totalPosts FROM tbl_posts, tbl_topics, tbl_post_topic 
        WHERE tbl_topics.ID = tbl_post_topic.topic_id
        AND tbl_topics.topic_status = 'Y'
        AND tbl_post_topic.post_id = :post_id";

$this->setSQL($sql);

$data = array(':post_id' => $idsanitized);

$tags = $this->findAll($data);

foreach ((array)$tags as $tag) {

  
  
}

}

}