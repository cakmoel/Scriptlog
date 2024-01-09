<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class CommentProviderModel extends Dao
 * 
 * @category Provider Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class CommentProviderModel extends Dao
{

private $linkComments;

private $pagination;

public function __construct()
{
  parent::__construct();
}

/**
 * getCommentsByPost
 *
 * retrieves comments records by detail post requested
 * and list it before footer 
 * 
 * @param int|num $postId
 * @param Sanitize $sanitize
 * @param Paginator $perPage
 */
public function getCommentsByPost($postId, Sanitize $sanitize, Paginator $perPage)
{

$this->linkComments = $perPage;

$stmt = $this->dbc->dbQuery("SELECT ID FROM tbl_comments WHERE comment_status = 'approved' ");

$this->linkComments->set_total($stmt->rowCount());

$sql = "SELECT  comment_post_id, comment_author_name, comment_author_ip, comment_author_email, 
                comment_content, comment_status, comment_date
        FROM tbl_comments 
        WHERE comment_status = 'approved' AND comment_post_id = :post_id 
        ORDER BY ID DESC ".$this->linkComments->get_limit($sanitize);

$idsanitized = $this->filteringId($sanitize, $postId, 'sql');

$this->setSQL($sql);

$commentsApproved = $this->findAll([':post_id' => $idsanitized]);

$this->pagination = $this->linkComments->page_links($sanitize);

return (is_iterable($commentsApproved)) ?  ['commentsApproved' => $commentsApproved, 'paginationLink' => $this->pagination] : "";

}

/**
 * totalCommentsByPost
 *
 * @param int|num $postId
 */
public function totalCommentsByPost($postId, $sanitize)
{
$sql = "SELECT ID FROM tbl_comments WHERE comment_status = 'approved' AND comment_post_id = ?";
$idsanitized = $this->filteringId($sanitize, $postId, 'sql');
$this->setSQL($sql);
$stmt = $this->checkCountValue([$idsanitized]);
return $stmt > 0;
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
       'comment_author_email' => $bind['comment_author_email'],
       'comment_content' => purify_dirty_html($bind['comment_content']),
       'comment_date' => $bind['comment_date']
  ]); 
   
}

}