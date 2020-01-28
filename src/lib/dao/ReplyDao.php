<?php 
/**
 * Reply class extends Dao
 *
 * 
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ReplyDao extends Dao
{
    
  public function __construct()
  {
    parent::__construct();
  }
  
  public function findReplies($orderBy = 'ID')
  {
    $sql = "SELECT ID, comment_id, user_id, 
            reply_content, reply_status, reply_date 
            FROM tbl_comment_reply ORDER BY :orderBy DESC ";
    
    $this->setSQL($sql);
    $replies = $this->findAll([':orderBy' => $orderBy]);
    
    return (empty($replies)) ?: $replies;
    
  }
  
  public function findReply($id, $sanitize)
  {
    $idsanitized = $this->filteringId($sanitize, $id, 'sql');
    $sql = "SELECT ID, comment_id, user_id, reply_content, reply_status, reply_date 
            FROM tbl_comment_reply WHERE ID = ?";
    
    $this->setSQL($sql);
    
    $replyDetail = $this->findRow([$idsanitized], PDO::FETCH_ASSOC);
    
    return (empty($replyDetail)) ?: $replyDetail;
    
  }
  
  public function createReply($bind)
  {
    $this->create("tbl_comment_reply", [
        'comment_id' => $bind['comment_id'],
        'user_id' => $bind['user_id'],
        'reply_content' => $bind['reply_content'],
        'reply_date' => $bind['reply_date']
    ]);
    
  }
  
  public function updateReply($sanitize, $bind, $id)
  {
     $cleanId = $this->filteringId($sanitize, $id, 'sql');
     $this->modify("tbl_comment_reply", [
         'comment_id' => $bind['comment_id'],
         'user_id' => $bind['user_id'],
         'reply_content' => $bind['reply_content'],
         'reply_status' => $bind['reply_status']
     ], "ID = {$cleanId}");
     
  }
  
  public function deleteReply($id, $sanitize)
  { 
    $clean_id = $this->filteringId($sanitize, $id, 'sql');
    $this->deleteRecord("tbl_comment_reply", "ID = ".(int)$clean_id);
  }
  
  public function checkReplyId($id, $sanitize)
  {
    $sql = "SELECT ID FROM tbl_comment_reply WHERE ID = ?";
    $id_sanitized = $this->filteringId($sanitize, $id, 'sql');
    $this->setSQL($sql);
    $stmt = $this -> checkCountValue([$id_sanitized]);
    return $stmt > 0;
  }

  public function dropDownReplyStatus($selected = '')
  {
    $name = 'reply_status';

    $reply_status = array('active', 'disable');

    if($selected != '') {
      $selected = $selected;
    }

    return dropdown($name, $reply_status, $selected);

  }

  public function totalReplyRecords($data = null)
  {
    $sql = "SELECT ID FROM tbl_comment_reply";
    $this->setSQL($sql);
    return $this->checkCountValue($data);
  }

}