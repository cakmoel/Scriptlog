<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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

  $scriptlog_image = app_url() . DS . APP_IMAGE . 'scriptlog-1200x630.jpg';
  $scriptlog_imgthumb = app_url() . DS . APP_IMAGE . 'scriptlog-612x221.jpg';

  if (is_permalink_enabled() === 'yes') {

    $uri = class_exists('RequestPath') ? new RequestPath() : "";
    return metatag_by_path($scriptlog_image, $scriptlog_imgthumb, $uri);

  } else {

    return metatag_by_query(HandleRequest::isQueryStringRequested()['key'], HandleRequest::isQueryStringRequested()['value'], $scriptlog_image, $scriptlog_imgthumb);
  }
}

/**
 * metatag_by_path
 *
 * @param object $param1
 * @param object $param2
 * @param string $scriptlog_image
 * @param string $scriptlog_imgthumb
 *
 */
function metatag_by_path($scriptlog_image, $scriptlog_imgthumb, $uri)
{

  $uri = is_a($uri, 'RequestPath') ? $uri : null;

  $theme_meta = array();
  $post_id = null;
  $created_at = null;
  $modified_at = null;
  $page_id = null;
  $post_title = null;
  $page_title = null;
  $author = null;
  $keyword = null;
  $description = null;
  $topic_id = null;
  $topic_title = null;
  $canonical = null;
  $image = null;
  $date_published = null;
  $date_modified = null;

  switch ($uri->matched) {

    case 'post':

      if (!empty($uri->param1)) {

        $read_post = FrontHelper::grabPreparedFrontPostById($uri->param1);
        $post_id = (!empty($read_post['ID'])) ? abs((int)$read_post['ID']) : null;
        $post_title = (!empty($read_post['post_title'])) ? escape_html($read_post['post_title']) : app_info()['site_name'];
        $keyword = (!empty($read_post['post_keyword'])) ? escape_html($read_post['post_keyword']) : $post_title;
        $description = (!empty($read_post['post_summary'])) ? escape_html($read_post['post_summary']) : $post_title;
        $author = (!empty($read_post['user_login'])) ? escape_html($read_post['user_login']) :  APP_TITLE;
        $image = (!empty($read_post['media_filename'])) ? invoke_webp_image(escape_html($read_post['media_filename'])) : $scriptlog_imgthumb;
        $canonical = (!empty($read_post['post_slug'])) ? app_url() . DS . 'post' . DS . $post_id . DS . escape_html($read_post['post_slug']) : app_url();
        $created_at = (!empty($read_post['post_date'])) ? convert_to_timestamp(escape_html($read_post['post_date'])) : "";
        $modified_at = (!empty($read_post['post_modified'])) ? convert_to_timestamp(escape_html($read_post['post_modified'])) : "";
        $date_published = (!empty($created_at)) ? convert_to_atom($created_at) : date(DATE_ATOM);
        $date_modified = (!empty($modified_at)) ? convert_to_atom($modified_at) : date(DATE_ATOM);
      }

      $theme_meta['site_schema'] = is_null($post_id) ? generate_schema_org(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim($post_title)) . ' | ' . app_info()['site_name'], $canonical, $image, $description, $keyword, $scriptlog_imgthumb, $date_published, $date_modified);
      $theme_meta['site_meta_tags'] = is_null($post_id) ? generate_meta_tags(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_meta_tags($post_title, $description, $keyword, $author, $image, $canonical);

      break;

    case 'page':

      if (!empty($uri->param1)) {

        $read_page = FrontHelper::grabPreparedFrontPageBySlug($uri->param1);
        $page_id = (!empty($read_page['ID'])) ? abs((int)$read_page['ID']) : null;
        $page_title = (!empty($read_page['post_title'])) ? escape_html($read_page['post_title']) : app_info()['site_name'];
        $keyword = (!empty($read_page['post_keyword'])) ? escape_html($read_page['post_keyword']) : $page_title;
        $description = (!empty($read_page['post_summary'])) ? escape_html($read_page['post_summary']) : $page_title;
        $author = (!empty($read_page['user_login'])) ? escape_html($read_page['user_login']) : APP_TITLE;
        $image = (!empty($read_page['media_filename'])) ? invoke_webp_image(escape_html($read_page['media_filename'])) : $scriptlog_imgthumb;
        $canonical = (!empty($read_page['post_slug'])) ? app_url() . DS . 'page' . DS . escape_html($read_page['post_slug']) : app_url();
        $created_at = (!empty($read_page['post_date'])) ? convert_to_timestamp(escape_html($read_page['post_date'])) : "";
        $modified_at = (!empty($read_page['post_modified'])) ? convert_to_timestamp(escape_html($read_page['post_modified'])) : "";
        $date_published = (!empty($created_at)) ? convert_to_atom($created_at) : date(DATE_ATOM);
        $date_modified = (!empty($modified_at)) ? convert_to_atom($modified_at) : date(DATE_ATOM);
      }

      $theme_meta['site_schema'] = is_null($page_id) ? generate_schema_org(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim($page_title)) . ' | ' . app_info()['site_name'], $canonical, $image, $description, $keyword, $scriptlog_imgthumb, $date_published, $date_modified);
      $theme_meta['site_meta_tags'] = is_null($page_id) ? generate_meta_tags(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_ame'])) : generate_meta_tags($page_title, $description, $keyword, $author, $image, $canonical);

      break;

    case 'blog':

      $date_published = date(DATE_ATOM);
      $date_modified = date(DATE_ATOM);
      $theme_meta['site_schema'] = generate_schema_org(app_info()['site_name'] . ' | ' . app_info()['site_tagline'], app_url(), $scriptlog_image, app_info()['site_description'], app_info()['site_keywords'], $scriptlog_imgthumb, $date_published, $date_modified);
      $theme_meta['site_meta_tags'] = generate_meta_tags(ucfirst(trim(app_info()['site_name'] . ' | ' . app_info()['site_tagline'])), 'Blog', app_info()['site_description'], APP_TITLE, $scriptlog_image, app_url() . DS . 'blog');

      break;

    case 'category':

      if (!empty($uri->param1)) {

        $read_topic = FrontHelper::grabPreparedFrontTopicBySlug($uri->param1);
        $topic_id = (!empty($read_topic['ID'])) ? abs((int)$read_topic['ID']) : null;
        $topic_title = (!empty($read_topic['topic_title'])) ? escape_html($read_topic['topic_title']) : app_info()['site_name'];
        $description = 'Category: ' . $topic_title . ' | ' . escape_html(app_info()['site_description']);
        $keyword = escape_html(app_info()['site_keywords']);
        $canonical = app_url() . DS . 'category' . DS . $uri->param1;
        $date_published = date(DATE_ATOM);
      }

      $theme_meta['site_schema'] = (is_null($topic_id)) ? generate_schema_org(ucfirst(trim('page not fiund')) . ' | ' . escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim($topic_title)) . ' | ' . escape_html(app_info()['site_name']), $canonical, $scriptlog_image, $description, $keyword, $scriptlog_imgthumb, $date_published);
      $theme_meta['site_meta_tags'] = (is_null($topic_id)) ? generate_meta_tags(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_meta_tags(ucfirst(trim($topic_title)) . ' | ' . escape_html(app_info()['site_name']), $description, $keyword, APP_TITLE, $scriptlog_image, $canonical);

      break;

    case 'archive':

      $month = (isset($uri->param1)) ? $uri->param1 : null;
      $year = (isset($uri->param2)) ? $uri->param2 : null;

      $canonical = ((isset($month)) || (isset($year)) ? app_url() . DS . 'archive ' . DS . $month . DS . $year : app_url());
      $description = 'Archive: ' . ' - ' . $month  . ' ' . $year;
      $keyword = escape_html(app_info()['site_keywords']);
      $month_name = date("F Y", mktime(0, 0, 0, intval($month), 7, intval($year)));
      $date_published = date(DATE_ATOM);

      $theme_meta['site_schema'] = (is_null($month)) ? generate_schema_org(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name']))  : generate_schema_org(ucfirst(trim(escape_html($month_name))) . ' | ' . escape_html(app_info()['site_name']), $canonical, $scriptlog_image, $description, $keyword, $scriptlog_imgthumb, $date_published);
      $theme_meta['site_meta_tags'] = (is_null($month)) ? generate_meta_tags(ucfirst(trim('page not found')).' | ' . escape_html(app_info()['site_name'])) : generate_meta_tags( 'Archive:  '.  ucfirst(trim($month_name)), $description, $keyword, APP_TITLE, $scriptlog_image, $canonical);

      break;

    case 'tag':

      $tag_item = isset($uri->param1) ? $uri->param1 : null;
      
      $canonical = ((isset($tag)) || (isset($tag_item)) ? app_url() . DS . 'tag' . DS . $tag_item : app_url());
      $description = 'Tag:' . ' - ' . $tag_item;  
      $keyword = escape_html(app_info()['site_keywords']);
      $theme_meta['site_schema'] = (is_null($tag_item)) ? generate_schema_org(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim(escape_html($tag_item))) . ' | ' . escape_html(app_info()['site_name']), $canonical, $scriptlog_image, $description, $keyword, $scriptlog_imgthumb);
      $theme_meta['site_meta_tags'] = (is_null($tag_item)) ? generate_meta_tags(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_meta_tags('Tag: '.ucfirst(trim($tag_item)), $description, $keyword, APP_TITLE, $scriptlog_image, $canonical);

      break;
      
    default:

      $theme_meta['site_schema'] = generate_schema_org(app_info()['site_name'], app_url(), $scriptlog_image, app_info()['site_description'], app_info()['site_tagline'], $scriptlog_imgthumb, date(DATE_ATOM));
      $theme_meta['site_meta_tags'] = generate_meta_tags(app_info()['site_name'], app_info()['site_description'], app_info()['site_keywords'], APP_CODENAME, $scriptlog_image, app_url());

      break;
  }

  return array('site_schema' => $theme_meta['site_schema'], 'site_meta_tags' => $theme_meta['site_meta_tags']);
}

/**
 * metatag_by_query
 *
 * @param string $key
 * @param string $value
 * @param string $scriptlog_image
 * @param string $scriptlog_imgthumb
 * 
 */
function metatag_by_query($key, $value, $scriptlog_image, $scriptlog_imgthumb)
{

  $theme_meta = array();
  $post_id = null;
  $page_id = null;
  $category_id = null;
  $post_title = null;
  $page_title = null;
  $category_title = null;
  $tag_id = null;
  $tag_title = null;
  $author = null;
  $keyword = null;
  $description = null;
  $category_title = null;
  $canonical = null;
  $image = null;
  $created_at = null;
  $modified_at = null;
  $date_published = null;
  $date_modified = null;

  switch ($key) {

    case 'p':

      if ((empty($value)) || ($value === '')) {

        http_response_code(404);
        throw new InvalidArgumentException("Argument passed must be of the type string, numeric or integer, null given");
      } else {

        $read_post = class_exists('FrontHelper') ? FrontHelper::grabSimpleFrontPost($value) : "";

        $post_id = (!empty($read_post['ID'])) ? abs((int)$read_post['ID']) : null;
        $post_title = (!empty($read_post['post_title'])) ? escape_html($read_post['post_title'])  . " | " . app_info()['site_name'] : app_info()['site_name'];
        $keyword = (!empty($read_post['post_keyword'])) ? escape_html($read_post['post_keyword']) : app_info()['site_keywords'];
        $description = (!empty($read_post['post_summary'])) ? escape_html($read_post['post_summary']) : app_info()['site_description'];
        $author = (!empty($read_post['user_login'])) ? escape_html($read_post['user_login']) : APP_TITLE;
        $image = (!empty($read_post['media_filename'])) ? invoke_webp_image(escape_html($read_post['media_filename'])) : app_url() . DS . APP_IMAGE . 'scriptlog-612x221.jpg';
        $canonical = (!empty($read_post['post_slug'])) ? app_url() . DS . '?p=' . $post_id : app_url();
        $created_at = (!empty($read_post['post_date'])) ? convert_to_timestamp(escape_html($read_post['post_date'])) : "";
        $modified_at = (!empty($read_post['post_modified'])) ? convert_to_timestamp(escape_html($read_post['post_modified'])) : "";
        $date_published = (!empty($created_at))? convert_to_atom($created_at) : date(DATE_ATOM);
        $date_modified = (!empty($modified_at)) ? convert_to_atom($modified_at) : date(DATE_ATOM);

      }

      $theme_meta['site_schema'] = (is_null($post_id)) ? generate_schema_org(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim($post_title)) . ' | ' . app_info()['site_name'], $canonical, $image, $description, $keyword, $scriptlog_imgthumb, $date_published, $date_modified);
      $theme_meta['site_meta_tags'] = (is_null($post_id)) ? generate_meta_tags(ucfirst(trim('page not found')) . ' | ' . app_info()['site_name'], app_info()['site_description'], app_info()['site_keywords'], APP_CODENAME, $scriptlog_image, app_url()) : generate_meta_tags($post_title, $description, $keyword, $author, $image, $canonical);

      break;

    case 'pg':

      if (!empty($value)) {

        $read_page = class_exists('FrontHelper') ? FrontHelper::grabSimpleFrontPage($value) : "";

        $page_id = (!empty($read_page['ID'])) ? $read_page['ID'] : null;
        $page_title = (!empty($read_page['post_title'])) ? escape_html($read_page['post_title']) . " | " . app_info()['site_name'] : app_info()['site_name'];
        $keyword = (!empty($read_page['post_keyword'])) ? escape_html($read_page['post_keyword']) : app_info()['site_keywords'];
        $description = (!empty($read_page['post_summary'])) ? escape_html($read_page['post_summary']) : app_info()['site_description'];
        $author = (!empty($read_page['user_login'])) ? escape_html($read_page['user_login']) : APP_TITLE;
        $image = (!empty($read_page['media_filename'])) ? invoke_webp_image(escape_html($read_page['media_filename'])) : app_url() . DS . APP_IMAGE . 'scriptlog-612x221.jpg';
        $canonical = (!empty($read_page['post_slug'])) ? app_url() . DS . '?pg=' . $page_id : app_url();
        $created_at = (!empty($read_page['post_date'])) ? convert_to_timestamp(escape_html($read_page['post_date'])) : "";
        $modified_at = (!empty($read_page['post_modified'])) ? convert_to_timestamp(escape_html($read_page['post_modified'])) : "";
        $date_published = (!empty($created_at)) ? convert_to_atom($created_at) : date(DATE_ATOM);
        $date_modified = (!empty($modified_at)) ? convert_to_atom($modified_at) : date(DATE_ATOM);
        
      } else {

        http_response_code(404);
        throw new InvalidArgumentException("Argument passed must be of the type string, numeric or integer, null given");
      }

      $theme_meta['site_schema'] = (is_null($page_id)) ? generate_schema_org(ucfirst(trim('page not found')) . ' | ' . escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim($page_title)) . ' | ' . app_info()['site_name'], $canonical, $image, $description, $keyword, $scriptlog_imgthumb, $date_published, $date_modified);
      $theme_meta['site_meta_tags'] = (is_null($page_id)) ? generate_meta_tags(ucfirst(trim('page not found')) . ' | ' . app_info()['site_name']) : generate_meta_tags($page_title, $description, $keyword, $author, $image, $canonical);

      break;

    case 'cat':

      if (!empty($value)) {

        $read_category = class_exists('FrontHelper') ? FrontHelper::grabSimpleFrontTopic($value) : "";

        $category_id = (!empty($read_category['ID'])) ? $read_category['ID'] : null;
        $category_title = (!empty($read_category['topic_title'])) ? escape_html($read_category['topic_title']) : app_info()['site_name'];
        $keyword = (isset($category_title)) ? escape_html($read_category['topic_title']) : app_info()['site_keywords'];
        $description = (isset($category_title)) ? escape_html($read_category['topic_title']) : app_info()['site_description'];
        $canonical = (!empty($read_category['topic_slug'])) ? app_url() . DS . '?cat=' . $category_id : app_url();
        $date_published = date(DATE_ATOM);
        $date_modified = date(DATE_ATOM);

      } else {

        http_response_code(404);
        throw new InvalidArgumentException("Argument passed must be of the type string, numeric or integer, null given");
      }

      $theme_meta['site_schema'] = (is_null($category_id)) ? generate_schema_org(ucfirst(trim('page not found')) . '|' . escape_html(app_info()['site_name'])) : generate_schema_org(ucfirst(trim($category_title)) . '|' . app_info()['site_name'], $canonical, $image, $description, $keyword, $scriptlog_imgthumb, $date_published, $date_modified);
      $theme_meta['site_meta_tags'] = (is_null($category_id)) ? generate_meta_tags(ucfirst(trim('page not found')) . '|' . app_info()['site_name'], app_info()['site_description'], app_info()['site_keywords'], APP_TITLE, $scriptlog_image, app_url()) : generate_meta_tags($category_title, $description, $keyword, APP_TITLE, $scriptlog_image, $canonical);

      break;

    case 'a':

      if (!empty($value)) {

        $archive_requested = class_exists('HandleRequest') ? preg_split("//", HandleRequest::isQueryStringRequested()['value'], -1, PREG_SPLIT_NO_EMPTY) : preg_split("//", $value, -1, PREG_SPLIT_NO_EMPTY);

        $year = (isset($archive_requested[0]) && isset($archive_requested[1]) && isset($archive_requested[2]) && isset($archive_requested[3])) ? $archive_requested[0] . $archive_requested[1] . $archive_requested[2] . $archive_requested[3] : null;
        $month = (isset($archive_requested[4]) && isset($archive_requested[5])) ? $archive_requested[4] . $archive_requested[5] : $archive_requested[4] . "";

      
        $canonical = (!empty($month)) ? app_url() . DS . '?a=' .$year.$month : app_url();
        $month_num = isset($month) ? safe_html($month) : "";
        $monthObj = class_exists('DateTime') ? DateTime::createFromFormat('!m', $month_num) : "";
        $month_name = method_exists($monthObj, 'format') ? $monthObj->format('F') : "";
        $month_name = isset($month_name) ? $month_name : date("F", mktime(0, 0, 0, $month, 10)); 

      } else {

        http_response_code(404);
        throw new InvalidArgumentException("Argument passed must be of the type string, numeric or integer, null given");
      }

      $theme_meta['site_schema'] = (is_null($month)) ? generate_schema_org(ucfirst(trim('page not found')) . '|' . app_info()['site_name']) : generate_schema_org(ucfirst(trim(app_info()['site_name'] . '|' . $month . $year)), $canonical, $scriptlog_image, app_info()['site_description'], app_info()['site_keywords'], $scriptlog_imgthumb, date(DATE_ATOM));
      $theme_meta['site_meta_tags'] = (is_null($month)) ? generate_meta_tags(ucfirst(trim('page not found')), app_info()['site_description'], app_info()['site_keywords'], APP_TITLE, $scriptlog_image, app_url()) : generate_meta_tags($month_name . ' - ' . app_info()['site_name'], app_info()['site_description'], app_info()['site_keywords'], APP_TITLE, $scriptlog_image, $canonical);
      
      break;

    case 'tag':

      if (!empty($value)) {

        $tag_requested = class_exists('HandleRequest') ? HandleRequest::isQueryStringRequested()['value'] : escape_html($value);
        $tag = class_exists('FrontHelper') ? FrontHelper::simpleSearchingTag($tag_requested) : "";

        $canonical = app_url() . DS . '?tag=' . $tag_requested;
        $tag_id = isset($tag['ID']) ? intval($tag['ID']) : null;
        $tag_title = isset($tag_requested) ? $tag_requested : null;
        $description = isset($tag['post_content']) ? html_entity_decode(paragraph_l2br(htmlout($tag['post_content']))) : null;
        $keyword = isset($tag['post_summary']) ? htmlout($tag['post_summary']) : null;
        
      } else {

        http_response_code(404);
        throw new InvalidArgumentException("Argument passed must be of the type string, numeric or integer, null given");
      }

      $theme_meta['site_schema'] = (is_null($tag_id)) ? generate_schema_org(ucfirst(trim('page not found')) . '' . app_info()['site_name']) : generate_schema_org(strtolower(trim(app_info()['site_name'])), $canonical, $scriptlog_image, $description, $tag_title, $scriptlog_imgthumb, date(DATE_ATOM));
      $theme_meta['site_meta_tags'] = (is_null($tag_title)) ? generate_meta_tags(ucfirst(trim('page not found')), app_info()['site_description'], app_info()['site_keywords'], APP_TITLE, $scriptlog_image, app_url()) . '' . app_info()['site_name'] : generate_meta_tags(strtolower(trim(str_replace('-', ' ', $value))), $description, $keyword, app_info()['site_name'], $scriptlog_image, $canonical);

      break;

    case 'blog':

      $theme_meta['site_schema'] = generate_schema_org(ucfirst(trim('blog')) . ' | ' . app_info()['site_name'], app_url(), $scriptlog_image, app_info()['site_description'], app_info()['site_tagline'], $scriptlog_imgthumb, date(DATE_ATOM));
      $theme_meta['site_meta_tags'] = generate_meta_tags(ucfirst(trim('blog')) . ' | ' . app_info()['site_name'], app_info()['site_description'], app_info()['site_keywords'], APP_TITLE, $scriptlog_image, app_url());

      break;

    default:

      $theme_meta['site_schema'] = isset(app_info()['site_name']) ? generate_schema_org(app_info()['site_name'], app_url(), $scriptlog_image, app_info()['site_description'], app_info()['site_tagline'], $scriptlog_imgthumb, date(DATE_ATOM)) : "";
      $theme_meta['site_meta_tags'] = isset(app_info()['site_name']) ? generate_meta_tags(app_info()['site_name'], app_info()['site_description'], app_info()['site_keywords'], APP_TITLE, $scriptlog_image, app_url()) : "";

      break;
  }

  return array('site_schema' => $theme_meta['site_schema'], 'site_meta_tags' => $theme_meta['site_meta_tags']);
}
