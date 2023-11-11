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
           LEFT JOIN tbl_media m ON p.media_id = m.ID
           LEFT JOIN tbl_users u ON p.post_author = u.ID
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

    $sql = "SELECT ID, topic_title, topic_slug FROM tbl_topics WHERE ID = '$idsanitized'";

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

    $sql = "SELECT YEAR(tbl_posts.post_date) AS year_archive, MONTH(tbl_posts.post_date) AS month_archive
            FROM tbl_posts GROUP BY MONTH(tbl_posts.post_date), YEAR(tbl_posts.post_date) 
            ORDER BY MONTH(tbl_posts.post_date) DESC";

    $query = db_simple_query($sql);

    $result = $query->fetch_assoc();

    return (empty($result)) ?: $result;
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

    $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, p.post_title, p.post_slug,
    p.post_content, p.post_summary, p.post_keyword, 
    p.post_status, p.post_visibility, p.post_tags,  p.post_sticky, 
    p.post_type, p.comment_status, m.ID, m.media_filename, m.media_caption, m.media_access, 
    u.ID, u.user_login, u.user_fullname
FROM tbl_posts AS p
LEFT JOIN tbl_media AS m ON p.media_id = m.ID
LEFT JOIN tbl_users AS u ON p.post_author = u.ID
WHERE p.ID = '$idsanitized'
AND p.post_status = 'publish' 
AND p.post_visibility = 'public'
AND p.post_type = 'page' 
AND m.media_access = 'public' AND m.media_status = '1' LIMIT 1";

    $results = db_simple_query($sql)->fetch_assoc();

    return (empty($results)) ?: $results;
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
               p.post_content, p.post_summary, p.post_keyword, p.post_tags, p.post_status, p.post_visibility, p.post_sticky, 
               p.post_type, p.comment_status, m.media_filename, m.media_caption, m.media_target, 
               m.media_access, u.user_fullname
        FROM tbl_posts AS p
        LEFT JOIN tbl_media AS m ON p.media_id = m.ID
        LEFT JOIN tbl_users AS u ON p.post_author = u.ID
        WHERE p.ID = ? AND p.post_status = 'publish'
        AND p.post_visibility = 'public'
        AND p.post_type = 'blog' AND m.media_target = 'blog' 
        AND m.media_access = 'public' AND m.media_status = '1' LIMIT 1";

    return db_prepared_query($sql, [$id], 'i')->get_result()->fetch_assoc();
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
               p.post_tags, p.post_status, p.post_visibility, p.post_sticky, 
               p.post_type, p.comment_status,
               m.ID, m.media_filename, m.media_caption, m.media_access, u.ID, u.user_fullname
  FROM tbl_posts AS p
  LEFT JOIN tbl_media AS m ON p.media_id = m.ID
  LEFT JOIN tbl_users AS u ON p.post_author = u.ID
  WHERE p.post_slug = ?
  AND p.post_status = 'publish' 
  AND p.post_visibility = 'public'
  AND p.post_type = 'page' 
  AND m.media_access = 'public' AND m.media_status = '1' LIMIT 1";

    return db_prepared_query($sql, [$slug], 's')->get_result()->fetch_assoc();
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

    $sql = "SELECT ID, topic_title, topic_slug FROM tbl_topics WHERE topic_slug = ? AND topic_status = 'Y'";

    return db_prepared_query($sql, [$slug], 's')->get_result()->fetch_assoc();
  }

  /**
   * grabPreparedFrontArchive
   *
   * @param array $values
   * @return mixed
   * 
   */
  public static function grabPreparedFrontArchive($values)
  {

    $month = isset($values['month'])  ? Sanitize::mildSanitizer($values['month']) : null;
    $year = isset($values['year'])  ? Sanitize::mildSanitizer($values['year']) : null;

    // set from and to dates
    $from = date('Y-m-01 00:00:00', strtotime("$year-$month"));
    $to = date('Y-m-31 23:59:59', strtotime("$year-$month"));

    $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, 
    p.post_modified, p.post_title, p.post_slug, 
    p.post_content, p.post_summary, p.post_keyword, p.post_tags, p.post_type, p.post_status, 
    p.post_sticky, u.user_login, u.user_fullname,
    m.media_filename, m.media_captionh
  FROM tbl_posts AS p
  LEFT JOIN tbl_users AS u ON p.post_author = u.ID
  LEFT JOIN tbl_media AS m ON p.media_id = m.ID
  WHERE DATE(p.post_date) BETWEEN ? AND ? 
  AND p.post_type = 'blog' AND p.post_status = 'publish'
  ORDER BY DATE(p.post_date) DESC ";

    return db_prepared_query($sql, [$from, $to], 'ss')->get_result()->fetch_assoc();
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

    $sql = "SELECT ID, media_filename, media_caption FROM tbl_media WHERE media_target = 'gallery' ORDER BY ID LIMIT ?, ?";

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
   * grabFrontTag
   *
   * implementing a simple MySQL full-text searching
   *  
   * @param string $tag
   * @return mixed
   * 
   */
  public static function grabFrontTag($tag)
  {
     $sql = "SELECT ID, post_title, post_content FROM tbl_posts WHERE MATCH(post_title, post_content, post_tags) AGAINST('$tag' IN BOOLEAN MODE )";

     $results = db_simple_query($sql)->fetch_assoc();

     return (empty($results)) ?: $results;
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
