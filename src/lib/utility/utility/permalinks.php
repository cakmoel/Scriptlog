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

   if (is_permalink_enabled() === 'yes') {

      return listen_request_path($id, $app_url);

   } else {

      return listen_query_string($id, $app_url);

   }
}

/**
 * is_permalink_enabled
 *
 * checking is rewrite enabled or disabled 
 * and return in it status yes or no
 * 
 * @category Function
 * @author M.Noermoehammad
 * @return mixed
 * 
 */
function is_permalink_enabled()
{

   $rewrite_status = isset(app_info()['permalink_setting']) ? json_decode(app_info()['permalink_setting'], true) : "";
   return (is_array($rewrite_status) && isset($rewrite_status['rewrite'])) ? $rewrite_status['rewrite'] : "";

}

/**
 * listen_query_string
 * 
 * @category Function
 * @author M.Noermoehammad
 * @param mixed $id
 * @param string $app_url
 * @uses FrontHelper::methodName()
 * 
 */
function listen_query_string($id = null, $app_url = null)
{

   $post_id = $app_url . DS . '?p=' . abs((int)$id);
   $page_id = $app_url . DS . '?pg' . abs((int)$id);
   $cat_id = $app_url . DS . '?cat=' . abs((int)$id);
   $tag_id = $app_url . DS . '?tag=' . strval($id);
   $archive = $app_url . DS . '?a=' . $id;

   switch (HandleRequest::isQueryStringRequested()['key']) {

      case 'p':
         # Deliver request to single entry post         
         if ((!empty(HandleRequest::isQueryStringRequested()['value'])) && ($id === HandleRequest::isQueryStringRequested()['value'])) {

            $entry_post = FrontHelper::grabSimpleFrontPost($id);
            $post_id = $app_url . DS . '?p=' . (isset($entry_post['ID'])) ? escape_html((int)$entry_post['ID']) : "";
         }

         break;

      case 'pg':
         // Deliver request to single entry page
         if ((!empty(HandleRequest::isQueryStringRequested()['value'])) && ($id === HandleRequest::isQueryStringRequested()['value'])) {

            $entry_page = FrontHelper::grabSimpleFrontPage($id);
            $page_id = $app_url . DS . '?pg=' . (isset($entry_page['ID'])) ? escape_html((int)$entry_page['ID']) : 0;
         }

         break;

      case 'cat':

         if ((!empty(HandleRequest::isQueryStringRequested()['value'])) && ($id === HandleRequest::isQueryStringRequested()['value'])) {

            $entry_cat = FrontHelper::grabSimpleFrontTopic($id);
            $cat_id = $app_url . DS . '?cat=' . (isset($entry_cat['ID'])) ? escape_html($entry_cat['ID']) : "";
         }

         break;

      case 'tag':

         if ((!empty(HandleRequest::isQueryStringRequested()['value'])) && ($id === HandleRequest::isQueryStringRequested()['value'])) {

            $entry_tag = FrontHelper::grabFrontTag($id);
            (isset($entry_tag['ID'])) ? escape_html($entry_tag['ID']) : 0;
            $tag_id = $app_url . DS . '?tag=' . $id;
         }

         break;

      case 'a':

         if ((!empty(HandleRequest::isQueryStringRequested()['value'])) && ($id === HandleRequest::isQueryStringRequested()['value'])) {

            $entry_archives = FrontHelper::grabSimpleFrontArchive();

            $archive_requested = class_exists('HandleRequest') ? preg_split("//", HandleRequest::isQueryStringRequested()['value'], -1, PREG_SPLIT_NO_EMPTY) : preg_split("//", $id, -1, PREG_SPLIT_NO_EMPTY);

            $year = (isset($archive_requested[0]) && isset($archive_requested[1]) && isset($archive_requested[2]) && isset($archive_requested[3])) ? $archive_requested[0] . $archive_requested[1] . $archive_requested[2] . $archive_requested[3] : null;
            $month = (isset($archive_requested[4]) && isset($archive_requested[5])) ? $archive_requested[4] . $archive_requested[5] : $archive_requested[4] . "";

            foreach ($entry_archives as $entry_archive) {
               $_SESSION['month_archive'] = isset($entry_archive['month_archive']) ? $entry_archive['month_archive'] : $month;
               $_SESSION['year_archive'] = isset($entry_archive['year_archive']) ? $entry_archive['year_archive'] : $year;
            }

            $archive = (isset($_SESSION['year_archive']) && isset($_SESSION['month_archive'])) ? $app_url . DS . '?a=' . $_SESSION['year_archive'] . $_SESSION['month_archive'] : $year . $month;
         }

         break;

      case 'blog':

         break;

      default:

         return ['post' => $post_id, 'page' => $page_id, 'cat' => $cat_id, 'tag' => $tag_id, 'archive' => $archive];

         break;
   }

   return ['post' => $post_id, 'page' => $page_id, 'cat' => $cat_id, 'tag' => $tag_id, 'archive' => $archive];
}

