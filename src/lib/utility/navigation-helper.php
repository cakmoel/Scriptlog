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

    // Handle both MySQLi (fetch_array) and PDO (fetch)
    if (is_object($parent)) {
        if (method_exists($parent, 'fetch')) {
            // PDO
            return $parent->fetch(PDO::FETCH_ASSOC);
        } elseif (method_exists($parent, 'fetch_array')) {
            // MySQLi
            return $parent->fetch_array(MYSQLI_ASSOC);
        }
    }
    return [];
}
