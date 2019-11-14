<?php
/**
 * Front Navigation Function
 * 
 * @category Function
 * @package  SCRIPTLOG/LIB/UTILITY
 * @return mixed
 * 
 */
function front_navigation()
{
  
  $navigation = new Menu();
  return $navigation -> findFrontNavigation(find_request()[0]);

}