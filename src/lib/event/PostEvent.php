<?php 
/**
 * PostEvent Class
 *
 * @package   SCRIPTLOG/LIB/EVENT/PostEvent
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
   * @var string
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
 
  /**
   * Post DAO
   * 
   * @var object
   * 
   */
  private $postDao;

  /**
   * Validator
   * 
   * @var object
   */
  private $validator;

  /**
   * Sanitizer
   * 
   * @var object
   * 
   */
  private $sanitizer;

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
    $this->content = prevent_injection($content);
  }
  
  /**
   * set post's summary as meta_description tag
   * 
   * @param string $meta_desc
   */
  public function setMetaDesc($meta_desc)
  {
    $this->meta_desc = $meta_desc;
  }
  
  /**
   * set post's keyword as meta_keyword tag
   * 
   * @param string $meta_keys
   */
  public function setMetaKeys($meta_keys)
  {
    $this->meta_key = $meta_keys;
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
    
     $category = new Topic();
     
     $this->validator->sanitize($this->author, 'int');
     $this->validator->sanitize($this->title, 'string');
     
     if ((!empty($this->meta_desc)) || (!empty($this->meta_key))) {
      $this->validator->sanitize($this->meta_desc, 'string');
      $this->validator->sanitize($this->meta_key, 'string');
     }
     
     if (empty($this->post_image)) {
         
         if ($this->topics == 0) {
             
             $categoryId = $category -> createTopic(['topic_title' => 'Uncategorized', 'topic_slug' => 'uncategorized']);
             $getCategory = $category -> findTopicById($categoryId, $this->sanitizer, PDO::FETCH_ASSOC);
             
             return $this->postDao->createPost([
                 'post_author' => $this->author,
                 'post_date' => date("Y-m-d H:i:s"),
                 'post_title' => $this->title,
                 'post_slug'  => $this->slug,
                 'post_content' => $this->content,
                 'post_summary' => $this->meta_desc,
                 'post_keyword' => $this->meta_key,
                 'post_status' => $this->post_status,
                 'comment_status' => $this->comment_status
             ], $getCategory['ID']);
             
         } else {
             
             return $this->postDao->createPost([
                 'post_author' => $this->author,
                 'post_date' => date("Y-m-d H:i:s"),
                 'post_title' => $this->title,
                 'post_slug'  => $this->slug,
                 'post_content' => $this->content,
                 'post_summary' => $this->meta_desc,
                 'post_keyword' => $this->meta_key,
                 'post_status' => $this->post_status,
                 'comment_status' => $this->comment_status
             ], $this->topics);
             
         }
         
     } else {
              
         if ($this->topics == 0) {
             
             $categoryId = $category -> createTopic(['topic_title' => 'Uncategorized', 'topic_slug' => 'uncategorized']);
             $getCategory = $category -> findTopicById($categoryId, $this->sanitizer, PDO::FETCH_ASSOC);
             
             return $this->postDao->createPost([
                 'post_image' => $this->post_image,
                 'post_author' => $this->author,
                 'post_date' => date("Y-m-d H:i:s"),
                 'post_title' => $this->title,
                 'post_slug'  => $this->slug,
                 'post_content' => $this->content,
                 'post_summary' => $this->meta_desc,
                 'post_keyword' => $this->meta_key,
                 'post_status' => $this->post_status,
                 'comment_status' => $this->comment_status
             ], $getCategory['ID']);
             
         } else {
             
             return $this->postDao->createPost([
                 'post_image' => $this->post_image,
                 'post_author' => $this->author,
                 'post_date' => date("Y-m-d H:i:s"),
                 'post_title' => $this->title,
                 'post_slug'  => $this->slug,
                 'post_content' => $this->content,
                 'post_summary' => $this->meta_desc,
                 'post_keyword' => $this->meta_key,
                 'post_status' => $this->post_status,
                 'comment_status' => $this->comment_status
             ], $this->topics);
             
         }
         
     }
     
  }
  
  public function modifyPost()
  {
     
    $category = new Topic();
      
    $this->validator->sanitize($this->postId, 'int');
    $this->validator->sanitize($this->author, 'int');
    $this->validator->sanitize($this->title, 'string');
    $this->validator->sanitize($this->meta_desc, 'string');
    $this->validator->sanitize($this->meta_key, 'string');
      
    if (empty($this->post_image)) {
          
        return $this->postDao->updatePost($this->sanitizer, [
            'post_author' => $this->author,
            'date_modified' => date("Y-m-d H:i:s"),
            'post_title' => $this->title,
            'post_slug' => $this->slug,
            'post_content' => $this->content,
            'post_summary' => $this->meta_desc,
            'post_keyword' => $this->meta_key,
            'post_status' => $this->post_status,
            'comment_status' => $this->comment_status
        ], $this->postId, $this->topics);
         
    } else {
         
        return $this->postDao->updatePost($this->sanitizer, [
            'post_image' => $this->post_image,
            'post_author' => $this->author,
            'date_modified' => date("Y-m-d H:i:s"),
            'post_title' => $this->title,
            'post_slug' => $this->slug,
            'post_content' => $this->content,
            'post_summary' => $this->meta_desc,
            'post_keyword' => $this->meta_key,
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
    
    $post_image = $data_post['post_image'];
    if ($post_image !== '') {
        
       if (is_readable(__DIR__ . '/../public/files/pictures/'.$post_image)) {
           
           unlink(__DIR__ . '/../public/files/pictures'.$post_image);
           unlink(__DIR__ . '/../public/files/pictures/thumbs/thumbs_'.$post_image);
           
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
  public function postStatusDropDown($selected = "")
  {
     return $this->postDao->dropDownPostStatus($selected);
  }
  
  /**
   * Drop down comment status
   * 
   * @param string $selected
   * @return string
   */
  public function commentStatusDropDown($selected = "")
  {
     return $this->postDao->dropDownCommentStatus($selected);
  }
 
  /**
   * Checking whether author session exists
   * 
   * @return string
   * 
   */
  public function isPostAuthor()
  {

    if (isset($_SESSION['user_id'])) {
      
        return $_SESSION['user_id'];

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