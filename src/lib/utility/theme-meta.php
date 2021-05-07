<?php
/**
 * theme_meta
 * 
 * Display meta tag, link tag, title tag based on client request
 *
 * @category Function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
function theme_meta()
{

  $uri = new RequestPath();
  
  $theme_meta = array();

  switch ($uri->param1) {

      case 'post':

        if (!empty($uri->param2)) {

          $read_post = FrontHelper::frontPostById($uri->param2);

          $post_title = (!empty($read_post['post_title'])) ? escape_html($read_post['post_title']) : null;
           
        }
        
        $theme_meta['site_title'] = (is_null($post_title)) ? title_tag(app_info()['site_name']) : title_tag(ucfirst(trim($post_title)) . ' &#8211; ' . app_info()['site_name']);
        
        $theme_meta['meta_tag'] = meta_tag();

        break;

      case 'page':

        if (!empty($uri->param2)) {

           $read_page = FrontHelper::frontPageBySlug($uri->param2);

           $page_title = (!empty($read_page['post_title'])) ? escape_html($read_page['post_title']) : null;

        }

        $theme_meta['site_title'] = (is_null($page_title)) ? title_tag(escape_html(app_info()['site_name'])) : title_tag(ucfirst(trim($page_title)) . ' &#8211; ' . escape_html(app_info()['site_name']));
        
        $theme_meta['meta_tag'] = meta_tag();

        break;

      case 'blog':

        if (empty($uri->param2) && empty($uri->param3)) {

          $theme_meta['site_title'] = title_tag($uri->param1 . ' &#8211; ' . app_info()['site_name']);
           
        }
        
        $theme_meta['meta_tag'] = meta_tag();

        break;

      case 'category':

        if (!empty($uri->param2)) {

          $read_topic = FrontHelper::frontTopicBySlug($uri->param2);

          $topic_title = (!empty($read_topic['topic_title'])) ? escape_html($read_topic['topic']) : null;

        }

        $theme_meta['site_title'] = (is_null($topic_title)) ? title_tag(escape_html(app_info()['site_name'])) : title_tag(ucfirst(trim($topic_title)) . ' &#8211; ' . escape_html(app_info()['site_name']));

        $theme_meta['meta_tag'] = meta_tag();

        break;

      case 'archive':

        if (!empty($uri->param1)) {

          $theme_meta['site_title'] = title_tag(ucfirst(trim(escape_html($uri->param1)) .' '. escape_html($uri->param3)) . ' &#8211; ' . escape_html(app_info()['site_name']));
        
          $theme_meta['meta_tag'] = meta_tag();

        }

        break;

      default:

        $meta_title = (!empty(app_info()['site_name'])) ? app_info()['site_name']: "";

        $theme_meta['site_title'] = title_tag($meta_title);
        $theme_meta['meta_tag'] = meta_tag();

        break;

  }

  return array('site_title' => $theme_meta['site_title'], 'meta_tag' => $theme_meta['meta_tag']);

}

/**
 * title_tag
 *
 * @param string $title
 * @return void
 * 
 */
function title_tag($title)
{

$tag_title = <<<_TITLE

<title>$title</title>

_TITLE;

return $tag_title;

}

// function meta_tag
function meta_tag()
{

$meta_title = app_info()['site_name'];
$meta_desc = app_info()['site_description'];
$meta_key = app_info()['site_keywords'];
$meta_tagline = app_info()['site_tagline'];

$meta_tag = <<<_META

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="$meta_title, $meta_desc">
<meta name="keywords" content="$meta_key, $meta_tagline">

_META;

return $meta_tag;

}
