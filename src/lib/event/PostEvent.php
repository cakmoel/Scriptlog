<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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
   * post_date
   * @var string
   */
  private $post_date;

  /**
   * post_modified
   *
   * @var string
   * 
   */
  private $post_modified;

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
   * post_headlines
   *
   * @var integer
   * 
   */
  private $post_headlines;

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
   * post's tags
   * 
   * @var integer
   *  
   */
  private $tags;

  /**
   * postDao
   *
   * @var object
   * 
   */
  private $postDao;

  /**
   * validator
   *
   * @var object
   * 
   */
  private $validator;

  /**
   * sanitizer
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
   * setPostDate
   *
   * @param string $date_created
   * 
   */
  public function setPostDate($date_created)
  {
    $this->post_date = $date_created;
  }

  /**
   * setPostModified
   *
   * @param string $date_modified
   * 
   */
  public function setPostModified($date_modified)
  {
    $this->post_modified = $date_modified;
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
   * setHeadlines
   *
   * @param int $post_headlines
   * 
   */
  public function setHeadlines($post_headlines)
  {
    $this->post_headlines = $post_headlines;
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
   * 
   */
  public function setTopics($topics)
  {
    $this->topics = $topics;
  }

  /**
   * setPostTags
   *
   * @param string $tags
   * 
   */
  public function setPostTags($tags)
  {
    $this->tags = $tags;
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
  public function grabPost($postId)
  {
    return $this->postDao->findPost($postId, $this->sanitizer);
  }

  /**
   * Insert new post
   * 
   * @return integer
   * 
   */
  public function addPost()
  {

    $category = new TopicDao();

    $this->validator->sanitize($this->author, 'int');
    $this->validator->sanitize($this->post_image, 'int');
    $this->validator->sanitize($this->title, 'string');

    if ((!empty($this->meta_desc)) || (!empty($this->meta_key)) || (!empty($this->tags))) {
      $this->validator->sanitize($this->meta_desc, 'string');
      $this->validator->sanitize($this->meta_key, 'string');
    }

    if ($this->topics == 0) {

      $categoryId = $category->createTopic(['topic_title' => 'Uncategorized', 'topic_slug' => 'uncategorized']);

      $getCategory = $category->findTopicById($categoryId, $this->sanitizer, PDO::FETCH_ASSOC);

      $new_post = ['media_id' => $this->post_image,
      'post_author' => $this->author,
      'post_date' => date_for_database($this->post_date),
      'post_title' => $this->title,
      'post_slug'  => $this->slug,
      'post_content' => $this->content,
      'post_summary' => $this->meta_desc,
      'post_keyword' => $this->meta_key,
      'post_status' => $this->post_status,
      'post_tags' => $this->tags,
      'post_headlines' => $this->post_headlines,
      'comment_status' => $this->comment_status];
      
      $topic_id = isset($getCategory['ID']) ? abs((int)$getCategory['ID']) : 0;

    } else {

      $new_post = [ 'media_id' => $this->post_image,
      'post_author' => $this->author,
      'post_date' => date_for_database($this->post_date),
      'post_title' => $this->title,
      'post_slug'  => $this->slug,
      'post_content' => $this->content,
      'post_summary' => $this->meta_desc,
      'post_keyword' => $this->meta_key,
      'post_status' => $this->post_status,
      'post_tags' => $this->tags,
      'post_headlines' => $this->post_headlines,
      'comment_status' => $this->comment_status];

      $topic_id = $this->topics;

    }

    return $this->postDao->createPost($new_post, $topic_id);

  }

  /**
   * modifyPost
   *
   * @return integer
   * 
   */
  public function modifyPost()
  {

    $this->validator->sanitize($this->postId, 'int');
    $this->validator->sanitize($this->author, 'int');
    $this->validator->sanitize($this->post_image, 'int');
    $this->validator->sanitize($this->title, 'string');

    $post_updated = array();

    if ( ( !empty($this->meta_desc) ) || ( !empty($this->meta_key) ) || ( !empty($this->post_tags) ) ) {
      $this->validator->sanitize($this->meta_desc, 'string');
      $this->validator->sanitize($this->meta_key, 'string');
      $this->validator->sanitize($this->tags, 'string');
    }

    if ( !empty( $this->post_image ) ) {

      $post_updated = [
        
        'media_id' => $this->post_image,
        'post_modified' => date_for_database($this->post_modified),
        'post_title' => $this->title,
        'post_slug' => $this->slug,
        'post_content' => $this->content,
        'post_summary' => $this->meta_desc,
        'post_keyword' => $this->meta_key,
        'post_status' => $this->post_status,
        'post_tags' => $this->tags,
        'post_headlines' => $this->post_headlines,
        'comment_status' => $this->comment_status

      ];

    } else {

      $post_updated = [

        'post_modified' => date_for_database($this->post_modified),
        'post_title' => $this->title,
        'post_slug' => $this->slug,
        'post_content' => $this->content,
        'post_summary' => $this->meta_desc,
        'post_keyword' => $this->meta_key,
        'post_status' => $this->post_status,
        'post_tags' => $this->tags,
        'post_headlines' => $this->post_headlines,
        'comment_status' => $this->comment_status

      ];
      
    }

    return $this->postDao->updatePost($this->sanitizer, $post_updated, $this->postId, $this->topics);

  }

  /**
   * removePost
   *
   * removing an existing post record
   * 
   */
  public function removePost()
  {

    $media_data = [];

    $this->validator->sanitize($this->postId, 'int');

    if (!$data_post = $this->postDao->findPost($this->postId, $this->sanitizer)) {

      direct_page('index.php?load=posts&error=postNotFound', 404);

    }

    $media_id = $data_post['media_id'];

    $medialib = new MediaDao();
    $media_data = $medialib->findMediaBlog((int)$media_id);
    $post_image = isset($media_data['media_filename'])  ? basename($media_data['media_filename']) : "";

    if (isset($post_image) && $post_image !== '') {

      if (is_readable(__DIR__ . '/../../' . APP_IMAGE . $post_image)) {

        unlink(__DIR__ . '/../../' . APP_IMAGE . $post_image);
        unlink(__DIR__ . '/../../' . APP_IMAGE_LARGE . 'large_' . $post_image);
        unlink(__DIR__ . '/../../' . APP_IMAGE_MEDIUM . 'medium_' . $post_image);
        unlink(__DIR__ . '/../../' . APP_IMAGE_SMALL . 'small_' . $post_image);
        
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
   * 
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
   * 
   */
  public function commentStatusDropDown($selected = "")
  {
    return $this->postDao->dropDownCommentStatus($selected);
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
   * 
   * Checking whether author cookie_level or session_level exists
   *
   * @return void|bool return bool if return false elsewhere return void
   * 
   */
  public function postAuthorLevel()
  {
    return user_privilege();
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
