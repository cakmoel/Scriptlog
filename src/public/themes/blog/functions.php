<?php
/**
 * request_path()
 *
 * @return object
 * 
 */
function request_path()
{
  return class_exists('RequestPath') ? new RequestPath() : "";
}

/**
 * initialize_post()
 *
 * @return object
 */
function initialize_post()
{
  return class_exists('PostProviderModel') ? new PostProviderModel() : "";
}

/**
 * initialize_page()
 *
 * @return object
 */
function initialize_page()
{
  return class_exists('PageProviderModel') ? new PageProviderModel() : "";
}

/**
 * initialize_comment
 *
 * @return object
 * 
 */
function initialize_comment()
{
  return class_exists('CommentProviderModel') ? new CommentProviderModel() : "";
}

/**
 * initialize_archive
 *
 * @return object
 * 
 */
function initialize_archive()
{
  return class_exists('ArchivesProviderModel') ? new ArchivesProviderModel() : "";
}

/**
 * initialize_topic()
 *
 * @return object
 */
function initialize_topic()
{
  return class_exists('TopicProviderModel') ? new TopicProviderModel() : "";
}

/**
 * initialize_tag()
 *
 * @return object
 * 
 */
function initialize_tag()
{
  return class_exists('TagProviderModel') ? new TagProviderModel() : "";
}

/**
 * initialize_gallery()
 *
 * @return object
 * 
 */
function initialize_gallery()
{
  return (class_exists('GalleryProviderModel')) ? new GalleryProviderModel() : "";
}

/**
 * featured_post()
 * 
 * retrieving random headlines
 *
 * @category themes function
 * @return array
 * 
 */
function featured_post()
{
  $headlines = class_exists('FrontContentProvider') ? FrontContentProvider::frontRandomHeadlines(initialize_post()) : "";
  return is_iterable($headlines) ? $headlines : array();
}

/**
 * sticky_page()
 * 
 * @category theme function
 * @return mixed
 *
 */
function sticky_page()
{
  $sticky_page = class_exists('FrontContentProvider') ? FrontContentProvider::frontRandomStickyPage(initialize_page()) : "";
  return is_iterable($sticky_page) ? $sticky_page : array();
}

/**
 * random_posts()
 *
 * @category theme function
 * @param int|num $limit
 * @return mixed
 * 
 */
function random_posts($start, $end)
{
  $random_posts = class_exists('FrontContentProvider') ? FrontContentProvider::frontRandomPosts($start, $end, initialize_post()) : "";
  return is_iterable($random_posts) ? $random_posts : array();
}

/**
 * latest_posts()
 *
 * @param int|numeric $position
 * @param int|numeric $limit
 * @return array
 * 
 */
function latest_posts($limit, $position = null)
{
  $latest_posts = class_exists('FrontContentProvider') ? FrontContentProvider::frontLatestPosts($limit, initialize_post(), $position) : "";
  return is_iterable($latest_posts) ? $latest_posts : array();
}

/**
 * retrieves_topic_simple()
 *
 * @param int|num $id
 * 
 */
function retrieves_topic_simple($id)
{

  $categories = array();

  $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug
          FROM tbl_topics, tbl_post_topic WHERE tbl_topics.ID = tbl_post_topic.topic_id
          AND tbl_topics.topic_status = 'Y' AND tbl_post_topic.post_id = '$id'";

  $stmt = db_simple_query($sql);

  if ($stmt->num_rows > 0) {

    while ($result = $stmt->fetch_array(MYSQLI_ASSOC)) {

      if (rewrite_status() === 'yes') {
        $permalinks = permalinks($result['topic_slug'])['cat'];
      } else {
        $permalinks = permalinks($result['ID'])['cat'];

      }
      
      $categories[] = "<a href='" .$permalinks. "'>" . $result['topic_title'] . "</a>";

    }

  }
  
  return implode("", $categories);
}

/**
 * retrieves_topic_prepared()
 * 
 * @param int|num $id
 * 
 */
function retrieves_topic_prepared($id)
{

  $topics = null;
  $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug, tbl_topics.topic_status
          FROM tbl_topics, tbl_post_topic
          WHERE tbl_topics.ID = tbl_post_topic.topic_id 
          AND tbl_topics.topic_status = 'Y' 
          AND tbl_post_topic.post_id = ? ";

  $items = db_prepared_query($sql, [$id], 'i')->get_result();
  $count_items = db_num_rows($items);

  if ($count_items > 0) {

    while ($item = $items->fetch_assoc()) {

      $permalinks = ((function_exists('rewrite_status')) && (rewrite_status() === 'yes') ? permalinks($item['topic_slug'])['cat'] : permalinks($item['ID'])['cat']);
      $topics[] = "<a href='" .$permalinks. "'>" . $item['topic_title'] . "</a>";

    }
  
  }

  return implode("", $topics);

}

