<?php
/**
 * class TopicProviderController extend FrontProviderController
 * 
 * @category Provider class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class TopicProviderController extends FrontProviderController
{

/**
 * topicProviderService
 *
 * @var object
 * 
 */
private $topicProviderService;

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

public function __construct(TopicProviderService $topicProviderService)
{

  $this->topicProviderService = $topicProviderService;

  if (Registry::isKeySet('uri')) {

    $this->uri = Registry::get('uri');

  }

}

public function getItems()
{

$errors = array();
$checkError = true;

$this->setupView('category');
$this->setFrontTitle($this->uri->param1." &raquo; ".app_info()['site_name']);
$this->content->set('frontTitle', $this->getFrontTitle());

$getTopicPosts = $this->getItemBySlug($this->uri->param2);

if (!$getTopicPosts) {

  $checkError = false;
  array_push($errors, "You have not any post yet in this category");

}

if (!$checkError) {

  $this->content->set('errors', $errors);
  
}

$this->content->set('postPublishedByTopic');


}

public function getItemById($id)
{

}

public function getItemBySlug($slug)
{

  $sanitize_slug = sanitize_string($slug);

  return $this->topicProviderService->showTopicBySlug($sanitize_slug);

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