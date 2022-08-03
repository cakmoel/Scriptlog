<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * request_path()
 *
 * @return object
 * 
 */
function request_path()
{
  $request_path = new RequestPath();
  return $request_path;
} 

/**
 * initialize_post()
 *
 * @return object
 */
function initialize_post()
{
 $frontPostContent = new PostProviderModel();
 return $frontPostContent;
}

/**
 * initialize_page()
 *
 * @return object
 */
function initialize_page()
{
 $frontPageContent = new PageProviderModel();
 return $frontPageContent;
}

/**
 * initialize_archive
 *
 * @return object
 * 
 */
function initialize_archive()
{
  $frontArchiveContent = new ArchivesProviderModel();
  return $frontArchiveContent;
}

/**
 * initialize_topic()
 *
 * @return object
 */
function initialize_topic()
{
 $frontTopicContent = new TopicProviderModel();
 return $frontTopicContent;
}

/**
 * initialize_tag()
 *
 * @return object
 * 
 */
function initialize_tag()
{
  $frontTagContent = new TagProviderModel();
  return $frontTagContent;
}

/**
 * initialize_gallery()
 *
 * @return object
 * 
 */
function initialize_gallery()
{
$frontGalleries = new GalleryProviderModel();
return $frontGalleries;
}

/**
 * featured_post
 * 
 * retrieving random headlines
 *
 * @category themes function
 * @return array
 * 
 */
function featured_post()
{
  $headlines = FrontContentProvider::frontRandomHeadlines(initialize_post());
  return is_iterable($headlines) ? $headlines : array();
}

/**
 * sticky_page
 * 
 * @category theme function
 * @return mixed
 *
 */
function sticky_page()
{
  $sticky_page = FrontContentProvider::frontRandomStickyPage(initialize_page());
  return is_iterable($sticky_page) ? $sticky_page : array();
}

/**
 * random_posts
 *
 * @category theme function
 * @param int|num $limit
 * @return mixed
 * 
 */
function random_posts($start, $end)
{
  $random_posts = FrontContentProvider::frontRandomPosts($start, $end, initialize_post());
  return is_iterable($random_posts) ? $random_posts : array();
}

/**
 * latest_posts
 *
 * @param int|numeric $position
 * @param int|numeric $limit
 * @return array
 * 
 */
function latest_posts($limit)
{
  $latest_posts = FrontContentProvider::frontLatestPosts($limit, initialize_post());
  return is_iterable($latest_posts) ? $latest_posts : array();
}

/**
 * retrieves_topic()
 *
 * @return string
 * 
 */
function retrieves_topic($postId)
{
 
 $categories = array();

 $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug, tbl_topics.topic_status 
         FROM tbl_topics, tbl_post_topic  
         WHERE tbl_topics.ID = tbl_post_topic.topic_id 
         AND tbl_topics.topic_status = 'Y' 
         AND tbl_post_topic.post_id = '$postId' ";

 $results = db_simple_query($sql);

 foreach ( $results as $result) {

   $categories[] = "<a href='".permalinks($result['ID'])['cat']."'>".$result['topic_title']."</a>";

 }

 return implode("", $categories);

}

/**
 * retrieve_post_topic
 * 
 * @param int|num $postId
 * @return string
 * 
 */
function retrieve_post_topic($postId)
{
  
  $topics = array(); 

  $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug, tbl_topics.topic_status 
          FROM tbl_topics, tbl_post_topic
          WHERE tbl_topics.ID = tbl_post_topic.topic_id 
          AND tbl_topics.topic_status = 'Y' 
          AND tbl_post_topic.post_id = ? ";

  $items = db_prepared_query($sql, [$postId], 'i')->get_result()->fetch_all(MYSQLI_ASSOC);

 foreach ( (array) $items as $item) {

    $topic_id = ( !empty($item['ID']) ? abs((int)$item['ID']) : null);
  
    $topics[] = "<a href='".permalinks($topic_id)['cat']."'>".$item['topic_title']."</a>";
  
 }
  
  return implode("", $topics);

}

/**
 * retrieve_tags()
 * 
 */
function retrieve_tags()
{
  return outputting_tags();
}

function link_tag($postId)
{
 $linkTag = FrontContentProvider::frontLinkTag($postId, initialize_tag());
 return $linkTag;
}

/**
 * link_topic
 *
 * @param num|int $postId
 * @return mixed
 * 
 */
function link_topic($postId)
{
 $linkTopic = FrontContentProvider::frontLinkTopic($postId, initialize_topic());
 return $linkTopic;
}

/**
 * display_galleries
 * 
 * @category theme function
 * @param int|num $start
 * @param int|num $limit
 * 
 */
function display_galleries($start, $limit)
{
$showcase = FrontContentProvider::frontGalleries(initialize_gallery(), $start, $limit);
return is_iterable($showcase) ? $showcase : array();
}

/**
 * retrieve_detail_post
 *
 * @param int $id
 * @return mixed
 */
function retrieve_detail_post($id)
{
  $detail_post = FrontContentProvider::frontPostById($id, initialize_post());
  return is_iterable($detail_post) ? $detail_post : array();
}

/**
 * retrieve_archives()
 *
 * @param array $arguments
 * @return mixed
 * 
 */
function retrieve_archives(array $arguments)
{
  $archives = FrontContentProvider::frontArchivesPublished($arguments, initialize_archive());
  return is_iterable($archives) ? $archives : array();
}

/**
 * nothing_found
 *
 * @return string
 * 
 */
function nothing_found()
{

$site_url = app_info()['app_url'];

$nothing_found = <<<_NOTHING_FOUND

<div class="alert alert-warning" role="alert">
  <h4 class="alert-heading">Whoops !</h4>
  <p>You have not any post yet!</p>
  <hr>
  <p class="mb-0">Please go to <a href="$site_url/admin/login.php">administrator panel</a> to populate your blog.</p>
</div>

_NOTHING_FOUND;

return $nothing_found;

}