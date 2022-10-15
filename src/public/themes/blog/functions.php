<?php
/**
 * request_path()
 *
 * @return object
 * 
 */
function request_path()
{
  return new RequestPath();
} 

/**
 * initialize_post()
 *
 * @return object
 */
function initialize_post()
{
 return new PostProviderModel();
}

/**
 * initialize_page()
 *
 * @return object
 */
function initialize_page()
{
 return new PageProviderModel();
}

/**
 * initialize_comment
 *
 * @return object
 * 
 */
function initialize_comment()
{
  return new CommentProviderModel();
}

/**
 * initialize_archive
 *
 * @return object
 * 
 */
function initialize_archive()
{
  return new ArchivesProviderModel();
}

/**
 * initialize_topic()
 *
 * @return object
 */
function initialize_topic()
{
  return new TopicProviderModel();
}

/**
 * initialize_tag()
 *
 * @return object
 * 
 */
function initialize_tag()
{
  return new TagProviderModel();
}

/**
 * initialize_gallery()
 *
 * @return object
 * 
 */
function initialize_gallery()
{
return new GalleryProviderModel();
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
function latest_posts($limit, $position = null)
{
  $latest_posts = FrontContentProvider::frontLatestPosts($limit, initialize_post(), $position);
  return is_iterable($latest_posts) ? $latest_posts : array();
}

/**
 * retrieves_topic()
 *
 * @param int|num $id
 */
function retrieves_topic_simple($id)
{
 
 $categories = array();

 $idsanitized = sanitizer($id, 'sql');

 $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug, tbl_topics.topic_status 
         FROM tbl_topics, tbl_post_topic  
         WHERE tbl_topics.ID = tbl_post_topic.topic_id 
         AND tbl_topics.topic_status = 'Y' 
         AND tbl_post_topic.post_id = '$idsanitized' ";

 $results = db_simple_query($sql);

 foreach ( $results as $result) {

   $categories[] = "<a href='".permalinks($result['ID'])['cat']."'>".$result['topic_title']."</a>";

 }

 return implode("", $categories);

}

/**
 * retrieve_post_topic
 * 
 * @param int|num $id
 */
function retrieves_topic_prepared($id)
{
  
  $topics = array(); 

  $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug, tbl_topics.topic_status 
          FROM tbl_topics, tbl_post_topic
          WHERE tbl_topics.ID = tbl_post_topic.topic_id 
          AND tbl_topics.topic_status = 'Y' 
          AND tbl_post_topic.post_id = ? ";

  $items = db_prepared_query($sql, [$id], 'i')->get_result()->fetch_all(MYSQLI_ASSOC);

 foreach ( (array) $items as $item) {

    $topic_id = ( !empty($item['ID']) ? abs((int)$item['ID']) : null);
    $topics[] = "<a href='".permalinks($topic_id)['cat']."'>".$item['topic_title']."</a>";
  }
  
  return implode("", $topics);

}
  
/**
 * sidebarTopics()
 * retrieving categories and display it on sidebar
 *
 * @return mixed
 */
function sidebar_topics()
{
 $sidebar_topics = FrontContentProvider::frontSidebarTopics(initialize_topic());
 return (is_iterable($sidebar_topics)) ? $sidebar_topics : array();
}

/**
 * retrieve_tags()
 * retrieving tags records and display it on sidebar
 */
function retrieve_tags()
{
  return outputting_tags();
}

/**
 * link_tag()
 *
 * @param num|int $id
 * @return mixed
 * 
 */
function link_tag($id)
{
 return FrontContentProvider::frontLinkTag($id, initialize_tag());
}

/**
 * link_topic
 *
 * @param num|int $id
 * @return mixed
 * 
 */
function link_topic($id)
{
 return FrontContentProvider::frontLinkTopic($id, initialize_topic());
}

/**
 * previous_post
 *
 * @param int|number $id
 */
function previous_post($id)
{
 $idsanitized = sanitizer($id, 'sql');

 $html = null;

 $sql = "SELECT ID, post_title, post_slug FROM tbl_posts WHERE ID < '$idsanitized'
         AND post_status = 'publish' AND post_type = 'blog'
         ORDER BY ID LIMIT 1";

 $stmt = db_simple_query($sql);

 if ($stmt->num_rows > 0) {

  while ($rows = $stmt->fetch_array(MYSQLI_ASSOC)) {

    $html .= '<a href="'.permalinks($rows['ID'])['post'].'" class="prev-post text-left d-flex align-items-center">';
    $html .= '<div class="icon prev"><i class="fa fa-angle-left" aria-hidden="true"></i></div>';
    $html .= '<div class="text"><strong class="text-primary">Previous Post </strong>';
    $html .= '<h6>'.escape_html($rows['post_title']).'</h6>';
    $html .= '</div>';
    $html .= '</a>';

  }
   
  return $html;

 }

}

/**
 * next_post()
 *
 * @param int|num $id
 */
function next_post($id)
{
$idsanitized = sanitizer($id,'sql');

$html = null;

$sql = "SELECT ID, post_title, post_slug FROM tbl_posts WHERE ID > '$idsanitized'
AND post_status = 'publish' AND post_type = 'blog'
ORDER BY ID LIMIT 1";

$stmt = db_simple_query($sql);

if ($stmt->num_rows > 0) {

  while ( $rows = $stmt->fetch_array(MYSQLI_ASSOC) ) {

    $html .= '<a href="'.permalinks($rows['ID'])['post'].'"  class="next-post text-right d-flex align-items-center justify-content-end">';
    $html .= '<div class="text"><strong class="text-primary">Next Post </strong>';
    $html .= '<h6>'.escape_html($rows['post_title']).'</h6>';
    $html .= '</div>';
    $html .= '<div class="icon next"><i class="fa fa-angle-right" aria-hidden="true"></i></div>';
    $html .= '</a>';

  }

  return $html;

}

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
 * retrieves_posts_published
 * retrieving all posts published 
 * and display it on blog
 *
 * @return mixed
 */
function retrieves_posts_published()
{
  $posts = FrontContentProvider::frontPostsPublished(initialize_post());
  return is_iterable($posts) ? $posts : array();
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
 * posts_by_archive
 *
 * retrieving posts by archive requested
 * @param array $values
 * @return mixed
 * 
 */
function posts_by_archive(array $values)
{
  $archives = FrontContentProvider::frontPostsByArchive($values, initialize_archive());
  return is_iterable($archives) ? $archives : array();
}

/**
 * retrieve_archives()
 *
 * retrieving list of archives 
 * and display it on sidebar theme
 * 
 * @return mixed
 */
function retrieve_archives()
{
  $archives = FrontContentProvider::frontSidebarArchives(initialize_archive());
  return is_iterable($archives) ? $archives : array();
}

/**
 * comments_by_post
 *
 * retrieves list of comments by detail post requested
 * 
 * @param int|num $id
 *
 */
function comments_by_post($id)
{
  $comments = FrontContentProvider::frontCommentsByPost($id, initialize_comment());
  return is_iterable($comments) ? $comments : array();
}

/**
 * total_comment
 *
 * @param int| $id
 * 
 */
function total_comment($id)
{
 return FrontContentProvider::frontTotalCommentByPost($id, initialize_comment());
}

/**
 * block_csrf
 * 
 * generating string token 
 * 
 * @return string
 */
function block_csrf()
{
  return generate_form_token('comment_form', 40);
}

function front_navigation()
{
  
}