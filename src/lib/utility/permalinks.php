<?php

use Whoops\Handler\Handler;

/**
 * permalinks
 *
 * @category Function
 * @author M.Noermoehammad
 * @param object $requestPath
 * @return mixed
 * 
 */
function permalinks($id)
{

$config_file = read_config(invoke_config());
$app_url = $config_file['app']['url'];

if ( is_permalink_enabled() === 'yes' ) {

   return listen_request_path($id, $app_url);

} else {

   return listen_query_string($id, $app_url);

}

}

/**
 * is_permalink_enabled
 *
 * checking is rewrite enabled or disabled and return in it status
 * yes or no
 * 
 * @category Function
 * @author M.Noermoehammad
 * @return mixed
 * 
 */
function is_permalink_enabled()
{
 
 $rewrite_status = json_decode(app_info()['permalink_setting'], true);

 return $rewrite_status['rewrite'];

}

/**
 * listen_query_string
 * 
 * @category Function
 * @author M.Noermoehammad
 * @param mixed $id
 * @param string $app_url
 * 
 */
function listen_query_string($id, $app_url)
{

   $link = [];

   switch (HandleRequest::isQueryStringRequested()['key']) {

      case 'p':
         # Deliver request to single entry post
         if ( ( ! empty(HandleRequest::isQueryStringRequested()['value'] ) ) && ( $id === HandleRequest::isQueryStringRequested()['value'] ) ) {
    
            $link = $app_url . DS . '?p=' . abs((int)$id);
             
         } else {
   
            scriptlog_error("param requested not recognized");
   
         }
   
         return $link;
   
         break;
   
      case 'pg':
         // Deliver request to single entry page
         if ( ( ! empty(HandleRequest::isQueryStringRequested()['value'] ) ) && ( $id === HandleRequest::isQueryStringRequested()['value'] ) ) {

            $link = $app_url . DS . '?pg='. abs((int)$id);
             
         } else {

            scriptlog_error("param requested not recogniezed");

         }

         return $link;

         break;
   
      case 'cat':
   
         if ( ( ! empty(HandleRequest::isQueryStringRequested()['value'] ) ) && ( $id === HandleRequest::isQueryStringRequested()['value'] ) ) {

             $link = $app_url . DS . '?cat='. abs((int)$id);

         } else {

            scriptlog_error("param requested not recognized");

         }
         
         return $link;
         
         break;
   
      case 'a':

         if ( ( ! empty(HandleRequest::isQueryStringRequested()['value'] ) ) && ( $id === HandleRequest::isQueryStringRequested()['value'] ) ) {

            $link = $app_url . DS . '?a=';
         }
   
         break;
      
      case 'blog':
   
         break;
   
      default:
         
      $post_id = $app_url . DS . '?p=' . abs((int)$id);
      $page_id = $app_url . DS . '?pg' . abs((int)$id);
      $cat_id = $app_url . DS . '?cat=' . abs((int)$id);
      $archive = $app_url . DS . '?a=' . prevent_injection($id);
   
      $link = ['post' => $post_id, 'page' => $page_id, 'cat' => $cat_id, 'archive'=>$archive];  
   
      return $link;
   
      break;
   
   }

}

/**
 * list_request_path
 *
 * @category Function
 * @author M.Noermoehammad
 * @param mixed $arg
 * @param string $app_url
 * 
 */
function listen_request_path($id, $app_url)
{

$request_path = new RequestPath();

if ( true === HandleRequest::checkMatchUriRequested() ) {

   $link = [];

   switch ($request_path->param1) {

      case 'archive':
         
         break;

      case 'category':

         break;

      case 'blog':

         break;

      case 'page':

         if ( ! empty( $request_path->param2) ) {

            $page_slug = FrontHelper::frontPageBySlug($id);

            $link = $app_url . DS . 'page' . DS . $id;

         } else {

            
         }

         break;

      case 'post':

         if ( ( ! empty( $request_path->param2) ) && ( $id === $request_path->param2 ) ) {

            $post_slug = FrontHelper::grabSimpleFrontPost($id);
            
            $link = $app_url . DS . 'post' . DS . $id . DS . $post_slug['post_slug'];

         } else {

            scriptlog_error("param requested not recognized");

         }

         return $link;

         break;
      
      default:
         
         $post_slug = FrontHelper::grabSimpleFrontPost($id);
         
         $post_rewrite = $app_url . DS . 'post' . DS . safe_html((int)$post_slug['ID']). DS . safe_html($post_slug['post_slug']);
         
         $link = ['post' => $post_rewrite];

         return $link;

         break;

   }

}

}