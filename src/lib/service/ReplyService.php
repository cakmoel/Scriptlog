<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class ReplyService
 *
 * @category Class Service
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 *
 */
class ReplyService
{
    private $reply_id;
    private $post_id;
    private $parent_id;
    private $author_name;
    private $author_ip;
    private $author_email;
    private $content;
    private $status;
    private $created_at;
    private $replyDao;
    private $validator;
    private $sanitizer;

    public function __construct(ReplyDao $replyDao, FormValidator $validator, Sanitize $sanitize)
    {
        $this->replyDao = $replyDao;
        $this->validator = $validator;
        $this->sanitizer = $sanitize;
    }

    public function setReplyId($reply_id)
    {
        $this->reply_id = $reply_id;
    }

    public function setPostId($post_id)
    {
        $this->post_id = $post_id;
    }

    public function setParentId($parent_id)
    {
        $this->parent_id = $parent_id;
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

    public function setReplyContent($content)
    {
        $this->content = prevent_injection($content);
    }

    public function setReplyStatus($status)
    {
        $this->status = $status;
    }

    public function setReplyDate($created_at)
    {
        $this->created_at = $created_at;
    }

    /**
     * Grab Replies by Parent Comment ID
     *
     * @param int $parentId
     * @param string $orderBy
     * @return array|null
     */
    public function grabReplies($parentId, $orderBy = 'ID')
    {
        return $this->replyDao->findReplies($parentId, $orderBy);
    }

    /**
     * Grab Single Reply
     *
     * @param int $id
     * @return array|null
     */
    public function grabReply($id)
    {
        return $this->replyDao->findReply($id, $this->sanitizer);
    }

    /**
     * Get Parent Comment
     *
     * @param int $parentId
     * @return array|null
     */
    public function grabParentComment($parentId)
    {
        return $this->replyDao->getParentComment($parentId, $this->sanitizer);
    }

    /**
     * Add New Reply
     *
     * @return int|bool
     */
    public function addReply()
    {
        $this->validator->sanitize($this->parent_id, 'int');
        $this->validator->sanitize($this->author_name, 'string');

        if (empty($this->author_name)) {
            throw new AppException("Author name is required");
        }

        if (empty($this->content)) {
            throw new AppException("Reply content is required");
        }

        if (empty($this->status)) {
            $this->status = 'pending';
        }

        return $this->replyDao->createReply([
          'comment_post_id' => $this->post_id,
          'comment_parent_id' => $this->parent_id,
          'comment_author_name' => $this->author_name,
          'comment_author_ip' => $this->author_ip,
          'comment_author_email' => $this->author_email,
          'comment_content' => purify_dirty_html($this->content),
          'comment_status' => $this->status
        ]);
    }

    /**
     * Modify/Update Reply
     *
     * @return bool
     */
    public function modifyReply()
    {
        $this->validator->sanitize($this->reply_id, 'int');
        $this->validator->sanitize($this->author_name, 'string');

        if (empty($this->author_name)) {
            throw new AppException("Author name is required");
        }

        if (empty($this->content)) {
            throw new AppException("Reply content is required");
        }

        return $this->replyDao->updateReply($this->sanitizer, [
          'comment_author_name' => $this->author_name,
          'comment_content' => $this->content,
          'comment_status' => $this->status
        ], $this->reply_id);
    }

    /**
     * Remove Reply
     *
     * @return bool
     */
    public function removeReply()
    {
        $this->validator->sanitize($this->reply_id, 'int');

        if (!$this->replyDao->checkReplyId($this->reply_id, $this->sanitizer)) {
            $_SESSION['error'] = "replyNotFound";
            direct_page('index.php?load=comments&error=replyNotFound', 404);
        }

        return $this->replyDao->deleteReply($this->reply_id, $this->sanitizer);
    }

    /**
     * Reply Statement Dropdown
     *
     * @param string $selected
     * @return string
     */
    public function replyStatementDropDown($selected = "")
    {
        return $this->replyDao->dropDownReplyStatement($selected);
    }

    /**
     * Check Reply Exists
     *
     * @param int $id
     * @return bool
     */
    public function checkReplyExists($id)
    {
        return $this->replyDao->checkReplyId($id, $this->sanitizer);
    }

    /**
     * Total Replies
     *
     * @param array $data
     * @param int|null $parentId
     * @return int
     */
    public function totalReplies($data = null, $parentId = null)
    {
        return $this->replyDao->totalReplyRecords($data, $parentId);
    }
}
