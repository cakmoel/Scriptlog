<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class FrontHelper
 * 
 * FrontHelper class will be useful for theme functionality
 * to retrieves particular content needed on theme layout
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
   * grabSimpleFrontPost
   *
   * @param int $id
   * @return array|null|false
   * 
   */
  public static function grabSimpleFrontPost($id)
  {

    $idsanitized = static::frontSanitizer($id, 'sql');

    $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, p.post_title, p.post_slug,
           p.post_content, p.post_summary, p.post_keyword, p.post_tags, p.post_status, p.post_sticky, 
           p.post_type, p.comment_status, m.media_filename, m.media_caption, m.media_target, 
           m.media_access, u.user_login, u.user_fullname
           FROM tbl_posts p
           INNER JOIN tbl_media m ON p.media_id = m.ID
           INNER JOIN tbl_users u ON p.post_author = u.ID
           WHERE p.ID = '$idsanitized' AND p.post_status = 'publish'
           AND p.post_type = 'blog' AND m.media_target = 'blog'
           AND m.media_access = 'public' AND m.media_status = '1' LIMIT 1";

    $query = db_simple_query($sql);

    $result = $query->fetch_assoc();

    return (empty($result)) ?: $result;
  }

  /**
   * grabSimpleFrontTopic
   *
   * @param int|numeric $id
   * @return array|null|false
   * 
   */
  public static function grabSimpleFrontTopic($id)
  {

    $idsanitized = static::frontSanitizer($id, 'sql');

    $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug, tbl_topics.topic_status FROM tbl_topics, tbl_post_topic WHERE tbl_topics.ID = tbl_post_topic.topic_id 
            AND tbl_topics.topic_status = 'Y' AND tbl_post_topic.post_id = '$idsanitized' ";

    $query = db_simple_query($sql);

    $result = $query->fetch_assoc();

    return (empty($result)) ?: $result;
  }

  /**
   * grabSimpleFrontTag
   *
   * @param string $param
   * @return array|null|false
   * 
   */
  public static function grabSimpleFrontTag($param)
  {
    $param_sanitized = static::frontSanitizer($param, 'xss');

    $sql = "SELECT tbl_posts.post_tags FROM tbl_posts WHERE tbl_posts.post_tags = '$param_sanitized'";

    $query = db_simple_query($sql);

    $result = $query->fetch_assoc();

    return (empty($result)) ?: $result;
  }

  /**
   * grabSimpleFrontArchive
   * 
   */
  public static function grabSimpleFrontArchive()
  {
    
    $sql = "SELECT MONTH(p.post_date) AS month, YEAR(p.post_date) AS year, COUNT(p.ID) AS total
        FROM tbl_posts p GROUP BY month, year 
        ORDER BY month DESC";

    $query = db_simple_query($sql);

    $result = $query->fetch_assoc();

    return ( empty($result) ) ?: $result;
   
  }
  
  /**
   * grabSimpleFrontPage
   *
   * @param int|num $id
   * @return array|null|false
   * 
   */
  public static function grabSimpleFrontPage($id)
  {
    $idsanitized = static::frontSanitizer($id, 'sql');

    $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, 
    p.post_title, p.post_slug,
    p.post_content, p.post_summary, p.post_keyword, 
    p.post_tags, p.post_status, p.post_sticky, 
    p.post_type, p.comment_status,
    m.ID, m.media_filename, m.media_caption, m.media_access, u.ID, u.user_login, u.user_fullname
FROM tbl_posts AS p
INNER JOIN tbl_media AS m ON p.media_id = m.ID
INNER JOIN tbl_users AS u ON p.post_author = u.ID
WHERE p.ID = '$idsanitized'
AND p.post_status = 'publish' AND p.post_type = 'page' 
AND m.media_access = 'public' AND m.media_status = '1'";

    $query = db_simple_query($sql);

    $result = $query->fetch_assoc();

    return (empty($result)) ?: $result;
  }

  /**
   * grabPreparedFrontPostById
   *
   * @param int $id
   * @return array
   * 
   */
  public static function grabPreparedFrontPostById($id)
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
AND m.media_access = 'public' AND m.media_status = '1' LIMIT 1";

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
  public static function grabPreparedFrontPageBySlug($slug)
  {

    $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, 
               p.post_title, p.post_slug,
               p.post_content, p.post_summary, p.post_keyword, 
               p.post_tags, p.post_status, p.post_sticky, 
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
  public static function grabPreparedFrontTopicBySlug($slug)
  {

    $sql = "SELECT ID, topic_title FROM tbl_topics WHERE topic_slug = ? AND topic_status = 'Y'";

    $slug_sanitized = self::frontSanitizer($slug, 'xss');

    $statement = db_prepared_query($sql, [$slug_sanitized], 's');

    $result = get_result($statement);

    return (empty($result)) ?: $result;
  }

  /**
   * frontGalleries
   *
   * @param int $start
   * @param int $limit
   * @return mixed
   * 
   */
  public static function grabPreparedFrontGalleries($start, $limit)
  {

    $front = [];

    $sql = "SELECT ID, media_filename, media_caption FROM tbl_media WHERE media_target = 'gallery'
       ORDER BY ID LIMIT ?, ?";

    $statement = db_prepared_query($sql, [$start, $limit], 'ii');

    $results = get_result($statement);

    if (isset($results)) {

      foreach ($results as $result) {

        $media_filename = $result['media_filename'];
        $media_caption = $result['media_caption'];
        $media_id = $result['ID'];
      }

      return ['media_filename' => $media_filename, 'media_caption' => $media_caption, 'media_id' => $media_id];

    }
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