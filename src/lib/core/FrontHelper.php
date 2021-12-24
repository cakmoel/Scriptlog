<?php
defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class FrontHelper
 * 
 * FrontHelper class will be useful for theme functionality
 * to retrieve particular content needed on theme layout
 * and theme meta
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

/**
 * frontPermalinks
 *
 * @param int $id
 * @return mixed|bool
 * 
 */
public static function frontPostSlug($id)
{

  $idsanitized = static::frontSanitizer($id, 'sql');

  $query = db_simple_query('SELECT ID, post_slug FROM tbl_posts WHERE ID = ' . $idsanitized);
  
  $results = $query->fetch_assoc();

  if ( isset($results) ) {

    return $results;

  }
             
}

/**
 * frontGalleries
 *
 * @param int $start
 * @param int $limit
 * @return mixed
 * 
 */
public static function frontGalleries($start, $limit)
{

$front = [];

$sql = "SELECT ID, media_filename, media_caption FROM tbl_media WHERE media_target = 'gallery'
       ORDER BY ID LIMIT ?, ?";

$statement = db_prepared_query($sql, [$start, $limit], 'ii');

$results = get_result($statement);

if ( isset($results) ) {

foreach ($results as $result) {

  $media_filename = $result['media_filename'];
  $media_caption = $result['media_caption'];
  $media_id = $result['ID'];

}   

$front = ['media_filename' => $media_filename, 'media_caption' => $media_caption, 'media_id' => $media_id];

return $front;

}

}

/**
 * frontPostById
 *
 * @param int $id
 * @return mixed
 * 
 */
public static function frontPostById($id)
{

$sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, p.post_title, p.post_slug,
               p.post_content, p.post_summary, p.post_keyword, p.post_tags, p.post_status, p.post_sticky, 
               p.post_type, p.comment_status, m.media_filename, m.media_caption, m.media_target, 
               m.media_access, u.user_fullname
        FROM tbl_posts AS p
        INNER JOIN tbl_media AS m ON p.media_id = m.ID
        INNER JOIN tbl_users AS u ON p.post_author = u.ID
        WHERE p.ID = ? AND p.post_status = 'publish'
AND p.post_type = 'blog' AND m.media_target = 'blog'
AND m.media_access = 'public' AND m.media_status = '1'";

$idsanitized = self::frontSanitizer($id, 'sql');

$statement = db_prepared_query($sql, [$idsanitized], 'i');

$result = get_result($statement);

return (empty($result)) ?: $result;

}

/**
 * frontPageBySlug
 *
 * @param string $slug
 * @return mixed
 * 
 */
public static function frontPageBySlug($slug)
{

$sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, p.post_title, p.post_slug,
               p.post_content, p.post_summary, p.post_keyword, p.post_tags, p.post_status, p.post_sticky, 
               p.post_type, p.comment_status,
               m.ID, m.media_filename, m.media_caption, m.media_access, u.ID, u.user_fullname
  FROM tbl_posts AS p
  INNER JOIN tbl_media AS m ON p.media_id = m.ID
  INNER JOIN tbl_users AS u ON p.post_author = u.ID
  WHERE p.post_slug = ?
  AND p.post_status = 'publish' AND p.post_type = 'page' 
  AND m.media_access = 'public' AND m.media_status = '1'";

$slug_sanitized = self::frontSanitizer($slug, 'xss'); 

$statement = db_prepared_query($sql, [$slug_sanitized], 's');

$result = get_result($statement);

return (empty($result)) ?: $result;

}

/**
 * frontTopicBySlug
 *
 * @param string $slug
 * @return mixed
 * 
 */
public static function frontTopicBySlug($slug)
{

 $sql = "SELECT ID, topic_title FROM tbl_topics WHERE topic_slug = ? AND topic_status = 'Y'";

 $slug_sanitized = self::frontSanitizer($slug, 'xss');

 $statement = db_prepared_query($sql, [$slug_sanitized], 's');

 $result = get_result($statement);

 return (empty($result)) ?: $result;

}

/**
 * frontSanitizer
 *
 * @param string $str
 * @param string $type
 * @return string
 * 
 */
private static function frontSanitizer($str, $type)
{ 
 return sanitizer($str, $type);
}


}