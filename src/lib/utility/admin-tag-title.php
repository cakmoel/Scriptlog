<?php
/**
 * admin_tag_title()
 * 
 * print control panel page title
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $value
 * 
 */
function admin_tag_title($value) 
{
    $title = null;

    switch($value) {

         case 'posts':

             $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            break;

         case 'medialib':

             $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             break;

         case 'comments':
            
             $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             break;

         case 'menu':

             $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             break;

         case 'pages':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             break;

         case 'plugins':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            break;

         case 'users':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            break;
        
         case 'topics':

            $title .= safe_html(ucfirst("categories"))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            break;
          
          case 'tags':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            break;
            
         case 'option-general':

             $title .= safe_html("General settings")." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             break;

         case 'option-permalink':

            $title .= safe_html("Permalink settings")." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

           break;

         case 'option-reading':

            $title .= safe_html("Reading settings")." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

           break;
        
         case 'templates':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

          break;

         case 'dashboard':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

           break;
         
         default:
          
           if (strpos($value, '..')) {

             http_response_code(400);

             $title .= 'Bad Request - ERROR: ' . http_response_code(400);

           }

           if ( ( strstr($value, '../') !== false) || ( strstr($value, 'file://') !== false) || ( strstr($value, 'http://') !== false) ) {

              http_response_code(400);
              
              $title .= 'Bad Request - ERROR: ' . http_response_code(400);

           }

           if ( ( strstr($value, 'php://input') ) || ( strstr($value, 'php://filter') ) || ( strstr($value, 'data:') ) || ( strstr($value, 'zip://') ) ) {

             http_response_code(400);

             $title .= 'Bad Request - ERROR: ' . http_response_code(400);
             
           }
           
           if( ( empty($value) ) || ( !in_array($value, array_keys( admin_query() ) ) ) ) {

              http_response_code(404);
            
              $title .= 'Page Not Found - ERROR: ' . http_response_code(404);

           }

           return $title;

           break;

    }
    
}