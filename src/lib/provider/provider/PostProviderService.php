<?php
/**
 * class PostProviderService
 * 
 * @category Provider class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class PostProviderService
{

  /**
   * postProviderModel
   *
   * @var object
   * 
   */
  private $postProviderModel; 
  
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
   * @param PostProviderModel $postProviderModel
   * @param FormValidator $validator
   * @param Sanitize $sanitizer
   */
  public function __construct(PostProviderModel $postProviderModel, Sanitize $sanitizer)
  {

    $this->postProviderModel = $postProviderModel;
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
    return $this->postProviderModel->getPostFeeds($limit);
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
    return $this->postProviderModel->getPostById($id, $this->sanitizer);
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
    return $this->postProviderModel->getPostBySlug($slug, $this->sanitizer);
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

      return $this->postProviderModel->getPostsPublished($paginator, $this->sanitizer);

    }

  }

  /**
   * showRandomStickyPosts
   *
   * @return void
   * 
   */
  public function showRandomStickyPosts()
  {
    return $this->postProviderModel->getRandomStickyPosts();
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
    return $this->postProviderModel->getRelatedPosts($post_title);
  }

  /**
   * showRandomPosts
   *
   * @param int $limit
   * @return void
   */
  public function showRandomPosts($limit)
  {
    return $this->postProviderModel->getRandomPosts($limit);
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
    return $this->postProviderModel->getNextPost($id, $this->sanitizer, $fetchMode);
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
    return $this->postProviderModel->getPrevPost($id, $this->sanitizer, $fetchMode);
  }

  /**
   * showPostsOnSidebar
   *
   * @param string $status
   * @param int $start
   * @param int $limit
   * @return array
   * 
   */
  public function showPostsOnSidebar($status, $start, $limit)
  {
    return $this->postProviderModel->getPostsOnSidebar($status, $start, $limit);
  }

}