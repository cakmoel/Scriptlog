<?php
/**
 * cp_tag_title function
 * print control panel page title
 * 
 * @category function
 * @param string $value
 * 
 */
function cp_tag_title($value, $allowed_request) 
{
    $title = null;

    switch($value) {

         case 'posts':

             $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             return $title;

            break;

         case 'medialib':

             $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             return $title;

             break;

         case 'comments':
            
             $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             return $title;

             break;

         case 'menu':

             $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             return $title;

             break;

         case 'menu-child':

             $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             return $title;

             break;

         case 'pages':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            return $title;

             break;

         case 'plugins':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            return $title;

             break;

         case 'users':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            return $title;

             break;
        
         case 'topics':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            return $title;

             break;
         
         case 'option-general':

             $title .= safe_html("General setting")." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             return $title;

             break;

         case 'option-permalink':

            $title .= safe_html("Permalink setting")." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            return $title;

           break;
        
         case 'templates':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

            return $title;

          break;

         case 'dashboard':

            $title .= safe_html(ucfirst($value))." &raquo; ".APP_TITLE." &raquo; ".APP_CODENAME;

             return $title;

           break;
         
         default:
          
           if((strstr($value, '../') !== false) || (strstr($value, 'file://') !== false)) {

              http_response_code(400);
              
              $title .= safe_html(strtoupper($value)) . ' ERROR: ' . http_response_code(400);

           }
           
           if((empty($breadCrumbs)) || (!in_array($breadCrumbs, array_keys($allowedQuery)))) {

              http_response_code(404);
            
              $title .= safe_html(strtoupper($value)) . ' ERROR: ' . http_response_code(404);

           }

           return $title;

           break;

    }
    
}