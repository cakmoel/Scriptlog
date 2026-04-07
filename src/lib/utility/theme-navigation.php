<?php

/**
 * theme_navigation
 * 
 * Retrieves navigation menu items filtered by visibility and current locale
 *
 * @param string $visibility
 * @return array
 */
function theme_navigation($visibility)
{

    $currentLocale = 'en';
    
    if (function_exists('get_locale')) {
        $currentLocale = get_locale();
    }

    $sql = "SELECT ID, menu_label, menu_link, menu_status, menu_visibility, parent_id, menu_sort, menu_locale 
         FROM tbl_menu 
         WHERE menu_status = 'Y' 
           AND menu_visibility = ? 
           AND (menu_locale = ? OR menu_locale IS NULL OR menu_locale = '')
         ORDER BY menu_sort ASC, menu_label";

    $menus = array(
      'items' => array(),
      'parents' => array()
    );

    $db = db_instance();
    
    // Handle both Db (PDO) and mysqli
    if (method_exists($db, 'dbQuery')) {
        // PDO style - use dbQuery
        $stmt = $db->dbQuery($sql, [$visibility, $currentLocale]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            while ($items = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $menus['items'][$items['ID']] = $items;
                $menus['parents'][$items['parent_id']][] = $items['ID'];
            }
        }
    } else {
        // mysqli style - use simpleQuery
        $escapedVisibility = db_instance()->real_escape_string($visibility);
        $escapedLocale = db_instance()->real_escape_string($currentLocale);
        $sql = "SELECT ID, menu_label, menu_link, menu_status, menu_visibility, parent_id, menu_sort, menu_locale 
             FROM tbl_menu 
             WHERE menu_status = 'Y' 
               AND menu_visibility = '$escapedVisibility'
               AND (menu_locale = '$escapedLocale' OR menu_locale IS NULL OR menu_locale = '')
             ORDER BY menu_sort ASC, menu_label";
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
