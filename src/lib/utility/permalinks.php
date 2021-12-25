<?php
/**
 * permalinks
 *
 * @category Function
 * @author M.Noermoehammad
 * @param object $requestPath
 * @return mixed
 * 
 */
function permalinks($arg)
{

$config_file = read_config(invoke_config());
$app_url = $config_file['app']['url'];

if ( is_permalink_enabled() === 'yes' ) {

   return listen_request_path($arg, $app_url);

} else {

   return listen_query_string($arg, $app_url);

}

}

/**
 * is_permalink_enabled
 *
 * checking is rewrite enabled or disabled and return in it status
 * yes or no
 * 
 * @return boolean
 * 
 */
function is_permalink_enabled()
{
 
 $rewrite_status = json_decode(app_info()['permalink_setting'], true);

 return $rewrite_status['rewrite'];

}

function listen_query_string($arg, $app_url)
{

$link = [];

switch (HandleRequest::isQueryStringRequested()['key']) {

   case 'p':
      # Deliver request to single entry post
      if ( ( ! empty(HandleRequest::isQueryStringRequested()['value'] ) ) && ( $arg === HandleRequest::isQueryStringRequested()['value'] ) ) {
 
         $link = $app_url . DS . '?p=' . abs((int)$arg);
          
      } else {

         scriptlog_error("param requested not recognized");

      }

      return $link;

      break;

   case 'pg':

      break;

   case 'cat':

      break;

   case 'a':

      break;
   
   case 'blog':

      break;

   default:
      
   $post_id = $app_url . DS . '?p=' . abs((int)$arg);
   $page_id = $app_url . DS . '?pg' . abs((int)$arg);

   $link = ['post' => $post_id, 'page' => $page_id];  

   return $link;

   break;

}

}

function listen_request_path($arg, $app_url)
{

$request_path = new RequestPath();

$link = [];

if ( true === HandleRequest::checkMatchUriRequested() ) {


   switch ($request_path->param1) {

      case 'archive':
         
         break;

      case 'category':

         break;

      case 'blog':

         break;

      case 'page':

         break;

      case 'post':

         if ( ( ! empty( $request_path->param2) ) && ( $arg === $request_path->param2 ) ) {

            $post_slug = FrontHelper::frontPostSlug($arg);
            
            $link = $app_url . DS . 'post' . DS . $arg . DS . $post_slug['post_slug'];

         } else {

            scriptlog_error("param requested not recognized");

         }

         return $link;

         break;
      
      default:
         
         $post_slug = FrontHelper::frontPostSlug($arg);
         
         $post_rewrite = $app_url . DS . 'post' . DS . $arg . DS . $post_slug['post_slug'];

         $link = ['post' => $post_rewrite];

         return $link;

         break;

   }

}

}