<?php
/**
 * FrontHelper Class
 * FrontHelper class will be useful for theme functionality
 * to retrieve some particular content needed on theme layout 
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */
class FrontHelper 
{
  
protected static $sanitize;

protected static $paginator; 

protected static $postDao;  

protected static $pageDao; 

protected static $topicDao;

protected static $navigation;

private static $dispatcher;

const PER_PAGE = 10;

const QUERY_PAGE = 'p';

public static function frontNavigation(MenuDao $navigation)
{

  self::$navigation = $navigation;

  return self::$navigation->findFrontNavigation(find_request()[0]);

}

public static function frontPermalinks($id)
{

  $url = false;

  $idsanitized = self::frontSanitizer(prevent_injection(db_instance()->real_escape_string((int)$id)), 'sql');
  
  if (app_info()['permalink_setting'] == 'yes') {
  
      $query = db_simple_query("SELECT post_slug FROM tbl_posts WHERE ID = '$idsanitized'")->fetch_object();
       
      $url = $query->post_slug;
  
      return $url;
  
  } else {
  
    $url = false;
  
    return $url;
  
  }

}

public static function frontHeadlines($start, $limit)
{

$sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
        p.post_title, p.post_content, p.post_slug, p.post_status, p.post_type,
        m.ID, m.media_filename, m.media_caption, m.media_target, m.media_access
        FROM tbl_posts AS p
        INNER JOIN tbl_media AS m ON p.media_id = m.ID
        WHERE p.post_status = 'publish' AND p.post_type = 'blog'
        AND m.media_target = 'blog' AND m.media_access = 'public'
        ORDER BY p.ID LIMIT ?, ?";

$statement = db_prepared_query($sql, [(int)$start, (int)$limit], 'ii');

$result = get_result($statement);

return $result;
    
}

public static function frontGalleris($start, $limit)
{

$sql = "SELECT ID, media_filename, media_caption FROM tbl_media WHERE media_target = 'gallery'
       ORDER BY ID LIMIT ?, ?";

$statement = db_prepared_query($sql, [(int)$start, (int)$limit], 'ii');

$result = get_result($statement);

return $result;

}

public static function frontNextPost($post_id, PostDao $postDao, Sanitize $sanitize, $fetchMode = null)
{
  self::$postDao = $postDao;
  self::$sanitize = $sanitize;

  return self::$postDao->showNextPost($post_id, self::$sanitize, $fetchMode);

}

public static function frontPrevPost($post_id, PostDao $postDao, Sanitize $sanitize, $fetchMode = null)
{
 self::$postDao = $postDao;
 self::$sanitize = $sanitize;

 return self::$postDao->showPrevPost($post_id, self::$sanitize, $fetchMode);
 
}

public static function frontRecentPosts(PostDao $postDao, Sanitize $sanitize)
{

  self::$postDao = $postDao;
  self::$sanitize = $sanitize;

  return self::$postDao->showPostsPublished(self::frontPaginator(), self::$sanitize);

}

public static function frontTotalPosts(PostDao $postDao)
{
  
  self::$postDao = $postDao;

  return self::$postDao->totalPostRecords();

}

public static function frontReadPost($id, PostDao $postDao, Sanitize $sanitize)
{

 self::$postDao = $postDao;
 self::$sanitize = $sanitize;

 return self::$postDao->showPostById($id, self::$sanitize);

}

public static function frontReadPage($slug, PageDao $pageDao, Sanitize $sanitize)
{

self::$pageDao = $pageDao;
self::$sanitize = $sanitize;

return self::$pageDao->findPageBySlug($slug, self::$sanitize);

}

public static function frontSidebarTopics(TopicDao $topicDao)
{
  
 self::$topicDao = $topicDao;

 return self::$topicDao->showAllActiveTopics();

}

public static function frontSidebarPosts(PostDao $postDao, $status, $start, $limit)
{

  self::$postDao = $postDao;

  return self::$postDao->showPostsOnSidebar($status, $start, $limit);

}

public function frontSidebarArchives()
{

  $sql = "SELECT MONTH(post_data) AS month, YEAR(post_date) AS year FROM tbl_posts GROUP BY month, year ORDER BY month DESC";

  $statement = db_simple_query($sql)->fetch_all(MYSQL_ASSOC);

  return $statement;

}

public static function frontRequestParam(Dispatcher $dispatcher)
{
  
  self::$dispatcher = $dispatcher;

  return self::$dispatcher->findRequestParam();

}

public static function frontRequestPath($args, Dispatcher $dispatcher)
{
  self::$dispatcher = $dispatcher;

  return self::$dispatcher->findRequestPath($args);

}

public static function frontPaginator()
{
  self::$paginator = new Paginator(self::PER_PAGE, self::QUERY_PAGE);

  return self::$paginator;
  
}

private static function frontSanitizer($str, $type)
{
  return sanitizer($str, $type);
}

}