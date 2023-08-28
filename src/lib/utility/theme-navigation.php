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
         FROM tbl_menu WHERE menu_status = 'Y' AND menu_visibility = '$visibility' 
         ORDER BY menu_sort ASC, menu_label";

$menus = array(
  'items' => array(),
  'parents' => array()
);

  $stmt = db_simple_query($sql);

  if ($stmt->num_rows > 0) {

    while ($items = $stmt->fetch_array(MYSQLI_ASSOC)) {

      $menus['items'][$items['ID']] = $items; // Create current menus item id into array

      $menus['parents'][$items['parent_id']][] = $items['ID']; // Create list of all items with child

    }
  }

  return $menus;
}