<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class PageProviderModel extends Dao
 * 
 * @category Provider class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class PageProviderModel extends Dao
{

public function __construct()
{
  parent::__construct();
}

/**
 * getPageById
 *
 * @param int|numeric $id
 * @param object $sanitize
 * @param object $fetchMode
 * @return mixed
 * 
 */
public function getPageById($id, $sanitize, $fetchMode = null)
{

$sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, p.post_title, p.post_slug,
    p.post_content, p.post_summary, p.post_keyword, p.post_status, p.post_sticky, p.post_type, 
    p.comment_status, m.media_filename, m.media_caption, m.media_target, m.media_access, 
    u.user_fullname
FROM tbl_posts AS p
INNER JOIN tbl_media AS m ON p.media_id = m.ID
INNER JOIN tbl_users AS u ON p.post_author = u.ID
WHERE p.ID = :ID AND p.post_status = 'publish'
AND p.post_type = 'page' AND m.media_target = 'page'
AND m.media_access = 'public' AND m.media_status = '1'";

$sanitized_id = $this->filteringId($sanitize, $id, 'sql');

$this->setSQL($sql);

$postById = (is_null($fetchMode)) ? $this->findRow([':ID' => (int)$sanitized_id]) : $this->findRow([':ID' => (int)$sanitized_id], $fetchMode);

return (empty($postById)) ?: $postById;

}
/**
 * Find page by slug title
 * 
 * @param string $slug
 * @return boolean|array|object
 * 
 */
public function getPageBySlug($slug, $sanitize)
{

$sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, p.post_title, p.post_slug,
        p.post_content, p.post_summary, p.post_keyword, p.post_status, p.post_sticky, 
	    p.post_type, p.comment_status,
        m.ID, m.media_filename, m.media_caption, m.media_access, u.ID, u.user_fullname
    FROM tbl_posts AS p
    INNER JOIN tbl_media AS m ON p.media_id = m.ID
    INNER JOIN tbl_users AS u ON p.post_author = u.ID
    WHERE p.post_slug = :slug 
	AND p.post_status = 'publish' AND p.post_type = 'page' 
	AND m.media_access = 'public' AND m.media_status = '1'";
	
	$this->setSQL($sql);
	
	$slug_sanitized = $this->filteringId($sanitize, $slug, 'xss');

    $pageBySlug = $this->findRow([':slug' => $slug_sanitized]);
    
    return (empty($pageBySlug)) ?: $pageBySlug;
    
}

/**
 * getRandomStickyPages
 * 
 * @return mixed
 * 
 */
public function getRandomStickyPages()
{

$sql = "SELECT ID, post_title, post_content FROM tbl_posts 
WHERE post_sticky = '1' 
AND post_status = 'publish' 
AND post_type = 'page' ORDER BY RAND() LIMIT 1 ";

$this->setSQL($sql);

$sticky_pages = $this->findAll();

return (empty($sticky_pages)) ?: $sticky_pages;

}

}
