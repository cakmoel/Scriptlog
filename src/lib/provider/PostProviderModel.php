<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class PostProviderModel extends Dao
 * 
 * @category Provider class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class PostProviderModel extends Dao 
{
 
private $linkPosts;

private $pagination;

public function __construct()
{
  parent::__construct();
}

/**
 * getPostFeeds
 * 
 * Retrieve posts records for sharing post on post feeds
 *
 * @param integer $limit
 * @return void
 */
public function getPostFeeds($limit)
{
  $sql =  "SELECT p.ID, p.media_id, p.post_author,
                  p.post_date, p.post_modified, p.post_title,
                  p.post_slug, p.post_content, p.post_type,
                  p.post_status, p.post_tags, 
                  p.post_sticky, u.user_fullname, u.user_login
            FROM tbl_posts AS p
            INNER JOIN tbl_users AS u ON p.post_author = u.ID
            WHERE p.post_type = 'blog' AND p.post_status = 'publish'
            ORDER BY p.ID DESC LIMIT :limit";

  $data = array(':limit' => $limit);

  $this->setSQL($sql);

  $feeds = $this->findAll($data);

  return (empty($feeds)) ?: $feeds;

}

/**
 * getLatesetPosts
 *
 * retrieves latest posts and display it on homepage
 * 
 * @param int|numeric $position
 * @param int|num $limit
 * @param PDO::FETCH_MODE static $fetchMode = null
 * @return mixed
 * 
 */
public function getLatestPosts($limit)
{

$sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, 
            p.post_title, p.post_slug, p.post_content, p.post_summary, 
            p.post_keyword, p.post_status, p.post_tags, 
            p.post_type, p.comment_status, 
  m.media_filename, m.media_caption, m.media_access, u.user_fullname, u.user_login
FROM tbl_posts AS p 
INNER JOIN tbl_media AS m ON p.media_id = m.ID
INNER JOIN tbl_users AS u ON p.post_author = u.ID
WHERE p.post_status = 'publish'
AND p.post_type = 'blog' 
ORDER BY p.ID DESC LIMIT :limit";

$this->setSQL($sql);

$latestPosts = isset($limit) ? $this->findAll([':limit' => $limit]) : null;

return ( empty($latestPosts) ) ?: $latestPosts;

}

/**
 * getPostById
 * 
 * retrieving detail post record by Id
 *
 * @param int|num $id
 * @param object $sanitize
 * @return boolean|array|object
 *
 */
public function getPostById($id, $sanitize)
{
   
$sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, p.post_title, 
p.post_slug, p.post_content, p.post_summary, p.post_keyword, p.post_status, p.post_sticky, 
p.post_type, p.comment_status, m.media_filename, m.media_caption, m.media_target, m.media_access, 
u.user_login, u.user_fullname
FROM tbl_posts AS p
INNER JOIN tbl_media AS m ON p.media_id = m.ID
INNER JOIN tbl_users AS u ON p.post_author = u.ID
WHERE p.post_status = 'publish'
AND p.post_type = 'blog' AND m.media_target = 'blog'
AND m.media_access = 'public' AND m.media_status = '1' 
AND p.ID = :ID ";

$sanitizeid = $this->filteringId($sanitize, $id, 'sql');

$this->setSQL($sql);

$item = $this->findRow([':ID' => $sanitizeid]);

return (empty($item)) ?: $item;

}

/**
 * getPostBySlug
 * 
 * retrieving post record by slug
 *
 * @param string $slug
 * @return mixed
 *
 */
public function getPostBySlug($slug, $sanitize)
{

  $sql = "SELECT p.ID, p.media_id, p.post_author,
                 p.post_date, p.post_modified, p.post_title,
                 p.post_slug, p.post_content, p.post_summary,
                 p.post_keyword, p.post_status, p.post_sticky, 
                 p.post_type, p.comment_status, 
                 m.media_filename, m.media_caption, m.media_target, m.media_access,
                 u.user_fullname
          FROM tbl_posts AS p
          INNER JOIN tbl_users AS u ON p.post_author = u.ID
          INNER JOIN tbl_media AS m ON p.media_id = m.ID
          WHERE p.post_slug = :slug AND p.post_status = 'publish'
          AND p.post_type = 'blog' AND m.media_target = 'blog'
          AND m.media_access = 'public' AND m.media_status = '1'";

  $this->setSQL($sql);

  $slug_sanitized = $this->filteringId($sanitize, $slug, 'xss');

  $postBySlug = $this->findRow([':slug' => $slug_sanitized]);

  return (empty($postBySlug)) ?: $postBySlug;

}

/**
 * getPostByAuthor
 *
 * retrieving post records based on author
 * 
 * @param string $author
 * @return mixed
 * 
 */
public function getPostByAuthor($author)
{
  
  $sql = "SELECT p.ID, p.media_id, p.post_author,
                 p.post_date, p.post_modified, p.post_title,
                 p.post_slug, p.post_content, p.post_summary,
                 p.post_keyword, 
                 p.post_status, p.post_sticky, 
                 p.post_type, p.comment_status, 
                 m.media_filename, m.media_caption, m.media_target, m.media_access,
                 u.user_fullname, u.user_login
          FROM tbl_posts AS p
          INNER JOIN tbl_user AS u ON p.post_author = u.ID
          INNER JOIN tbl_media AS m ON p.media_id = m.ID
          WHERE u.user_fullname = :author AND p.post_status = 'publish'
          AND p.post_type = 'blog' AND m.media_target = 'blog'
          AND m.media_access = 'public' AND m.media_status = '1'";

  $this->setSQL($sql);

  $postByAuthor = $this->findRow([':author' => $author]);

  return (empty($postByAuthor)) ?: $postByAuthor;

}

/**
 * getPostsPublished
 * 
 * retrieving all records published 
 * and display it on blog section
 *
 * @param Paginator $perPage
 * @param object $sanitize
 * @return boolean|array[]|object[]|string[]
 *
 */
public function getPostsPublished(Paginator $perPage, $sanitize)
{

  $this->linkPosts = $perPage;

  $stmt = $this->dbc->dbQuery("SELECT ID FROM tbl_posts WHERE post_status = 'publish' AND post_type = 'blog'");

  $this->linkPosts->set_total($stmt->rowCount());

  $sql = "SELECT p.ID, p.media_id, p.post_author,
                     p.post_date, p.post_modified, p.post_title,
                     p.post_slug, p.post_content, p.post_summary,
                     p.post_keyword, 
                     p.post_type, p.post_status, p.post_sticky, 
                     u.user_login, u.user_fullname,
                     m.media_filename, m.media_caption
  			FROM tbl_posts AS p
  			INNER JOIN tbl_users AS u ON p.post_author = u.ID
        INNER JOIN tbl_media AS m ON p.media_id = m.ID
  			WHERE p.post_type = 'blog' AND p.post_status = 'publish'
  			ORDER BY p.ID DESC " . $this->linkPosts->get_limit($sanitize);

  $this->setSQL($sql);

  $postsPublished = $this->findAll();

  $this->pagination = $this->linkPosts->page_links($sanitize);

  return (empty($postsPublished)) ?: ['postsPublished' => $postsPublished, 'paginationLink' => $this->pagination];

}

/**
 * getRandomHeadlinesPosts
 * 
 * retrieving random headlines 
 * 
 * @method public getRandomHeadlinesPosts()
 * @param int $start
 * @param int $limit
 * @return mixed
 * 
 */
public function getRandomHeadlines()
{

$sql = "SELECT p.ID, p.media_id, p.post_author,
        p.post_date, p.post_modified, p.post_title,
        p.post_slug, p.post_content, p.post_summary,
        p.post_keyword, p.post_sticky, p.post_type, p.post_status, 
        p.post_tags, u.user_login, u.user_fullname,
        m.media_filename, m.media_caption, m.media_type, m.media_target, m.media_access
FROM tbl_posts AS p
INNER JOIN (SELECT ID FROM tbl_posts ORDER BY RAND() LIMIT 5) AS p2 ON p.ID = p2.ID 
INNER JOIN tbl_users AS u ON p.post_author = u.ID
INNER JOIN tbl_media AS m ON p.media_id = m.ID
WHERE p.post_type = 'blog'
AND m.media_target = 'blog' 
AND p.post_status = 'publish' 
AND p.post_headlines = '1' ";

$this->setSQL($sql);

$headlines = $this->findAll();

return (empty($headlines)) ?: $headlines;

}

/**
 * getRelatedPosts
 * 
 * retrieving related post records
 *
 * @param string $post_title
 * @return mixed
 *
 */
public function getRelatedPosts($post_title)
{

  $sql = "SELECT ID, media_id, post_author, post_date, post_modified,
                 post_title, post_slug, post_content, MATCH(post_title, post_content, post_tags)
                 AGAINST(?) AS score
          FROM tbl_posts WHERE MATCH(post_title, post_content) AGAINTS(?)
          ORDER BY score ASC LIMIT 3";

  $this->setSQL($sql);

  $relatedPosts = $this->findRow([$post_title]);

  return (empty($relatedPosts)) ?: $relatedPosts;

}

/**
 * getRandomPosts
 *
 * retrieving random posts and display it on homepage
 * 
 * @param int $limit
 * @return mixed
 *
 */
public function getRandomPosts($start, $end)
{

  $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, 
          p.post_title, p.post_slug, p.post_content, 
          m.media_filename, m.media_caption, u.user_login, u.user_fullname
          FROM tbl_posts AS p
          INNER JOIN (SELECT ID FROM tbl_posts ORDER BY RAND() LIMIT 5) AS p2 ON p.ID = p2.ID
          INNER JOIN tbl_users AS u ON p.post_author = u.ID
          INNER JOIN tbl_media AS m ON p.media_id = m.ID
          WHERE p.post_type = 'blog'
          AND p.post_status = 'publish'
          AND m.media_target = 'blog' 
          LIMIT :position, :limit                                                                                                                                                                                       ";

  $this->setSQL($sql);

  $data = array(':position' => $start, ':limit' => $end);

  $randomPosts = $this->findAll($data);

  return (empty($randomPosts)) ?: $randomPosts;

}

/**
 * getPostsOnSidebar
 *
 * @param string $status
 * @param int|num $start
 * @param int|num $limit
 * @return array
 * 
 */
public function getPostsOnSidebar($limit)
{

$sql = "SELECT p.ID, p.media_id, p.post_author,
               p.post_date, p.post_modified, p.post_title,
               p.post_slug, p.post_content, p.post_summary,
               p.post_keyword, p.post_sticky,
               p.post_type, p.post_status, u.user_login, u.user_fullname
  FROM tbl_posts AS p
  INNER JOIN tbl_users AS u ON p.post_author = u.ID
  WHERE p.post_type = 'blog' 
  AND p.post_status = 'publish'
  ORDER BY p.post_date DESC LIMIT :limit ";

$this->setSQL($sql);

$sidebar_posts = $this->findAll([':limit' => $limit]);

return (empty($sidebar_posts)) ?: ['sidebarPosts' => $sidebar_posts];

}

}