/**
 * sidebar_topics()
 * 
 * retrieving categories and display it on sidebar
 *
 * @return mixed
 * 
 */
function sidebar_topics()
{
  $sidebar_topics = class_exists('FrontContentProvider') ? FrontContentProvider::frontSidebarTopics(initialize_topic()) : "";
  return is_iterable($sidebar_topics) ? $sidebar_topics : array();
}

/**
 * retrieve_tags()
 * 
 * retrieving tags records and display it on sidebar
 * 
 */
function retrieve_tags()
{
  return (function_exists('outputting_tags')) ? outputting_tags() : "";
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
  return (class_exists('FrontContentProvider')) ? FrontContentProvider::frontLinkTag($id, initialize_tag()) : "";
}

/**
 * link_topic()
 *
 * @param num|int $id
 * @return mixed
 * 
 */
function link_topic($id)
{
  return (class_exists('FrontContentProvider')) ? FrontContentProvider::frontLinkTopic($id, initialize_topic()) : "";
}

/**
 * previous_post()
 *
 * @param int|num $id
 * 
 */
function previous_post($id)
{
  $idsanitized = sanitizer($id, 'sql');

  $html = null;

  $sql = "SELECT ID, post_title, post_slug FROM tbl_posts WHERE ID < '$idsanitized' AND post_status = 'publish' AND post_type = 'blog' ORDER BY ID LIMIT 1";

  $stmt = db_simple_query($sql);

  if ($stmt->num_rows > 0) {

    while ($rows = $stmt->fetch_array(MYSQLI_ASSOC)) {

      $html .= '<a href="' . permalinks($rows['ID'])['post'] . '" class="prev-post text-left d-flex align-items-center">';
      $html .= '<div class="icon prev"><i class="fa fa-angle-left" aria-hidden="true"></i></div>';
      $html .= '<div class="text"><strong class="text-primary">Previous Post </strong>';
      $html .= '<h6>' . escape_html($rows['post_title']) . '</h6>';
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
 * 
 */
function next_post($id)
{
  $idsanitized = sanitizer($id, 'sql');

  $html = null;

  $sql = "SELECT ID, post_title, post_slug FROM tbl_posts WHERE ID > '$idsanitized' AND post_status = 'publish' AND post_type = 'blog' ORDER BY ID LIMIT 1";

  $stmt = db_simple_query($sql);

  if ($stmt->num_rows > 0) {

    while ($rows = $stmt->fetch_array(MYSQLI_ASSOC)) {

      $html .= '<a href="' . permalinks($rows['ID'])['post'] . '"  class="next-post text-right d-flex align-items-center justify-content-end">';
      $html .= '<div class="text"><strong class="text-primary">Next Post </strong>';
      $html .= '<h6>' . escape_html($rows['post_title']) . '</h6>';
      $html .= '</div>';
      $html .= '<div class="icon next"><i class="fa fa-angle-right" aria-hidden="true"></i></div>';
      $html .= '</a>';
    }

    return $html;
  }
}

/**
 * display_galleries()
 * 
 * @category theme function
 * @param int|num $start
 * @param int|num $limit
 * 
 */
function display_galleries($start, $limit)
{
  $showcase = class_exists('FrontContentProvider') ? FrontContentProvider::frontGalleries(initialize_gallery(), $start, $limit) : "";
  return is_iterable($showcase) ? $showcase : array();
}

/**
 * retrieves_posts_published()
 * 
 * retrieving all posts published and display it on blog
 *
 * @return mixed
 * 
 */
function retrieve_blog_posts()
{
  $posts = class_exists('FrontContentProvider') ? FrontContentProvider::frontBlogPosts(initialize_post()) : "";
  return is_iterable($posts) ? $posts : array();
}

/**
 * retrieve_detail_post()
 *
 * @param int $id
 * @return mixed
 * 
 */
function retrieve_detail_post($id)
{
  $detail_post = class_exists('FrontContentProvider') ? FrontContentProvider::frontPostById($id, initialize_post()) : "";
  return is_iterable($detail_post) ? $detail_post : array();
}

/**
 * posts_by_archive
 *
 * retrieving posts by archive requested
 * 
 * @param array $values
 * @return mixed
 * 
 */
function posts_by_archive(array $values)
{
  $archives = class_exists('FrontContentProvider') ? FrontContentProvider::frontPostsByArchive($values, initialize_archive()) : "";
  return is_iterable($archives) ? $archives : array();
}

/**
 * posts_by_tag
 *
 * @param string $tag
 * @return mixed
 * 
 */
function posts_by_tag($tag)
{
  $tags = class_exists('FrontContentProvider') ? FrontContentProvider::frontPostsByTag($tag, initialize_tag()) : "";
  return is_iterable($tags) ? $tags : array();
}

/**
 * posts_by_tag()
 * 
 * Full-Text searching for posts based on tag requested
 *
 * @param string $tag
 * @return mixed
 * 
 */
function searching_by_tag($tag)
{
  $tags = class_exists('FrontHelper') ? FrontHelper::simpleSearchingTag($tag) : "";
  return is_iterable($tags) ? $tags : array();
}

/**
 * posts_by_category
 *
 * @param string|num|int $category
 * @param string $rewrite
 * @return mixed
 */
function posts_by_category($topicId)
{

  $entries = FrontContentProvider::frontPostsByTopic($topicId, initialize_topic())['entries'];
  $pagination = FrontContentProvider::frontPostsByTopic($topicId, initialize_topic())['pagination'];

  return is_iterable($entries) ? array('entries' => $entries, 'pagination' => $pagination) : array();

}

/**
 * retrieve_archives()
 *
 * retrieving list of archives and display it on sidebar theme
 * 
 * @return mixed
 * 
 */
function retrieve_archives()
{
  $archives = class_exists('FrontContentProvider') ? FrontContentProvider::frontSidebarArchives(initialize_archive()) : "";
  return is_iterable($archives) ? $archives : array();
}

/**
 * retrieve_page
 *
 * @param int|string|numeric $arg
 * @param string $rewrite
 * @return mixed
 * 
 */
function retrieve_page($arg, $rewrite)
{
  if ($rewrite == 'no') {
    $page = class_exists('FrontContentProvider') ? FrontContentProvider::frontPageById($arg, initialize_page()) : ""; 
    return is_iterable($page) ? $page : [];
  } else {
    $page = class_exists('FrontContentProvider') ? FrontContentProvider::frontPageBySlug($arg, initialize_page()) : "";
    return is_iterable($page) ? $page : [];
  }
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
  $comments = class_exists('FrontContentProvider') ? FrontContentProvider::frontCommentsByPost($id, initialize_comment()) : "";
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
  $sql = "SELECT COUNT(1) AS comment_count FROM tbl_comments WHERE comment_post_id = ? AND comment_status = 'approved'";
  $result = db_prepared_query($sql, [$id], "i")->get_result();
  $row = $result->fetch_assoc();
  
  if (isset($row['comment_count'])) {
    return $row['comment_count'];
  }
  
}

/**
 * block_csrf
 * 
 * generating string token 
 * @return string
 * 
 */
function block_csrf()
{
  return (function_exists('generate_form_token')) ? generate_form_token('comment_form', 32) : "";
}

/**
 * front_navigation
 *
 * @param int|num| $parent
 * @param array $menu
 * 
 */
function front_navigation($parent, $menu)
{
  $html = "";

  if (isset($menu['parents'][$parent])) {

    foreach ($menu['parents'][$parent] as $itemId) {

      if (!isset($menu['parents'][$itemId])) {
        $html .= "<li><a  href='" . $menu['items'][$itemId]['menu_link'] . "'>" . $menu['items'][$itemId]['menu_label'] . "</a></li>";
      }
      if (isset($menu['parents'][$itemId])) {
        $html .= "<li class='dropdown'><a class='dropdown-toggle' data-toggle='dropdown' href='" . $menu['items'][$itemId]['menu_link'] . "'>" . $menu['items'][$itemId]['menu_label'] . "</a>";
        $html .= '<ul class="dropdown-menu">';
        $html .= front_navigation($itemId, $menu);
        $html .= '</ul>';
        $html .= "</li>";
      }
    }
  }

  return $html;
}

/**
 * retrieve_site_url
 *
 * @return mixed
 * 
 */
function retrieve_site_url()
{
  $config_file = read_config(invoke_config());
  return isset($config_file['app']['url']) ? $config_file['app']['url'] : "";
}

/**
 * nothing_found
 *
 * @return string
 * 
 */
function nothing_found()
{

  $site_url = function_exists('app_url') ? app_url() . "/admin/login.php" : "";

  return <<<_NOTHING_FOUND

<div class="alert alert-warning" role="alert">
  <h4 class="alert-heading">Whoops !</h4>
  <p>I haven't posted to my blog yet!</p>
  <hr>
  <p class="mb-0">Please go to <a href="$site_url" target="_blank" rel="noopener noreferrer" title="administrator panel">administrator panel</a> to populate your blog.</p>
</div>

_NOTHING_FOUND;
}
