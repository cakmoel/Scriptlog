<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * ReplyEvent Class
 * 
 * @category Event Class
 * @author  M.Noermoehammad 
 * @license MIT
 * @version 1.0.0
 * @since   Since Release 1.0.0
 * 
 */
class ReplyEvent
{
/**
 * reply_id
 *
 * @var integer
 * 
 */
  private $reply_id;

/**
 * comment_id
 *
 * @var integer
 */
  private $comment_id;

/**
 * user_id
 *
 * @var integer
 */
  private $user_id;

/**
 * reply_content
 *
 * @var string
 * 
 */
  private $reply_content;

/**
 * reply_status
 *
 * @var bool
 * 
 */
  private $reply_status;

/**
 * replyDao
 *
 * @var object
 * 
 */
  private $replyDao;

/**
 * validator
 *
 * @var object
 * 
 */
  private $validator;

/**
 * sanitize
 *
 * @var object
 * 
 */
  private $sanitize;

  public function __construct(ReplyDao $replyDao, FormValidator $validator, Sanitize $sanitize)  {

    $this->replyDao = $replyDao;
    $this->validator = $validator;
    $this->sanitize = $sanitize;

  }

  public function setReplyId($replyId)
  {
    $this->reply_id = $replyId;
  }

  public function setCommentId($commentId)
  {
    $this->comment_id = $commentId;
  }

  public function setUserId($userId)
  {
    $this->user_d = $userId;
  }

  public function setReplyContent($replyContent)
  {
    $this->reply_content = $replyContent;
  }

  public function setReplyStatus($replyStatus)
  {
    $this->reply_status = $replyStatus;
  }

  public function grabReplies($orderBy = 'ID') 
  {
    return $this->replyDao->findReplies($orderBy);
  }

  public function grabReply($id)
  {
    return $this->replyDao->findReply($id, $this->sanitize);
  }

  public function addReply()
  {
    $this->validator->sanitize($this->comment_id, 'int');
    $this->validator->sanitize($this->user_id, 'int');
    $this->validator->sanitize($this->reply_content, 'string');

    return $this->replyDao->createReply([
      'comment_id' => $this->comment_id,
      'user_id' => $this->user_id,
      'reply_content' => $this->reply_content,
      'date_publish' => date("Y-m-d H:i:s")
    ]);

  }

  public function modifyReply()
  {
    $this->validator->sanitize($this->reply_id, 'int');
    $this->validator->sanitize($this->comment_id, 'int');
    $this->validator->sanitize($this->user_id, 'int');
    $this->validator->sanitize($this->reply_content, 'string');

    return $this->replyDao->updateReply($this->sanitize, [
      'comment_id' => $this->comment_id,
      'user_id' => $this->user_id,
      'reply_content' => $this->reply_content,
      'reply_status' => $this->reply_status
    ], $this->reply_id);

  }

  public function removeReply()
  {
    $this->validator->sanitize($this->reply_id, 'int');

    if (!$data_reply = $this->replyDao->findReply($this->reply_id, $this->sanitize)) {
       direct_page('index.php?load=reply&error=replyNotFound', 404);
    }

    return $this->replyDao->deleteReply($this->reply_id, $this->sanitize);

  }

  public function totalReplies($data = null)
  {
    return $this->replyDao->totalRelyRecords($data);
  }

}