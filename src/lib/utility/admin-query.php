<?php
/**
 * admin query funciton
 * whitelist query allowed in admin directory
 *
 * @return array
 * 
 */
function admin_query()
{

$whitelist = array(
'dashboard'=>'dashboard.php', 'posts'=>'posts.php', 'medialib'=>'medialib.php',
'pages'=>'pages.php', 'topics'=>'topics.php', 'comments'=>'comments.php', 
'reply'=>'reply.php', 'templates'=>'templates.php', 'menu'=>'menu.php', 
'menu-child'=>'menu-child.php', 'users'=>'users.php', 
'option-general'=>'option-general.php', 'option-permalink'=>'option-permalink.php', 
'plugins'=>'plugins.php', 'logout'=>'logout.php', '403'=>'403.php', '404'=>'404.php');

return $whitelist;

}