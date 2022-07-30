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

$types = array();

$taglink = "";

$get_tags = db_simple_query("SELECT post_tags FROM tbl_posts");

if ($get_tags->num_rows > 0) {

    while ($row = $get_tags->fetch_array(MYSQLI_NUM)) {

      $types = array_merge($types, explode(" ", $row[0]));

    }

    $types = array_unique_compact($types);

    for ($i=0; $i < count($types); $i++) { 
        
      $taglink .= "<a href='".permalinks($types[$i])['tag']."'>".$types[$i]."</a>";
    
      $taglink .= ($i < count($types)-1) ? ", \n" : "";

    }
     
}

return $taglink;

}

/**
 * array_unique_compact
 *
 * @param array $items
 * @return array
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

