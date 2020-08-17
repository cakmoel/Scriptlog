<?php
/**
 * Class PageEvent
 *
 * @category  Event Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

class PageEvent
{
  /**
   * page's ID
   * 
   * @var integer
   * 
   */
  private $pageId;
 
  /**
   * page's image
   * @var integer
   * 
   */
  private $page_image;

  /**
   * Author
   * 
   * @var string
   * 
   */
  private $author;

  /**
   * page's title
   * 
   * @var string
   * 
   */
  private $title;

  /**
   * Slug
   * property of URL SEO friendly
   * 
   * @var slug
   * 
   */
  private $slug;

  /**
   * Content
   * 
   * @var string
   * 
   */
  private $content;

  /**
   * Meta description
   * 
   * @var string
   * 
   */
  private $meta_desc;

  /**
   * Meta keywords
   * 
   * @var string
   * 
   */
  private $meta_key;
 
  /**
   * Page's status
   * 
   * @var string
   * 
   */
  private $page_status;

  /**
   * Post type
   * 
   * @var string
   * 
   */
  private $post_type;

  /**
   * Comment's status
   * 
   * @var string
   * 
   */
  private $comment_status;

  private $pageDao;

  private $validator;

  private $sanitizer;

  /**
   * Initialize or instanstiate of class propertis
   */
  public function __construct(PageDao $pageDao, FormValidator $validator, Sanitize $sanitizer)
  {
    $this->pageDao = $pageDao;
    $this->validator = $validator;
    $this->sanitizer = $sanitizer;
  }

  /**
   * set page id
   * 
   * @param integer $pageId
   * 
   */
  public function setPageId($pageId)
  {
    $this->pageId = $pageId;
  }

  public function setPageAuthor($author)
  {
    $this->author = $author;
  }

/**
 * set page's image
 *
 * @param number|interger $post_image
 * 
 */
  public function setPageImage($page_image)
  {
    $this->page_image = $page_image;
  }

  /**
   * set page title
   * 
   * @param string $title
   * 
   */
  public function setPageTitle($title)
  {
    $this->title = prevent_injection($title);
  }

  /**
   * set page slug
   * 
   * @param string $slug
   * 
   */
  public function setPageSlug($slug)
  {
    $this->slug = make_slug($slug);
  }
  
