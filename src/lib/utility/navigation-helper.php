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
    
$sql = "SELECT ID, parent_id, menu_label, menu_link, menu_status, menu_position
        FROM tbl_menu WHERE ID = '$parent_id' ";

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