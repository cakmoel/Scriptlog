<?php
/**
 * Class ContentGateway
 * 
 * @package  SCRIPTLOG/LIB/CORE/ContentGateway
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Releases 1.0
 * 
 */
class ContentGateway
{
  /**
   * Pagination instance
   * 
   * @var object
   * 
   */
  private $paginator;

  /**
   * Sanitization instance
   * 
   * @var object
   * 
   */
  private $sanitizer;

  /**
   * postDao instance
   * 
   * @var object
   * 
   */
  protected $postDao;

  /**
   * pageDao instance
   * 
   * @var object
   * 
   */
  protected $pageDao;

  /**
   * topicDao instance
   * 
   * @var object
   * 
   */
  protected $topicDao;

  /**
   * constructor
   * 
   * @param object $paginator
   * @param object $sanitizer
   * 
   */
  public function __construct(Paginator $paginator, Sanitize $sanitizer)
  {
    $this->paginator = $paginator;
    $this->sanitizer = $sanitizer;
  }

  /**
   * grab post published and display it in front-end
   * 
   * @method mixed grabPost()
   * @param object $postDao
   * @param integer $args
   * 
   */
  public function grabPost(Post $postDao, $args = null)
  {
    $this->postDao = $postDao;

    if (is_null($args)) {

        return $this->postDao->showPostsPublished($this->paginator, $this->sanitizer);

    } else {

        return $this->postDao->showPostById($args, $this->sanitizer);

    }

  }

  /**
   * grab page published and display it in front-end
   * 
   * @method mixed grabPage()
   * @param  object $pageDao
   * @param  string $args
   * 
   */
  public function grabPage(Page $pageDao, $args)
  {
    $this->pageDao = $pageDao;
    return $this->pageDao->findPageBySlug($args, $this->sanitizer);
  }

  /**
   * grab topic
   * 
   * @method mixed grabTopic()
   * @param object $topicDao
   * @param string $args
   * 
   */
  public function grabTopic(Topic $topicDao, $args)
  {
    $this->topicDao = $topicDao;
    return $this->topicDao->findTopicBySlug($args, $this->sanitizer);
  }

}