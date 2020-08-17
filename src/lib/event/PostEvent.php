<?php 
/**
 * PostEvent Class
 *
 * @category  Event Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PostEvent
{
  /**
   * post's ID
   * @var integer
   */
  private $postId;
  
  /**
   * post's Image
   * @var integer
   */
  private $post_image;

  /**
   * author 
   * @var string
   */
  private $author;
  
  /**
   * post's title 
   * @var string
   */
  private $title;
  
  /**
   * post's URL SEO Friendly
   * @var string
   */
  private $slug;
  
  /**
   * post's content
   * @var string
   */
  private $content;
  
  /**
   * post's summary 
   * it will be used for meta_description tag
   * 
   * @var string
   */
  private $meta_desc;
  
  /**
   * post's keyword
   * it will be used for meta_keyword tag
   * 
   * @var string
   */
  private $meta_key;

  /**
   * post's tags
   * the tags will be added to the posts
   * 
   * @var string
   * 
   */
  private $post_tags;
  
  /**
   * post's status
   * published or save as draft
   * 
   * @var string
   */
  private $post_status;
  
  /**
   * comment's status
   * is comment opened(allowed) or closed(not allowed)
   * 
   * @var string
   */
  private $comment_status;
  
  /**
   * post's topic
   * 
   * @var integer
   */
  private $topics; 

  private $postDao, $validator, $sanitizer;
 
  /**
   * Constructor
   * 
   * @param object $postDao
   * @param object $validator
   * @param object $sanitizer
   * 
   */
  public function __construct(PostDao $postDao, FormValidator $validator, Sanitize $sanitizer)
  {
     $this->postDao = $postDao;
     $this->validator = $validator;
     $this->sanitizer = $sanitizer;
  }
  
  /**
   * set post's ID
   * 
   * @param integer $postId
   */
  public function setPostId($postId)
  {
    $this->postId = $postId;    
  }

  /**
   * set post's image
   * 
   * @param string $post_image
   * 
   */
  public function setPostImage($post_image)
  {
    $this->post_image = $post_image;
  }

  /**
   * set post's author
   * 
   * @param string $author
   * 
   */
  public function setPostAuthor($author)
  {
    $this->author = $author;
  }

  /**
   * set post's title
   * 
   * @param string $title
   */
  public function setPostTitle($title)
  {
    $this->title = prevent_injection($title);
  }
  
  /**
   * set post's URL SEO Friendly
   * 
   * @param string $slug
   */
  public function setPostSlug($slug)
  {
    $this->slug = make_slug($slug);    
  }
  
  /**
   * set post's content
   * 
   * @param string $content
   */
  public function setPostContent($content)
  {
    $this->content = purify_dirty_html($content);
  }
  
  /**
   * set post's summary as meta_description tag
   * 
   * @param string $meta_desc
   */
  public function setMetaDesc($meta_desc)
  {
    $this->meta_desc = prevent_injection($meta_desc);
  }
  
  /**
   * set post's keyword as meta_keyword tag
   * 
   * @param string $meta_keys
   */
  public function setMetaKeys($meta_keys)
  {
    $this->meta_key = prevent_injection($meta_keys);
  }
  
  /**
   * set post's tag
   * adding tag to the posts
   * 
   * @param string $tags
   * @return void
   * 
   */
  public function setPostTags($tags)
  {
    $this->post_tags = prevent_injection($tags);

  }

  /**
   * set post's status
   * published or save as draft
   * 
   * @param string $post_status
   */
  public function setPublish($post_status)
  {
    $this->post_status = $post_status;
  }
  
  /**
   * set comment's status
   * comment allowed(open) or not allowed(close)
   * 
   * @param string $comment_status
   */
  public function setComment($comment_status)
  {
    $this->comment_status = $comment_status;    
  }
  
  /**
   * set post's topic
   * 
   * @param integer $topics
   */
  public function setTopics($topics)
  {
    $this->topics = $topics;    
  }
  
  /**
   * Retrieve all posts
   * 
   * @param number $position
   * @param number $limit
   * @param string $orderBy
   * @param null $author
   * @return boolean|array|object 
   * 
   */
  public function grabPosts($orderBy = 'ID', $author = null)
  {
    return $this->postDao->findPosts($orderBy, $author);
  }
  
  /**
   * Retrieve single post by ID
   * 
   * @param integer $id
   * @return boolean|array|object
   */
  public function grabPost($id)
  {
    return $this->postDao->findPost($id, $this->sanitizer);     
  }
  
  /**
   * Insert new post
   * 
   * @return integer
   */
  public function addPost()
  {
    
     $category = new TopicDao();
     
     $this->validator->sanitize($this->author, 'int');
     $this->validator->sanitize($this->post_image, 'int');
     $this->validator->sanitize($this->title, 'string');
     
     if ((!empty($this->meta_desc)) || (!empty($this->meta_key)) || (!empty($this->post_tags))) {
        $this->validator->sanitize($this->meta_desc, 'string');
        $this->validator->sanitize($this->meta_key, 'string');
        $this->validator->sanitize($this->post_tags, 'string');
     }
     
     if ($this->topics == 0) {
             
      $categoryId = $category -> createTopic(['topic_title' => 'Uncategorized', 'topic_slug' => 'uncategorized']);

      $getCategory = $category -> findTopicById($categoryId, $this->sanitizer, PDO::FETCH_ASSOC);
      
      return $this->postDao->createPost([
          'media_id' => $this->post_image,
          'post_author' => $this->author,
          'post_date' => date("Y-m-d H:i:s"),
          'post_title' => $this->title,
          'post_slug'  => $this->slug,
          'post_content' => $this->content,
          'post_summary' => $this->meta_desc,
          'post_keyword' => $this->meta_key,
          'post_tags' => $this->post_tags,
          'post_status' => $this->post_status,
          'comment_status' => $this->comment_status
      ], $getCategory['ID']);
      
     } else {
      
       return $this->postDao->createPost([
            'media_id' => $this->post_image,
            'post_author' => $this->author,
            'post_date' => date("Y-m-d H:i:s"),
            'post_title' => $this->title,
            'post_slug'  => $this->slug,
            'post_content' => $this->content,
            'post_summary' => $this->meta_desc,
            'post_keyword' => $this->meta_key,
            'post_tags' => $this->post_tags,
            'post_status' => $this->post_status,
            'comment_status' => $this->comment_status
          ], $this->topics);
      
      }
  
  }
  
  public function modifyPost()
  {
    
     $this->validator->sanitize($this->postId, 'int');  
     $this->validator->sanitize($this->author, 'int');
     $this->validator->sanitize($this->post_image, 'int');
     $this->validator->sanitize($this->title, 'string');
     
     if ((!empty($this->meta_desc)) || (!empty($this->meta_key)) || (!empty($this->post_tags))) {
         $this->validator->sanitize($this->meta_desc, 'string');
         $this->validator->sanitize($this->meta_key, 'string');
         $this->validator->sanitize($this->post_tags, 'string');
     }
      
    if (empty($this->post_image)) {
          
        return $this->postDao->updatePost($this->sanitizer, [
            'post_author' => $this->author,
            'post_modified' => date("Y-m-d H:i:s"),
            'post_title' => $this->title,
            'post_slug' => $this->slug,
            'post_content' => $this->content,
            'post_summary' => $this->meta_desc,
            'post_keyword' => $this->meta_key,
            'post_tags' => $this->post_tags,
            'post_status' => $this->post_status,
            'comment_status' => $this->comment_status
        ], $this->postId, $this->topics);
         
    } else {
         
        return $this->postDao->updatePost($this->sanitizer, [
            'media_id' => $this->post_image,
            'post_author' => $this->author,
            'post_modified' => date("Y-m-d H:i:s"),
            'post_title' => $this->title,
            'post_slug' => $this->slug,
            'post_content' => $this->content,
            'post_summary' => $this->meta_desc,
            'post_keyword' => $this->meta_key,
            'post_tags' => $this->post_tags,
            'post_status' => $this->post_status,
            'comment_status' => $this->comment_status
        ], $this->postId, $this->topics);
        
    }
    
  }
  
  public function removePost()
  {
    
    $this->validator->sanitize($this->postId, 'int');
    
    if (!$data_post = $this->postDao->findPost($this->postId, $this->sanitizer)) {

       direct_page('index.php?load=posts&error=postNotFound', 404); 
       
    }
    
    $media_id = $data_post['media_id'];
    
    $medialib = new MediaDao();
    $media_data = $medialib->findMediaBlog((int)$media_id);
    $post_image = basename($media_data['media_filename']);

    if ($post_image !== '') {
        
       if (is_readable(__DIR__ . '/../../'.APP_IMAGE.$post_image)) {
           
           unlink(__DIR__ . '/../../'.APP_IMAGE.$post_image);
           unlink(__DIR__ . '/../../'.APP_IMAGE_LARGE.'large_'.$post_image);
           unlink(__DIR__ . '/../../'.APP_IMAGE_MEDIUM.'medium_'.$post_image);
           unlink(__DIR__ . '/../../'.APP_IMAGE_SMALL.'small_'.$post_image);
           
       }
       
      return  $this->postDao->deletePost($this->postId, $this->sanitizer);
       
    } else {
        
      return $this->postDao->deletePost($this->postId, $this->sanitizer);
        
    }
    
  }
  
  /**
   * Drop down post status
   * 
   * @param string $selected
   * @return string
   */
  public static function postStatusDropDown($selected = "")
  {
     return PostDao::dropDownPostStatus($selected);
  }
  
  /**
   * Drop down comment status
   * 
   * @param string $selected
   * @return string
   * 
   */
  public static function commentStatusDropDown($selected = "")
  {
     return PostDao::dropDownCommentStatus($selected);
  }
 
  /**
   * postAuthorId
   * Checking whether author cookie_id or session_id exists
   * 
   * @return string
   * 
   */
  public function postAuthorId()
  {

    if (isset(Session::getInstance()->scriptlog_session_id)) {
      
        return Session::getInstance()->scriptlog_session_id;

    }

    return false;

  }

/**
 * postAuthorLevel
 * Checking whether author cookie_level or session_level exists
 *
 * @return void
 * 
 */
 public function postAuthorLevel()
 {
 
   if (isset(Session::getInstance()->scriptlog_session_level)) {

      return Session::getInstance()->scriptlog_session_level;
    
    }
    
    return false;

 }
 
/**
   * Total posts records
   * 
   * @param array $data
   * @return integer
   * 
   */
  public function totalPosts($data = null)
  {
     return $this->postDao->totalPostRecords($data);
  }
  
}