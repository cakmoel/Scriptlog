<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * TopicService Class
 *
 * @category  Service Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class TopicService
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

  /**
   * TopicDao
   *
   * @var object
   * 
   */
  private $topicDao;

  /**
   * validator
   *
   * @var object
   */
  private $validator;

  /**
   * sanitizer
   *
   * @var object 
   */
  private $sanitizer;
  
  public function __construct(TopicDao $topicDao, FormValidator $validator, Sanitize $sanitizer)
  {
    $this->topicDao = $topicDao;
    $this->validator = $validator;
    $this->sanitizer = $sanitizer;
  }
  
  /**
   * setTopicId
   *
   * @param int $topic_id
   * 
   */
  public function setTopicId($topic_id)
  {
    $this->topic_id = $topic_id;
  }
  
  /**
   * setTopicTitle
   *
   * @param string $topic_title
   *
   */
  public function setTopicTitle($topic_title)
  {
    $this->topic_title = prevent_injection($topic_title);
  }
  
  /**
   * setTopicSlug
   *
   * @param string $topic_slug
   * 
   */
  public function setTopicSlug($topic_slug)
  {
    $this->topic_slug = $topic_slug;
  }
  
  /**
   * setTopicStatus
   *
   * @param string $topic_status
   * 
   */
  public function setTopicStatus($topic_status)
  {
    $this->topic_status = $topic_status;
  }
  
  /**
   * grabTopics
   *
   * @param string $orderBy
   * 
   */
  public function grabTopics($orderBy = 'ID')
  {
    return $this->topicDao->findTopics($orderBy);
  }
  
  /**
   * grabTopic
   *
   * @param int|numeric $id
   * 
   */
  public function grabTopic($id)
  {
    return $this->topicDao->findTopicById($id, $this->sanitizer);
  }
  
  /**
   * addTopic
   *
   */
  public function addTopic()
  {
    
    $this->validator->sanitize($this->topic_title, 'string');

    return $this->topicDao->createTopic([
        'topic_title' => $this->topic_title, 
        'topic_slug' => $this->topic_slug]);
        
  }
  
  /**
   * modifyTopic
   *
   */
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
  
  /**
   * removeTopic
   * 
   */
  public function removeTopic()
  {
    
    $this->validator->sanitize($this->topic_id, 'int');
    
    if (!$this->topicDao->findTopicById($this->topic_id, $this->sanitizer)) {

      $_SESSION['error'] = "topicNotFound";  
      direct_page('index.php?load=topics&error=topicNotFound', 404);
      
    }
    
    return $this->topicDao->deleteTopic($this->topic_id, $this->sanitizer);
    
  }

  /**
   * totalTopics
   *
   * @param array $data
   * @return integer|numeric|null
   */
  public function totalTopics(array $data = []): ?int
  {
    return $this->topicDao->totalTopicRecords($data);
  }
  
}