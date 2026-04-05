<?php

/**
 * retrieves_navigation
 *
 * @param string $visibility
 *
 */
function theme_navigation($visibility)
{

    $sql = "SELECT ID, menu_label, menu_link, menu_status, menu_visibility, parent_id, menu_sort 
         FROM tbl_menu WHERE menu_status = 'Y' AND menu_visibility = ? 
         ORDER BY menu_sort ASC, menu_label";

    $menus = array(
      'items' => array(),
      'parents' => array()
    );

    $db = db_instance();
    
    // Handle both Db (PDO) and mysqli
    if (method_exists($db, 'dbQuery')) {
        // PDO style - use dbQuery
        $stmt = $db->dbQuery($sql, [$visibility]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            while ($items = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $menus['items'][$items['ID']] = $items;
                $menus['parents'][$items['parent_id']][] = $items['ID'];
            }
        }
    } else {
        // mysqli style - use simpleQuery
        $sql = str_replace('?', "'" . db_instance()->real_escape_string($visibility) . "'", $sql);
        $stmt = $db->simpleQuery($sql);
        
        if ($stmt->num_rows > 0) {
            while ($items = $stmt->fetch_array(MYSQLI_ASSOC)) {
                $menus['items'][$items['ID']] = $items;
                $menus['parents'][$items['parent_id']][] = $items['ID'];
            }
        }
    }

    return $menus;
}
