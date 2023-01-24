<?php
/**
 * nav_parent
 * 
 * @category function
 * @author M.Noermoehammad
 * @param int|number $parent_id
 * 
 */
function nav_parent($parent_id)
{
    
$idsanitized = sanitizer($parent_id, 'sql');
$sql = "SELECT ID, parent_id, menu_label, menu_link, menu_status, menu_visibility
        FROM tbl_menu WHERE ID = '$idsanitized' ";

return db_simple_query($sql);

}

/**
 * nav_nested
 *
 * @category function
 * @author M.Noermoehammad
 */
function nav_nested($parent)
{

 $row = (is_object($parent)) ? $parent->fetch_array(MYSQLI_ASSOC) : "";

 return (is_iterable($row)) ? $row : [];

}