/**
 * set page content
 * 
 * @param string $content
 * 
 */
  public function setPageContent($content)
  {
    $this->content = purify_dirty_html($content);
  }

  /**
   * set meta description
   * 
   * @param string $meta_desc
   * 
   */
  public function setMetaDesc($meta_desc)
  {
    $this->meta_desc = prevent_injection($meta_desc);
  }

  /**
   * set meta keywords
   * 
   * @param string $meta_keys
   * 
   */
  public function setMetaKeys($meta_keys)
  {
    $this->meta_key = prevent_injection($meta_keys);
  }
  
  /**
   * set page status
   * 
   * @param string $page_status
   * 
   */
  public function setPublish($page_status)
  {
    $this->page_status = $page_status;
  }
  
  /**
   * Set post type
   *
   * @param string $post_type
   * @return void
   * 
   */
  public function setPostType($post_type)
  {
    $this->post_type = $post_type;    
  }
  
  public function setComment($comment_status)
  {
   $this->comment_status = $comment_status;
  }
 
  public function grabPages($type)
  {
    return $this->pageDao->findPages($type);
  }
  
  public function grabPage($id, $type)
  {
    return $this->pageDao->findPageById($id, $type, $this->sanitizer);
  }
  
  public function addPage()
  {
     
    $this->validator->sanitize($this->author, 'int');
    $this->validator->sanitize($this->title, 'string');
    $this->validator->sanitize($this->post_image, 'int');
   
    if ((!empty($this->meta_desc)) || (!empty($this->meta_key))) {
      $this->validator->sanitize($this->meta_desc, 'string');
      $this->validator->sanitize($this->meta_key, 'string');
    }
    
    if (!empty($this->post_image)) {

      return $this->pageDao->createPage([
           'media_id' => $this->post_image,
           'post_author' => $this->author,
           'post_date' => date("Y-m-d H:i:s"),
           'post_title' => $this->title,
           'post_slug' => $this->slug,
           'post_content' => $this->content,
           'post_summary' => $this->meta_desc,
           'post_keyword' => $this->meta_key,
           'post_status' => $this->page_status,
           'post_type' => $this->post_type,
           'comment_status' => $this->comment_status
      ]);
        
    } else {

      return $this->pageDao->createPage([
          'post_author' => $this->author,
          'post_date' => date("Y-m-d H:i:s"),
          'post_title' => $this->title,
          'post_slug' => $this->slug,
          'post_content' => $this->content,
          'post_summary' => $this->meta_desc,
          'post_keyword' => $this->meta_key,
          'post_status' => $this->page_status,
          'post_type' => $this->post_type,
          'comment_status' => $this->comment_status
      ]);
       
    }
  
  }
  
  public function modifyPage()
  {
    
    $this->validator->sanitize($this->pageId, 'int');
    $this->validator->sanitize($this->author, 'int');
    $this->validator->sanitize($this->title, 'string');
   
    if( ( !empty( $this->meta_desc ) )  || ( !empty( $this->meta_key ) ) ) {
        $this->validator->sanitize($this->meta_desc, 'string');
        $this->validator->sanitize($this->meta_key, 'string');
    }
    
    if(empty($this->post_image)) {
        
      return $this->pageDao->updatePage($this->sanitizer, [
          'post_author' => $this->author,
          'date_modified' => date("Y-m-d H:i:s"),
          'post_title' => $this->title,
          'post_slug' => $this->slug,
          'post_content' => $this->content,
          'post_summary' => $this->meta_desc,
          'post_keyword' => $this->meta_key,
          'post_type' => $this->post_type,
          'post_status' => $this->page_status,
          'comment_status' => $this->comment_status
      ], $this->pageId);
      
    } else {
        
       return $this->pageDao->updatePage($this->sanitizer, [
           'post_image' => $this->post_image,
           'date_modified' => date("Y-m-d H:i:s"),
           'post_title' => $this->title,
           'post_slug' => $this->slug,
           'post_content' => $this->content,
           'post_summary' => $this->meta_desc,
           'post_keyword' => $this->meta_key,
           'post_type' => $this->post_type,
           'post_status' => $this->page_status,
           'comment_status' => $this->comment_status
       ], $this->pageId);
       
    }
      
  }
  
  public function removePage()
  {
    
    $this->validator->sanitize($this->pageId, 'int');
     
    if (!$data_page = $this->pageDao->findPageById($this->pageId, $this->post_type, $this->sanitizer)) {
        direct_page('index.php?load=pages&error=pageNotFound', 404);
    }
    
    $media_id = $data_page['media_id'];

    $medialib = new MediaDao();
    $media_data = $medialib->findMediaById((int)$media_id, $this->sanitizer);
    $page_image = basename($media_data['media_filename']);

    if ($page_image !== '') {
        
        if (is_readable(__DIR__ . '/../../'.APP_IMAGE.$page_image)) {

            unlink(__DIR__ . '/../../'.APP_IMAGE.$page_image);
            unlink(__DIR__ . '/../../'.APP_IMAGE_LARGE.'large_'.$page_image);
            unlink(__DIR__ . '/../../'.APP_IMAGE_MEDIUM.'medium_'.$page_image);
            unlink(__DIR__ . '/../../'.APP_IMAGE_SMALL.'small_'.$page_image);
            
        }
        
        return $this->pageDao->deletePage($this->pageId, $this->sanitizer, $this->post_type);
        
    } else {
        
        return $this->pageDao->deletePage($this->pageId, $this->sanitizer, $this->post_type);
        
    }
    
  }
  
  public function postStatusDropDown($selected = "") 
  {
    return $this->pageDao->dropDownPostStatus($selected);
  }
  
  public function commentStatusDropDown($selected = "")
  {
    return $this->pageDao->dropDownCommentStatus($selected);
  }
  
  public function pageAuthorId()
  {

    if(isset($_COOKIE['scriptlog_cookie_id'])) {

      return $_COOKIE['scriptlog_cookie_id'];

    }

    if(isset(Session::getInstance()->scriptlog_session_id)) {

       return Session::getInstance()->scriptlog_session_id;

    }

  }
  
  public function totalPages($data = null)
  {
    return $this->pageDao->totalPageRecords($data);
  }
  
}