<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class TagProviderModel extends Dao
 * 
 * @category Provider Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class TagProviderModel extends Dao
{

  private $linkPosts;

  private $pagination;

public function __construct()
{
  parent::__construct();
}

/**
 * getLinkTag()
 * 
 * @see https://www.longren.io/how-to-build-a-tag-cloud-with-php-mysql-and-css/
 * @see http://howto.philippkeller.com/2005/04/24/Tags-Database-schemas/
 * @see http://howto.philippkeller.com/2005/06/19/Tagsystems-performance-tests/
 * @see https://snook.ca/archives/php/how_i_added_tag
 * 
 */
public function getLinkTag($postId, $sanitize) 
{

$html = [];

$idsanitized = $this->filteringId($sanitize, $postId, 'sql');

$sql = "SELECT DISTINCT tbl_posts.post_tags FROM tbl_posts WHERE tbl_posts.ID = :post_id LIMIT 1";

$this->setSQL($sql);

$data = array(':post_id' => $idsanitized);

$tags = $this->findColumn($data);

$tag_exploaded = explode(',', strtolower($tags));

foreach ((array) $tag_exploaded as $tag) {

  $html[] = '<a href="'.permalinks(tag_slug($tag))['tag'].'" class="tag" title="'.escape_html($tag).'">#'.escape_html($tag).'</a>';

}

return implode(" ", $html);

}

/**
 * getPostsPublishedByTag
 *
 * @param string $tag
 * @param object $sanitize
 * @param Paginator $paginator
 * @return mixed
 */
public function getPostsPublishedByTag($tag, $sanitize, Paginator $perPage)
{
  $entries = [];

  $this->linkPosts = $perPage;

  $count_tags = "SELECT tbl_posts.ID, tbl_posts.post_title, tbl_posts.post_content, tbl_posts.post_tags 
                 FROM tbl_posts WHERE tbl_posts.post_tags LIKE :post_tags ORDER BY tbl_posts.ID DESC";

  $this->setSQL($count_tags);

  $this->linkPosts->set_total($this->checkCountValue([':post_tags' => $tag]));

  $sql = "SELECT tbl_posts.ID, tbl_posts.media_id, tbl_posts.post_author, 
                 tbl_posts.post_date AS created_at, 
                 tbl_posts.post_modified AS modified_at,  
                 tbl_posts.post_title, tbl_posts.post_slug, 
                 tbl_posts.post_content, tbl_posts.post_summary, 
                 tbl_posts.post_keyword, tbl_posts.post_tags, tbl_posts.post_status,
                 tbl_posts.post_type, tbl_posts.comment_status, tbl_users.user_fullname,
                 tbl_users.user_login, tbl_users.user_level,
                 tbl_media.media_filename, tbl_media.media_caption
          FROM tbl_posts, tbl_users, tbl_media
          WHERE tbl_posts.post_tags LIKE :post_tags 
          AND tbl_posts.post_author = tbl_users.ID
          AND tbl_posts.post_status = 'publish' 
          AND tbl_posts.post_type = 'blog'
          AND tbl_users.user_banned = '0'
          AND tbl_posts.media_id = tbl_media.ID
          ORDER BY tbl_posts.post_date DESC " . $this->linkPosts->get_limit($sanitize);

  $this->setSQL($sql);

  $entries = $this->findAll([':post_tags' => $tag]);

  $this->pagination = $this->linkPosts->page_links($sanitize);

  return (empty($entries)) ?: ['postsByTag' => $entries, 'paginationLink' => $this->pagination];
}

}