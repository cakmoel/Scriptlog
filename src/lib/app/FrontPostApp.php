<?php
/**
 * Class FrontPostApp
 * 
 * @category Class FrontPostApp extends FrontHelper
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since   Since Release 1.0
 * 
 */
class FrontPostApp extends FrontHelper
{

/**
 * frontPostEvent
 *
 * @var object
 * 
 */
private $frontPostEvent;

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
 * @param FrontPostEvent $frontPostEvent
 * 
 */
public function __construct(FrontPostEvent $frontPostEvent)
{
  $this->frontPostEvent = $frontPostEvent;
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

  $this->setupView('Blog');
  $this->setFrontTitle('Post');
  $this->content->set('frontTitle', $this->getFrontTitle());

  if (!is_array($this->frontPostEvent->showHeadlinesPosts(0, 5))) {

     $checkError = false;
     array_push($errors, "You have not any post yet");

  }

  if (!$checkError) {

    $this->content->set('errors', $errors);
    
  }
  
  $this->content->set('headlinesPosts', $this->frontPostEvent->showHeadlinesPosts(0, 5));
  $this->content->set('postsPublished', $this->frontPostEvent->showPostsPublished(self::frontPaginator()));
  $this->content->set('randomPosts', $this->frontPostEvent->showRandomPosts(5));
   
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

  if (!$detailPost = $this->frontPostEvent->showPostById($id)) {

      $checkError = false;
      array_push($errors, "Post not found");

  }

  $data_post = array(

  );
  $this->setupView('single');
  $this->setFrontTitle($detailPost['post_title']);
  $this->content->set('frontTitle', $this->getFrontTitle());

  if (!$checkError) {
    
    $this->content->set('errors', $errors);
    
  }
  
  $this->content->set('');

}

public function getItemBySlug($slug)
{

}

protected function setupView($fileRequested)
{
  
 $themeActived = theme_identifier();

 return $this->frontContent('public', $themeActived['theme_directory'], "", $fileRequested);

}

protected static function frontPaginator()
{

 self::$paginator = new Paginator(self::PER_PAGE, self::QUERY_PAGE);

 return self::$paginator;

}

}