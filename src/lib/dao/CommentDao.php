<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class Comment extends Dao
 * 
 * @category Dao Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class CommentDao extends Dao
{

/**
 * Constructor
 * 
 */
 public function __construct()
 {
   parent::__construct();
 }
 
/**
 * Find Comments
 * 
 * @method public findComments()
 * @param integer|string $orderBy -- default order By Id
 * @return array
 * 
 */
 public function findComments($orderBy = 'ID')
 {
   $sql = "SELECT c.ID, c.comment_post_id, c.comment_author_name,  
                  c.comment_author_ip, c.comment_content, c.comment_status, 
                  c.comment_date, p.post_title 
           FROM tbl_comments AS c 
           INNER JOIN tbl_posts AS p 
           ON c.comment_post_id = p.ID ORDER BY :orderBy DESC ";
   
   $this->setSQL($sql);

   $comments = $this->findAll([':orderBy' => $orderBy]);
   
   return (empty($comments)) ?: $comments;

 }
 
/**
 * Find Comment
 * 
 * @method public findComment()
 * @param integer|number $id
 * @param object $sanitize
 * @return array
 * 
 */
 public function findComment($id, $sanitize)
 {
   $id_sanitized = $this->filteringId($sanitize, $id, 'sql');
   
   $sql = "SELECT ID, comment_post_id, comment_author_name, 
           comment_author_ip, comment_content, comment_status, 
           comment_date FROM tbl_comments WHERE ID = ? ";
   
   $this->setSQL($sql);
   
   $commentDetails = $this->findRow([$id_sanitized]);
   
   return (empty($commentDetails)) ?: $commentDetails;
   
 }
 
/**
 * Add Comment
 * 
 * @method public addComment()
 * @param array $bind
 * 
 */
 public function addComment($bind)
 {
    
   $this->create("tbl_comments", [
        'comment_post_id' => $bind['comment_post_id'],
        'comment_author_name' => $bind['comment_author_name'],
        'comment_author_ip' => $bind['comment_author_ip'],
        'comment_content' => purify_dirty_html($bind['comment_content']),
        'comment_date' => $bind['comment_date']
   ]); 
    
 }
 
/**
 * Update Comment
 * 
 * @method public updateComment()
 * @param object $sanitize
 * @param array $bind
 * @param integer $ID
 * 
 */
 public function updateComment($sanitize, $bind, $ID)
 {
   
   $cleanId = $this->filteringId($sanitize, $ID, 'sql');
   $this->modify("tbl_comments", [
       'comment_author_name' => $bind['comment_author_name'],
       'comment_content' => purify_dirty_html($bind['comment_content']),
       'comment_status' => $bind['comment_status']
   ], " ID = ".(int)$cleanId);
   
 }
 
/**
 * DeleteComment
 * 
 * @method public deleteComment()
 * @param integer $ID
 * @param object $sanitize
 * 
 */
 public function deleteComment($id, $sanitize)
 {
   $clean_id = $this->filteringId($sanitize, $id, 'sql');
   $this->deleteRecord("tbl_comments", "ID = ".(int)$clean_id);
 }
 
/**
 * CheckCommentId
 * 
 * @method public checkCommentId()
 * @param integer $id
 * @param object $sanitize
 * @return integer|numeric
 * 
 */
 public function checkCommentId($id, $sanitize)
 {
   $sql = "SELECT ID FROM tbl_comments WHERE ID = ?";
   $id_sanitized = $this->filteringId($sanitize, $id, 'sql');
   $this->setSQL($sql);
   $stmt = $this->checkCountValue([$id_sanitized]);
   return $stmt > 0;
 }
 
/**
 * DropDownCommentStatement
 * 
 * @method public dropDownCommentStatement($selected)
 * @param string $selected
 * @return mixed
 * 
 */
 public function dropDownCommentStatement($selected = '')
 {
     $name = 'comment_status';
     
     // list position in array
     $comment_status = array('approved' => 'Approved', 'pending' => 'Pending', 'spam' => 'Spam');
     
     if ($selected != '') {
         $selected = $selected;
     }
     
     return dropdown($name, $comment_status, $selected);
     
 }

/**
 * TotalCommentRecords
 * 
 * @param array $data
 * @return integer
 * 
 */
 public function totalCommentRecords($data = null)
 {
   $sql = "SELECT ID FROM tbl_comments";

   $this->setSQL($sql);

   return $this->checkCountValue($data);

 }
 
}