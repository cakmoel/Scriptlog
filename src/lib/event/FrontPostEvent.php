<?php
/**
 * class FrontPostEvent
 * 
 * @category Event Class
 * @author M.Noermoehammad
 * 
 */
class FrontPostEvent
{

  /**
   * PostDao
   *
   * @var object
   * 
   */
  private $frontPostDao; 
  
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
   * @param FrontPostDao $frontPostDao
   * @param FormValidator $validator
   * @param Sanitize $sanitizer
   */
  public function __construct(FrontPostDao $frontPostDao, Sanitize $sanitizer)
  {

    $this->frontPostDao = $frontPostDao;
    $this->sanitizer = $sanitizer;

  }

  /**
   * showPostFeeds
   * retrieving showPostFeeds
   * 
   * @param integer $limit
   * @return void
   * 
   */
  public function showPostFeeds($limit = 5)
  {
    return $this->frontPostDao->getPostFeeds($limit);
  }

  /**
   * showPostById
   *
   * @param integer $id
   * @return array
   * 
   */
  public function showPostById($id)
  {
    return $this->frontPostDao->getPostById($id, $this->sanitizer);
  }

  /**
   * showPostBySlug
   *
   * @param string $slug
   * @return void
   * 
   */
  public function showPostBySlug($slug)
  {
    return $this->frontPostDao->getPostBySlug($slug);
  }

  /**
   * ShowPostsPublished
   *
   * @param object $paginator
   * @return void
   * 
   */
  public function showPostsPublished($paginator)
  {
    if (is_object($paginator)) {

        return $this->frontPostDao->getPostsPublished($paginator, $this->sanitizer);

    }

  }

  /**
   * showHeadlinesPosts
   *
   * @param  $start
   * @param [type] $limit
   * @return void
   */
  public function showHeadlinesPosts($start, $limit)
  {
    return $this->frontPostDao->getHeadlinesPosts($start, $limit);
  }

  /**
   * showRelatedPosts
   *
   * @param string $post_title
   * @return string
   * 
   */
  public function showRelatedPosts($post_title)
  {
    return $this->frontPostDao->getRelatedPosts($start, $limit);
  }

  /**
   * showRandomPosts
   *
   * @param int $limit
   * @return void
   */
  public function showRandomPosts($limit)
  {
    return $this->frontPostDao->getRandomPosts($limit);
  }

  /**
   * showNextPost
   *
   * @param int $id
   * @param PDO::FETCH_ASSOC|PDO::FETCH_OBJECT $fetchMode
   * @return void
   */
  public function showNextPost($id, $fetchMode = null)
  {
    return $this->frontPostDao->getNextPost($id, $this->sanitizer, $fetchMode);
  }

  /**
   * showPrevPost
   *
   * @param int $id
   * @param PDO::FETCH_ASSOC|PDO::FETCH_OBJECT $fetchMode
   * @return void
   * 
   */
  public function showPrevPost($id, $fetchMode = null)
  {
    return $this->frontPostDao->getPrevPost($id, $this->sanitizer, $fetchMode);
  }

  /**
   * showPostsOnSidebar
   *
   * @param string $status
   * @param int $start
   * @param int $limit
   * @return 
   * 
   */
  public function showPostsOnSidebar($status, $start, $limit)
  {
    return $this->frontPostDao->getPostsOnSidebar($status, $start, $limit);
  }

}