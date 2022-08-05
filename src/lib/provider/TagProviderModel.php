<?php
/**
 * class TagProviderModel extends Dao
 * 
 * @category Provider Class
 * 
 * 
 */
class TagProviderModel extends Dao
{

public function __construct()
{
  parent::__construct();
}

/**
 * getTagCloud()
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

foreach ((array) $tag_exploaded as $tag ) {

  $html[] = '<a href="'.permalinks($tag)['tag'].'" class="tag" title="'.escape_html($tag).'">#'.escape_html($tag).'</a>';

}

return implode(" ", $html);

}

}