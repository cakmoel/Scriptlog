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

  $theme_meta = array();
  $post_id = null;
  $page_id = null;
  $post_title = null;
  $page_title = null;
  $author = null;
  $keyword = null;
  $description = null;
  $topic_title = null;
  $canonical = null;
  $image = null;
  $thumbnailUrl = null;
  $date_published = null;

  if ( is_permalink_enabled() === 'yes') {

    $uri = new RequestPath();
  
  switch ($uri->param1) {

      case 'post':

        if (!empty($uri->param2)) {

          $read_post = FrontHelper::grabPreparedFrontPostById($uri->param2);

          $post_title = (!empty($read_post['post_title'])) ? escape_html($read_post['post_title']) : app_info()['site_name'];
          $keyword = (!empty($read_post['post_keyword'])) ? escape_html($read_post['post_keyword']) : $post_title;
          $description = (!empty($read_post['post_summary'])) ? escape_html($read_post['post_summary']) : $post_title;
          $author = (!empty($read_post['user_login'])) ? escape_html($read_post['user_login']) : escape_html($read_post['user_fullname']); 
          $image = (!empty($read_post['media_filename'])) ? invoke_webp_image(escape_html($read_post['media_filename'])) : app_url().DS.APP_IMAGE.'scriptlog-612x221.jpg';

        }
        
        $theme_meta['site_schema'] = (is_null($post_title)) ? generate_schema_org(app_info()['site_name']) : generate_schema_org(ucfirst(trim($post_title)) . ' | ' . app_info()['site_name']);
        
        $theme_meta['site_meta_tags'] = generate_meta_tags($post_title, $description, $keyword, $author, $image);

        break;

      case 'page':

        if (!empty($uri->param2)) {

          $read_page = FrontHelper::grabPreparedFrontPageBySlug($uri->param2);

          $page_title = (!empty($read_page['post_title'])) ? escape_html($read_page['post_title']) : null;


        }

        $theme_meta['site_schema'] = (is_null($page_title)) ? generate_schema_org(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim($page_title)) . ' | ' . escape_html(app_info()['site_name']));
        
        $theme_meta['site_meta_tag'] = generate_meta_tags();

        break;

      case 'blog':

        if (empty($uri->param2) && empty($uri->param3)) {

         // $theme_meta['site_title'] = title_tag($uri->param1 . ' &#8211; ' . app_info()['site_name']);
           
        }
        
        $theme_meta['site_meta_tags'] = generate_meta_tags();

        break;

      case 'category':

        if (!empty($uri->param2)) {

          $read_topic = FrontHelper::grabPreparedFrontTopicBySlug($uri->param2);

          $topic_title = (!empty($read_topic['topic_title'])) ? escape_html($read_topic['topic']) : null;

        }

        $theme_meta['site_schema'] = (is_null($topic_title)) ? generate_schema_org(escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim($topic_title)) . ' | ' . escape_html(app_info()['site_name']));

        $theme_meta['site_meta_tags'] = generate_meta_tags();

        break;

      case 'archive':

        if (!empty($uri->param1)) {

          $theme_meta['site_schema'] = generate_schema_org(ucfirst(trim(escape_html(grab_month($uri->param2)).' '. escape_html($uri->param3)))  . ' | ' . escape_html(app_info()['site_name']));
        
          $theme_meta['site_meta_tags'] = generate_meta_tags();

        }

        break;

      default:

        $image = app_url().DS.APP_IMAGE.'scriptlog-1200x630.jpg';
        $thumbnailUrl = app_url().DS.APP_IMAGE.'scriptlog-612x221.jpg';
        $theme_meta['site_schema'] = generate_schema_org(app_info()['site_name'], app_url(), $image, app_info()['site_description'], app_info()['site_tagline'], $thumbnailUrl, date(DATE_ATOM));
        $theme_meta['site_meta_tags'] = generate_meta_tags(app_info()['site_name'], app_info()['site_description'], app_info()['site_keywords'], APP_CODENAME, $image, app_url());

        break;

  }

    return array('site_schema' => $theme_meta['site_schema'], 'site_meta_tags' => $theme_meta['meta_tag']);

  } else {

    switch (HandleRequest::isQueryStringRequested()['key']) {
      
      case 'p':

        if ( ( empty(HandleRequest::isQueryStringRequested()['value'])) || ( HandleRequest::isQueryStringRequested()['value'] == '') ) {

          http_response_code(500);
          throw new TypeError("Argument passed must be of the type string, null given");

        } else {

          $read_post = FrontHelper::grabSimpleFrontPost(HandleRequest::isQueryStringRequested()['value']);

          $post_id = (!empty($read_post['ID'])) ? abs((int)$read_post['ID']): NULL;
          $post_title = (!empty($read_post['post_title'])) ? escape_html($read_post['post_title']) : app_info()['site_name'];
          $keyword = (!empty($read_post['post_keyword'])) ? escape_html($read_post['post_keyword']) : app_info()['site_keywords'];
          $description = (!empty($read_post['post_summary'])) ? escape_html($read_post['post_summary']) : app_info()['site_description'];
          $author = (!empty($read_post['user_login'])) ? escape_html($read_post['user_login']) : APP_TITLE; 
          $image = (!empty($read_post['media_filename'])) ? invoke_webp_image(escape_html($read_post['media_filename'])) : app_url().DS.APP_IMAGE.'scriptlog-612x221.jpg';
          $canonical = (!empty($read_post['post_slug'])) ? app_url() . DS . '?p=' . $post_id : app_url();

        }

        $theme_meta['site_schema'] = (is_null($post_id)) ? generate_schema_org(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim($post_title)) . ' | ' . app_info()['site_name']);

        $theme_meta['site_meta_tags'] = (is_null($post_id)) ? generate_meta_tags(ucfirst(trim('page not found')) . ' | ' . $post_title, $description, $keyword, $author, $image, $canonical) : generate_meta_tags($post_title, $description, $keyword, $author, $image, $canonical);

        break;

      case 'pg':

        if ( ! empty(HandleRequest::isQueryStringRequested()['value'])) {

          $read_page = FrontHelper::grabSimpleFrontPage(HandleRequest::isQueryStringRequested()['value']);

          $page_id = (!empty($read_page['ID'])) ? $read_page['ID'] : null;
          $page_title = (!empty($read_page['post_title'])) ? escape_html($read_page['post_title']) : app_info()['site_name'];
          $keyword = (!empty($read_page['post_keyword'])) ? escape_html($read_page['post_keyword']) : app_info()['site_keywords'];
          $description = (!empty($read_page['post_summary'])) ? escape_html($read_page['$post_summary']) : app_info()['site_description'];
          $author = (!empty($read_page['user_login'])) ? escape_html($read_page['user_login']) : APP_TITLE;
          $image = (!empty($read_page['media_filename'])) ? invoke_webp_image(escape_html($read_page['media_filename'])) : app_url().DS.APP_IMAGE.'scriptlog-612x221.jpg';
          $canonical = (!empty($read_page['post_slug'])) ? app_url() . DS . '?pg=' . $page_id : app_url();

        }

        $theme_meta['site_schema'] = (is_null($page_id)) ? generate_schema_org(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim($page_title)) . ' | ' . app_info()['site_name']); 

        $theme_meta['site_meta_tags'] = (is_null($page_id)) ? generate_meta_tags(ucfirst(trim('page not found')) . ' | ' . $page_title, $description, $keyword, $author, $image, $canonical) : generate_meta_tags($post_title, $description, $keyword, $author, $image, $canonical);
        
        break;
      
      case 'cat':

        break;

      default:
        
        $image = app_url().DS.APP_IMAGE.'scriptlog-1200x630.jpg';
        $thumbnailUrl = app_url().DS.APP_IMAGE.'scriptlog-612x221.jpg';
        $theme_meta['site_schema'] = generate_schema_org(app_info()['site_name'], app_url(), $image, app_info()['site_description'], app_info()['site_tagline'], $thumbnailUrl, date(DATE_ATOM));
        $theme_meta['site_meta_tags'] = generate_meta_tags(app_info()['site_name'], app_info()['site_description'], app_info()['site_keywords'], APP_CODENAME, $image, app_url());

        break;

    }

    return array('site_schema' => $theme_meta['site_schema'], 'site_meta_tags' => $theme_meta['site_meta_tags']);

  }
  
}