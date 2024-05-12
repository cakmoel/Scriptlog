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

  public $linkComments;

  public function __construct()
  {
    parent::__construct();
  }

  /**
   * addComment
   *
   * @param array $bind
   * 
   */
  public function addComment($bind)
  {

    $this->create("tbl_comments", [
      'comment_post_id' => $bind['comment_post_id'],
      'comment_parent_id' => $bind['comment_parent_id'],
      'comment_author_name' => $bind['comment_author_name'],
      'comment_author_ip' => $bind['comment_author_ip'],
      'comment_author_email' => $bind['comment_author_email'],
      'comment_content' => purify_dirty_html($bind['comment_content']),
      'comment_date' => $bind['comment_date']
    ]);
  }
}
