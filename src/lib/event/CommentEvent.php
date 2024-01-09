<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * CommentEvent Class
 *
 * @category  Event Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class CommentEvent
{

 /**
  * Comment ID
  * @var integer
  */
  private $comment_id;
  
  /**
   * Post ID
   * @var integer
   */
  private $post_id;

  /**
   * Author's name
   * @var string
   */
  private $author_name;

  /**
   * Author's IP address
   * @var string
   */
  private $author_ip;

  /**
   * Author's Email address
   *
   * @var string
   */
  private $author_email;
  
  /**
   * Comment content
   * @var string
   */
  private $content;
  
  /**
   * Comment status
   * @var boolean
   */
  private $status;

/**
 * comment creation date
 *
 * @var string
 */
  private $created_at;

/**
 * An instance of CommentDao
 *
 * @var object
 * 
 */
  private $commentDao;

/**
 * An instance of formValidator
 *
 * @var object
 * 
 */
  private $validator;

/**
 * An instance of Sanitize
 *
 * @var [type]
 */
  private $sanitizer;

  public function __construct(CommentDao $commentDao, FormValidator $validator, Sanitize $sanitize)
  {
   $this->commentDao = $commentDao;
   $this->validator = $validator;
   $this->sanitizer = $sanitize;
  }
  
  public function setCommentId($comment_id)
  {
    $this->comment_id = $comment_id;
  }

  public function setPostId($post_id)
  {
    $this->post_id = $post_id;
  }
  
  public function setAuthorName($author_name)
  {
    $this->author_name = $author_name;
  }
  
  public function setAuthorIP($author_ip)
  {
    $this->author_ip = $author_ip;
  }

  public function setAuthorEmail($author_email)
  {
    $this->author_email = $author_email;
  }
  
  public function setCommentContent($content)
  {
   $this->content = prevent_injection($content);
  }
  
  public function setCommentStatus($status)
  {
   $this->status = $status;
  }

  public function setCommentDate($created_at)
  {
    $this->created_at = $created_at;
  }
  
  public function grabComments($orderBy = 'ID')
  {
    return $this->commentDao->findComments($orderBy);
  }
  
  public function grabComment($id)
  {
    return $this->commentDao->findComment($id, $this->sanitizer);
  }
  
  public function modifyComment()
  {
    $this->validator->sanitize($this->comment_id, 'int');
    $this->validator->sanitize($this->author_name, 'string');
     
    return $this->commentDao->updateComment($this->sanitizer, [
        'comment_author_name' => $this->author_name,
        'comment_content' => $this->content,
        'comment_status' => $this->status
    ], $this->comment_id);
    
  }
  
  /**
   * Remove Comment
   * @return integer
   */
  public function removeComment()
  {
     
    $this->validator->sanitize($this->comment_id, 'int');
    
    if ( ! $this->commentDao->findComment($this->comment_id, $this->sanitizer)) {

      $_SESSION['error'] = "commentNotFound";
      direct_page('index.php?load=comments&error=commentNotFound', 404);
    }
    
    return $this->commentDao->deleteComment($this->comment_id, $this->sanitizer);
    
  }
  
  /**
   * Comment Statement DropDown
   * 
   * @param string $selected
   * @return string
   * 
   */
  public function commentStatementDropDown($selected = "")
  {
     return $this->commentDao->dropDownCommentStatement($selected);
  }

/**
 * Total Comments recorded
 * 
 * @param array $data
 * @return integer
 * 
 */
  public function totalComments($data = null)
  {
    return $this->commentDao->totalCommentRecords($data);
  }

}