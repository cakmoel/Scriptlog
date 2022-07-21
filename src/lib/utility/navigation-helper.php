<?php
/**
 * nav_parent
 * 
 * @param int|number $parent_id
 * @return mixed
 * 
 */
function nav_parent($parent_id)
{
    
$sql = "SELECT ID, parent_id, menu_label, menu_link, menu_sort, menu_status, menu_position
        FROM tbl_menu WHERE ID = ?";

$statement = db_prepared_query($sql, [(int)$parent_id], 'i');

$results = get_result($statement);

return $results;

}

function nav_nested()
{
    
}