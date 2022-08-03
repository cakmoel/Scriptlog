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

$items = [];

$idsanitized = $this->filteringId($sanitize, $postId, 'sql');

$sql = "SELECT tbl_posts.post_tags FROM tbl_posts WHERE tbl_posts.ID = :post_id";

$this->setSQL($sql);

$data = array(':post_id' => $idsanitized);

$html = '<div class="post-tags">';

while ( $tags = $this->findRow($data)) {

  $items = array_merge($items, explode(" ",$tags[0]));
}

$items = array_unique_compact($items);

for ($i = 0; $i < count($items); $i++) {

  $html .= "<a href='".permalinks($items[$i])['tag']."'>".$items[$i]."</a>";

}

$html .= '</div>';

return $html;

}

}