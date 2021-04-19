<?php
/**
 * class TopicProviderService
 * 
 * @category Provider class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class TopicProviderService
{

/**
 * topicProviderModel
 *
 * @var object
 * 
 */
private $topicProviderModel;

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
 * @param TopicProviderModel $topicProviderModel
 * @param Sanitize $sanitizer
 * 
 */
public function __construct(TopicProviderModel $topicProviderModel, Sanitize $sanitizer)
{
  $this->topicProviderModel = $topicProviderModel;
  $this->sanitizer = $sanitizer;
}

/**
 * showActiveTopicsOnSidebar
 *
 * @return mixed
 * 
 */
public function showActiveTopicsOnSidebar()
{
  return $this->topicProviderModel->getActiveTopicsOnSidebar();
}

/**
 * ShowTopicBySlug
 *
 * @param string $slug
 * @param PDO::FETCH_ASSOC|PDO::FETCH_OBJECT $fetchMode
 * @return array
 * 
 */
public function showTopicBySlug($slug, $fetchMode = null)
{
  return $this->topicProviderModel->getTopicBySlug($slug, $this->sanitizer, $fetchMode);
}

/**
 * showLinkTopic
 *
 * display ahref html attribute for link topic
 * 
 * @param int $postId
 * @return string
 * 
 */
public function showLinkTopic($postId)
{
 return $this->topicProviderModel->createLinkTopic($postId, $this->sanitizer);
}

/**
 * showAllPublishedPostsByTopic
 *
 * @param int $topicId
 * @param object $paginator
 * @return array
 * 
 */
public function showAllPublishedPostsByTopic($topicId, $paginator)
{

  if (is_object($paginator)) {

    return $this->topicProviderModel->getAllPublishedPostsByTopic($topicId, $this->sanitizer, $paginator);

  }

}
}