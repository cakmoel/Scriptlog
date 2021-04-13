<?php 
/**
 * TopicEvent Class
 *
 * @category  Event Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class TopicEvent
{
  /**
   * Topic's ID
   * 
   * @var integer
   */
  private $topic_id;
  
  /**
   * Topic's title
   * 
   * @var string
   */
  private $topic_title;
  
  /**
   * Topic's URL-Friendly
   * 
   * @var string
   */
  private $topic_slug;
  
  /**
   * Topic's status
   * 
   * @var string
   */
  private $topic_status;

  private $topicDao;

  private $validator;

  private $sanitizer;
  
  public function __construct(TopicDao $topicDao, FormValidator $validator, Sanitize $sanitizer)
  {
    $this->topicDao = $topicDao;
    $this->validator = $validator;
    $this->sanitizer = $sanitizer;
  }
  
  public function setTopicId($topic_id)
  {
    $this->topic_id = $topic_id;
  }
  
  public function setTopicTitle($topic_title)
  {
    $this->topic_title = prevent_injection($topic_title);
  }
  
  public function setTopicSlug($topic_slug)
  {
    $this->topic_slug = $topic_slug;
  }
  
  public function setTopicStatus($topic_status)
  {
    $this->topic_status = $topic_status;
  }
  
  public function grabTopics($orderBy = 'ID')
  {
    return $this->topicDao->findTopics($orderBy);
  }
  
  public function grabTopic($id)
  {
    return $this->topicDao->findTopicById($id, $this->sanitizer);
  }
  
  public function addTopic()
  {
    
    $this->validator->sanitize($this->topic_title, 'string');

    return $this->topicDao->createTopic([
        'topic_title' => $this->topic_title, 
        'topic_slug' => $this->topic_slug]);
        
  }
  
  public function modifyTopic()
  {
    $this->validator->sanitize($this->topic_id, 'int');
    $this->validator->sanitize($this->topic_title, 'string');
    
    return $this->topicDao->updateTopic($this->sanitizer, [
         'topic_title' => $this->topic_title, 
         'topic_slug' => $this->topic_slug,
         'topic_status' => $this->topic_status
        ], $this->topic_id);
  }
  
  public function removeTopic()
  {
    
    $this->validator->sanitize($this->topic_id, 'int');
    
    if (!$this->topicDao->findTopicById($this->topic_id, $this->sanitizer)) {
        direct_page('index.php?load=topics&error=topicNotFound', 404);
    }
    
    return $this->topicDao->deleteTopic($this->topic_id, $this->sanitizer);
    
  }
  
  public function totalTopics($data = null)
  {
    return $this->topicDao->totalTopicRecords($data);
  }
  
}