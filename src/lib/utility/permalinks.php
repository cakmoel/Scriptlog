<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * permalinks
 *
 * @category Function
 * @author M.Noermoehammad
 * @param int|num|string
 * @return string
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

   $post_id = $app_url . DS . '?p=' . abs((int)$id);
   $page_id = $app_url . DS . '?pg' . abs((int)$id);
   $cat_id = $app_url . DS . '?cat=' . abs((int)$id);
   $tag_id = $app_url . DS . '?tag=' . strval($id);
   $archive = $app_url . DS . '?a=' . prevent_injection($id);

   switch (HandleRequest::isQueryStringRequested()['key']) {

      case 'p':
         # Deliver request to single entry post         
         if ( ( ! empty(HandleRequest::isQueryStringRequested()['value'] ) ) && ( $id === HandleRequest::isQueryStringRequested()['value']) ) {
    
            $entry_post = FrontHelper::grabSimpleFrontPost($id);

            $post_id = $app_url . DS . '?p=' . (isset($entry_post['ID']) ) ? escape_html((int)$entry_post['ID']) : "";

         } 

         break;
   
      case 'pg':
         // Deliver request to single entry page
         if ( ( ! empty(HandleRequest::isQueryStringRequested()['value'] ) ) && ( $id === HandleRequest::isQueryStringRequested()['value'] ) ) {

            $entry_page = FrontHelper::grabSimpleFrontPage($id);

            $page_id = $app_url . DS . '?pg='. (isset($entry_page['ID']) ) ? escape_html((int)$entry_page['ID']) : 0;
             
         } 

         break;
   
      case 'cat':
   
         if ( ( ! empty(HandleRequest::isQueryStringRequested()['value'] ) ) && ( $id === HandleRequest::isQueryStringRequested()['value'] ) ) {

            $entry_cat = FrontHelper::grabSimpleFrontTopic($id);

            $cat_id = $app_url . DS . '?cat='. ( isset($entry_cat['ID'])) ? escape_html($entry_cat['ID']) : "";

         } 
         
         break;

      case 'tag':

         if ( ( ! empty(HandleRequest::isQueryStringRequested()['value'] ) )  && ( $id === HandleRequest::isQueryStringRequested()['value'] ) ) {

            $entry_tag = FrontHelper::grabSimpleFrontTag($id);

            $tag_id = $app_url . DS . '?tag=' . ( isset($entry_tag['ID'])) ? escape_html($entry_tag['ID']) : "";
            
         }
         
         break;
         
      case 'a':

         if ( ( ! empty(HandleRequest::isQueryStringRequested()['value'] ) ) && ( $id === HandleRequest::isQueryStringRequested()['value'] ) ) {

            $archive = $app_url . DS . '?a=';
         }
   
         break;
      
      case 'blog':
   
         break;

      default:

         $link = ['post' => $post_id, 'page' => $page_id, 'cat' => $cat_id, 'tag'=>$tag_id, 'archive'=>$archive];  

         return $link;
         
        break;
      
   }

   $link = ['post' => $post_id, 'page' => $page_id, 'cat' => $cat_id, 'tag' => $tag_id, 'archive'=>$archive];  

   return $link;

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

            $page_slug = FrontHelper::grabPreparedfrontPageBySlug($id);

            $link = $app_url . DS . 'page' . DS . (isset($page_slug['post_slug'])) ? safe_html($page_slug['post_slug']) : "";

         } else {

            
         }

         break;

      case 'post':

         if ( ( ! empty( $request_path->param2) ) && ( $id === $request_path->param2 ) ) {

            $post_slug = FrontHelper::grabSimpleFrontPost($id);
            
            $link = $app_url . DS . 'post' . DS . $id . DS . (isset($post_slug['post_slug']) ) ? $post_slug['post_slug'] : "";

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