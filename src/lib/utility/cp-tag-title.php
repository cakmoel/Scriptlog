<?php
/**
 * cp_tag_title function
 * print control panel page title
 * 
 * @category function
 * @param string $value
 * 
 */
function cp_tag_title($value) 
{
    switch($value) {

         case 'posts':

             echo 'Post';

            break;

         case 'medialib':

             echo 'Media Library';

             break;

         case 'comments':
            
             echo 'Comments';

             break;

         case 'menu':

             echo 'Menu';

             break;

         case 'menu-child':

             echo 'Sub Menu';

             break;

         case 'pages':

             echo 'Pages';

             break;

         case 'plugins':

             echo 'Plugins';

             break;

         case 'users':

             echo 'Users';

             break;

         default:
            
           echo 'Dashboard';

           break;

    }
    
}