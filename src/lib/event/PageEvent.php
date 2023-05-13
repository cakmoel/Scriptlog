<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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
  private $post_image;

  /**
   * Author
   * 
   * @var string
   * 
   */
  private $author;

  /**
   * post_date
   *
   * @var string
   * 
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
   * post's tags
   *
   * @var string
   * 
   */
  private $post_tags;

  /**
   * Page's status
   * 
   * @var string
   * 
   */
  private $page_status;

  /**
   * Stick to the top of the blog
   *
   * @var string
   * 
   */
  private $page_sticky;

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

  /**
   * PageDao
   *
   * @var object
   * 
   */
  private $pageDao;

  /**
   * Validator
   *
   * @var object
   * 
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

  /**
   * setPageAuthor
   *
   * @param string $author
   * @return string
   * 
   */
  public function setPageAuthor($author)
  {
    $this->author = $author;
  }

  /**
   * setPostDate
   *
   * @param string $date_created
   * @return string
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
   * @return string
   * 
   */
  public function setPostModified($date_modified)
  {
    $this->post_modified = $date_modified;
  }

  /**
   * SetPageImage
   *
   * @param interger $post_image
   * @return integer
   */
  public function setPageImage($post_image)
  {
    $this->post_image = $post_image;
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
   * setPostTags
   *
   * @param string $post_tags
   * 
   */
  public function setPageTags($post_tags)
  {
    $this->post_tags = $post_tags;
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

  /**
   * setSticky
   * 
   * stick to the top of the blog
   *
   * @param int $page_sticky
   * 
   */
  public function setSticky($page_sticky)
  {
    $this->page_sticky = $page_sticky;
  }

  /**
   * setComment
   *
   * @param string $comment_status
   * 
   */
  public function setComment($comment_status)
  {
    $this->comment_status = $comment_status;
  }

  /**
   * grabPages
   *
   * @param string $type
   * @return mixed
   * 
   */
  public function grabPages($type, $orderBy = 'ID', $author = null)
  {
    return $this->pageDao->findPages($type, $orderBy, $author);
  }

  /**
   * grabPage
   *
   * @param int|number $id
   * @param string $type
   * @return mixed
   * 
   */
  public function grabPage($id)
  {
    return $this->pageDao->findPageById($id, $this->sanitizer);
  }

  /**
   * addPage
   *
   * insert new page record
   * 
   * @method public addPage()
   */
  public function addPage()
  {

    $this->validator->sanitize($this->author, 'int');
    $this->validator->sanitize($this->title, 'string');
    $this->validator->sanitize($this->post_image, 'int');

    if ((!empty($this->meta_desc)) || (!empty($this->meta_key)) || (!empty($this->post_tags))) {
      $this->validator->sanitize($this->meta_desc, 'string');
      $this->validator->sanitize($this->meta_key, 'string');
      $this->validator->sanitize($this->post_tags, 'string');
    }

    if (!empty($this->post_image)) {

      return $this->pageDao->createPage([
        'media_id' => $this->post_image,
        'post_author' => $this->author,
        'post_date' => date_for_database($this->post_date),
        'post_title' => $this->title,
        'post_slug' => $this->slug,
        'post_content' => $this->content,
        'post_summary' => $this->meta_desc,
        'post_keyword' => $this->meta_key,
        'post_tags' => $this->post_tags,
        'post_status' => $this->page_status,
        'post_sticky' => $this->page_sticky,
        'post_type' => $this->post_type,
        'comment_status' => $this->comment_status
      ]);

    } else {

      return $this->pageDao->createPage([
        'post_author' => $this->author,
        'post_date' => date_for_database($this->post_date),
        'post_title' => $this->title,
        'post_slug' => $this->slug,
        'post_content' => $this->content,
        'post_summary' => $this->meta_desc,
        'post_keyword' => $this->meta_key,
        'post_tags' => $this->post_tags,
        'post_status' => $this->page_status,
        'post_sticky' => $this->page_sticky,
        'post_type' => $this->post_type,
        'comment_status' => $this->comment_status
      ]);
    }
  }

  /**
   * modifyPage
   *
   * Updating an existing page record
   * 
   */
  public function modifyPage()
  {

    $this->validator->sanitize($this->pageId, 'int');
    $this->validator->sanitize($this->author, 'int');
    $this->validator->sanitize($this->post_image, 'int');
    $this->validator->sanitize($this->title, 'string');

    if ((!empty($this->meta_desc))  || (!empty($this->meta_key)) || (!empty($this->post_tags))) {
      $this->validator->sanitize($this->meta_desc, 'string');
      $this->validator->sanitize($this->meta_key, 'string');
      $this->validator->sanitize($this->post_tags, 'string');
    }

    if (empty($this->post_image)) {

      return $this->pageDao->updatePage($this->sanitizer, [
        'post_author' => $this->author,
        'post_modified' => date_for_database($this->post_modified),
        'post_title' => $this->title,
        'post_slug' => $this->slug,
        'post_content' => $this->content,
        'post_summary' => $this->meta_desc,
        'post_keyword' => $this->meta_key,
        'post_tags' => $this->post_tags,
        'post_status' => $this->page_status,
        'post_sticky' => $this->page_sticky,
        'post_type' => $this->post_type,
        'comment_status' => $this->comment_status
      ], $this->pageId);

    } else {

      return $this->pageDao->updatePage($this->sanitizer, [
        'media_id' => $this->post_image,
        'post_author' => $this->author,
        'post_modified' => date_for_database($this->post_modified),
        'post_title' => $this->title,
        'post_slug' => $this->slug,
        'post_content' => $this->content,
        'post_summary' => $this->meta_desc,
        'post_keyword' => $this->meta_key,
        'post_tags' => $this->post_tags,
        'post_status' => $this->page_status,
        'post_sticky' => $this->page_sticky,
        'post_type' => $this->post_type,
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
    $page_image = isset($media_data['media_filename']) ? basename($media_data['media_filename']) : "";

    if ($page_image !== '') {

      if (is_readable(__DIR__ . '/../../' . APP_IMAGE . $page_image)) {

        unlink(__DIR__ . '/../../' . APP_IMAGE . $page_image);
        unlink(__DIR__ . '/../../' . APP_IMAGE_LARGE . 'large_' . $page_image);
        unlink(__DIR__ . '/../../' . APP_IMAGE_MEDIUM . 'medium_' . $page_image);
        unlink(__DIR__ . '/../../' . APP_IMAGE_SMALL . 'small_' . $page_image);
      }

      return $this->pageDao->deletePage($this->pageId, $this->sanitizer, $this->post_type);
    } else {

      return $this->pageDao->deletePage($this->pageId, $this->sanitizer, $this->post_type);
    }
  }

  /**
   * postStatusDropDown()
   *
   * select box post status
   * 
   * @param string $selected
   * @return void
   * 
   */
  public function postStatusDropDown($selected = "")
  {
    return $this->pageDao->dropDownPostStatus($selected);
  }

  /**
   * commentStatusDropDown()
   *
   * @param string $selected
   * 
   */
  public function commentStatusDropDown($selected = "")
  {
    return $this->pageDao->dropDownCommentStatus($selected);
  }

  /**
   * pageAuthorId
   * 
   * @return void|bool if true it will be return session otherwise it will be false
   * 
   */
  public function pageAuthorId()
  {

    if (isset(Session::getInstance()->scriptlog_session_id)) {

      return Session::getInstance()->scriptlog_session_id;
    }

    return false;
  }

  /**
   * pageAuthorLevel
   *
   * @return void|bool - return bool - false if it is not session
   * 
   */
  public function pageAuthorLevel()
  {

    if (isset($_COOKIE['scriptlog_auth'])) {

      Authorization::setAuthInstance(new Authentication(new UserDao, new UserTokenDao, $this->validator));

      return Authorization::authorizeLevel();
    }

    if (isset(Session::getInstance()->scriptlog_session_level)) {

      return Session::getInstance()->scriptlog_session_level;
    }

    return false;
  }

  public function totalPages($data = null)
  {
    return $this->pageDao->totalPageRecords($data);
  }
}