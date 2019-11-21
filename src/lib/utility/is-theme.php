<?php
/**
 * is_theme Function
 * checking which theme actived and retrieve necessary theme data
 * 
 * @param string $status
 * 
 */
function is_theme($status)
{
  $theme = new ThemeDao();
  return $theme->loadTheme($status);
}