/**
 * listen_request_path
 *
 * @param num|int $id
 * @param string $app_url
 * @return void
 */
function listen_request_path($id = null, $app_url = null)
{

   $request_path = new RequestPath();
   $rewrite = array();

   if (($request_path->matched == 'post') && ($id === $request_path->param1)) {

      $getPost = FrontHelper::grabPreparedFrontPostById($request_path->param1);
      $post_id = isset($getPost['ID']) ? abs((int)$getPost['ID']) : 0;
      $post_slug = isset($getPost['post_slug']) ? escape_html($getPost['post_slug']) : "";
      $rewrite['post'] = $app_url . DS . 'post' . DS .  $post_id . DS . $post_slug;

   } elseif (($request_path->matched == 'page') && ($id === $request_path->param1)) {

      $getPage = FrontHelper::grabPreparedFrontPageBySlug($request_path->param1);
      $page_slug = isset($getPage['post_slug']) ? escape_html($getPage['post_slug']) : "";
      $rewrite['page'] = $app_url . DS . 'page' . DS . $page_slug;

   } elseif (($request_path->matched == 'category') && ($id === $request_path->param1)) {

      $getCategory = FrontHelper::grabPreparedFrontTopicBySlug($request_path->param1);
      $category_slug = isset($getCategory['topic_slug']) ? escape_html($getCategory['topic_slug']) : "";
      $rewrite['cat'] = $app_url . DS . 'category' . DS . $category_slug;

   } elseif (($request_path->matched == 'tag') && ($id === $request_path->param1)) {

      $getTag = FrontHelper::grabFrontTag($request_path->param1);
      (isset($getTag['ID'])) ? abs((int)$getTag['ID']) : 0;
      $rewrite['tag'] = $app_url . DS . 'tag' . DS . $id;
      
   } elseif (($request_path->matched == 'archive') && ($id === $request_path->param1 . $request_path->param2)) {
      
      $month = isset($request_path->param1) ? escape_html($request_path->param1) : null;
      $year = isset($request_path->param2) ? escape_html($request_path->param2) : null;
      
      $rewrite['archive'] = $app_url . DS . 'archive' . DS . $month . DS . $year;

   } else {

      $getPost = FrontHelper::grabPreparedFrontPostById($id);
      $post_id = isset($getPost['ID']) ? abs((int)$getPost['ID']) : 0;
      $post_slug = isset($getPost['post_slug']) ? escape_html($getPost['post_slug']) : "";
      $rewrite['post'] = $app_url . DS . 'post' . DS . $post_id . DS . $post_slug;

      $getPage = FrontHelper::grabPreparedFrontPageBySlug($id);
      $page_slug = isset($getPage['post_slug']) ? escape_html($getPage['post_slug']) : "";
      $rewrite['page'] = $app_url . DS . 'page' . DS . $page_slug;

      $getCategory = FrontHelper::grabSimpleFrontTopic($id);
      $cat_slug = isset($getCategory['topic_slug']) ? escape_html($getCategory['topic_slug']) : "";
      $rewrite['cat'] = $app_url . DS . 'category' . DS . $cat_slug;

      $getTag = FrontHelper::grabFrontTag($id);
      (isset($getTag['ID'])) ? abs((int)$getTag['ID']) : 0;
      $rewrite['tag'] = $app_url . DS . 'tag' . DS . $id;

      $rewrite['archive'] = $app_url . DS . 'archive' . DS .$id;
   }
   
   return $rewrite;
}