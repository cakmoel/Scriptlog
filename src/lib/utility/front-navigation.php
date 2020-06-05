<?php
/**
 * Front Navigation Function
 * 
 * @category Function
 * @return mixed
 * 
 */
function front_navigation()
{
  
  $navigation = new MenuDao();
  return $navigation -> findFrontNavigation(find_request()[0]);

}