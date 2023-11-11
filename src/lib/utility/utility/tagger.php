<?php
/**
 * outputting_tags()
 * 
 * Outputting a unique list of keywords
 * 
 * @category Function outputting_tags()
 * @see http://howto.philippkeller.com/2005/06/19/Tagsystems-performance-tests/
 * @see http://howto.philippkeller.com/2005/04/24/Tags-Database-schemas/
 * @see http://howto.philippkeller.com/2005/05/05/Tags-with-MySQL-fulltext/
 * @see https://snook.ca/archives/php/how_i_added_tag
 * @see https://stackoverflow.com/questions/549292/id-for-tags-in-tag-systems?noredirect=1&lq=1
 * @see https://stackoverflow.com/questions/1854886/php-mysql-how-to-add-multiple-tags
 * @see https://stackoverflow.com/questions/4202375/how-to-build-tagging-system-like-stackoverflow
 * @see https://stackoverflow.com/questions/1838801/how-to-store-tags-in-a-database-using-mysql-and-php
 * @see https://stackoverflow.com/questions/5160307/how-can-i-create-a-tagging-system-using-php-and-mysql 
 * @see https://stackoverflow.com/questions/2602957/how-to-design-a-mysql-table-for-a-tag-cloud
 * @see https://www.longren.io/how-to-build-a-tag-cloud-with-php-mysql-and-css/
 * @see https://stackoverflow.com/questions/549292/id-for-tags-in-tag-systems
 * @see https://stackoverflow.com/questions/334183/what-is-the-most-efficient-way-to-store-tags-in-a-database
 * @see https://stackoverflow.com/questions/48475/database-design-for-tagging
 * @see https://stackoverflow.com/questions/373126/how-to-design-a-database-schema-to-support-tagging-with-categories
 * @see https://stackoverflow.com/questions/172648/is-there-an-agreed-ideal-schema-for-tagging
 *
 */
function outputting_tags()
{

$taglink = [];

$get_tags = function_exists('db_simple_query') ? db_simple_query("SELECT post_tags FROM tbl_posts") : "";

$tags = [];

if ($get_tags->num_rows > 0) {

  while ($rows = $get_tags->fetch_array(MYSQLI_ASSOC)) {

    $tags = array_merge($tags, explode(",", strtolower($rows['post_tags'])));
  
  }

  $tags = array_unique_compact($tags);

  for ($t = 0; $t < count($tags); $t++) {

    $taglink[] = '<li class="list-inline-item"><a href="'.permalinks(tag_slug($tags[$t]))['tag'].'" class="tag" title="'.$tags[$t].'">#'.$tags[$t].'</a></li>';

  }

  return implode(" ", $taglink);

}

}

/**
 * array_unique_compact
 *
 * @param array $items
 * @return array
 * 
 */
function array_unique_compact($items) 
{

  $temp_array = array_unique($items);

  $i = 0;

  foreach ($temp_array as $v) {
    
    $newarr[$i] = $v;
    $i++;

  }
  
  return $newarr;

}

/**
 * checking_duplicate_tags
 *
 * @return bool
 * 
 */
function checking_duplicate_tags()
{

 $sql  = "SELECT post_tags, COUNT(post_tags) as totalTag 
 FROM tbl_posts GROUP BY post_tags HAVING COUNT(post_tags) > 1 ORDER BY totalTag DESC";

 $stmt = function_exists('db_simple_query') ? db_simple_query($sql) : ""; 

 return ($stmt) ? false : true;

}

/**
 * tag_slug
 *
 * @category function
 * @author Reza Lavarian
 * @param string $tag
 * @see https://www.decodingweb.dev/php-replace-space-with-dash
 * @see https://stackoverflow.com/questions/8425521/put-dash-between-every-third-character
 * @return string
 * 
 */
function tag_slug($tag)
{
  $tag = preg_replace('![\s]+!u', '-', strtolower($tag));
  $tag = preg_replace('![^-\pL\pN\s]+!u', '', $tag);
  $tag = preg_replace('![-\s]+!u', '-', $tag);

  return trim($tag, '-');
}
