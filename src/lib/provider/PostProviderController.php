<?php
/**
 * class PostProviderController extends FrontProviderController
 * 
 * @category Provider Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class PostProviderController extends FrontProviderController
{

/**
 * postProviderService
 *
 * @var object
 * 
 */
private $postProviderService;

/**
 * uri
 *
 * @var object
 * 
 */
private $uri;

/**
 * paginator
 *
 * @var object
 * 
 */
private static $paginator;

/**
 * PER_PAGE
 * 
 * constant
 * 
 */
const PER_PAGE = 10;

/**
 * QUERY_PAGE
 * 
 * constant
 * 
 */
const QUERY_PAGE = 'p';

/**
 * Constructor
 *
 * @param postProviderService $postProviderService
 * 
 */
public function __construct(PostProviderService $postProviderService)
{
  
  $this->postProviderService = $postProviderService;

  if (Registry::isKeySet('uri')) {

    $this->uri = Registry::get('uri');

  }

}

/**
 * getItems
 *
 * @return void
 * 
 */
public function getItems()
{

  $errors = array();
  $checkError = true;

  $this->setupView('blog');
  $this->setFrontTitle($this->uri->param1." &raquo; ". app_info()['site_name']);
  $this->content->set('frontTitle', $this->getFrontTitle());

  if (!is_array($this->postProviderService->showRandomStickyPosts())) {

     $checkError = false;
     array_push($errors, "You have not any post yet");

  }

  if (!$checkError) {

    $this->content->set('errors', $errors);
    
  }
  
  $this->content->set('stickyPost', $this->postProviderService->showRandomStickyPosts());
  $this->content->set('postsPublished', $this->postProviderService->showPostsPublished(self::frontPaginator()));
  $this->content->set('randomPosts', $this->postProviderService->showRandomPosts(5));
   
  return $this->content->render();
  
}

/**
 * getItemById
 *
 * @param integer|num $id
 * @return void
 * 
 */
public function getItemById($id)
{
  $errors = array();
  $checkError = true;

  if (!$detailPost = $this->postProviderService->showPostById($id)) {

      $checkError = false;
      array_push($errors, "Post not found");

  }

  $data_post = array(

    'ID' => $detailPost['ID'],
    'media_id'=> $detailPost['media_id'],
    'media_filename' => $detailPost['media_filename'],
    'media_target' => $detailPost['media_target'],
    'media_access' => $detailPost['media_access'],
    'post_title' => $detailPost['post_title'],
    'post_slug' => $detailPost['post_slug'],
    'author_id' => $detailPost['post_author'],
    'author_name' => $detailPost['user_fullname'],
    'post_content' => $detailPost['post_content'],
    'meta_description' => $detailPost['post_summary'],
    'meta_keyword' => $detailPost['post_keyword'],
    'post_tags' => $detailPost['post_tags'],
    'post_status' => $detailPost['post_status'],
    'post_sticky' => $detailPost['post_sticky'],
    'comment_status' => $detailPost['comment_status']

  );
  
  $this->setupView('single');
  $this->setFrontTitle(escape_html($detailPost['post_title']));
  $this->content->set('frontTitle', $this->getFrontTitle());

  if (!$checkError) {
    
    $this->content->set('errors', $errors);
    
  }
  
  $this->content->set('dataPost', $data_post);

  return $this->content->render();

}

public function getItemBySlug($slug)
{

$errors = array();
$checkError = true;

if (!$detailPost = $this->postProviderService->showPostBySlug($slug)) {

  $checkError = false;
  array_push($errors, "Post not found!");

}

$data_post = array(

  'ID' => $detailPost['ID'],
  'media_id'=> $detailPost['media_id'],
  'media_filename' => $detailPost['media_filename'],
  'media_target' => $detailPost['media_target'],
  'media_access' => $detailPost['media_access'],
  'post_title' => $detailPost['post_title'],
  'post_slug' => $detailPost['post_slug'],
  'author_id' => $detailPost['post_author'],
  'author_name' => $detailPost['user_fullname'],
  'post_content' => $detailPost['post_content'],
  'meta_description' => $detailPost['post_summary'],
  'meta_keyword' => $detailPost['post_keyword'],
  'post_tags' => $detailPost['post_tags'],
  'post_status' => $detailPost['post-status'],
  'post_sticky' => $detailPost['post_sticky'],
  'comment_status' => $detailPost['comment_status']

);

$this->setupView('single');
$this->setFrontTitle($detailPost['post_title']);
$this->content->set('frontTitle', $this->getFrontTitle());

if (!$checkError) {

   $this->content->set('errors', $errors);

}

$this->content->set('dataPost', $data_post);

return $this->content->render();

}

protected function setupView($fileRequested)
{
  
 $themeActived = theme_identifier();

 return $this->renderFrontView('public', $themeActived['theme_directory'], "", $fileRequested);

}

protected static function frontPaginator()
{

 self::$paginator = new Paginator(self::PER_PAGE, self::QUERY_PAGE);

 return self::$paginator;

}

}