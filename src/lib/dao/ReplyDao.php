<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class ReplyDao extends Dao
 *
 * @category Dao class
 * @author M.Noermoehammad
 * @license MIT
 *
 */
class ReplyDao extends Dao
{
    private $selected;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create Reply
     *
     * @param array $bind
     * @return int|bool
     */
    public function createReply($bind)
    {
        $sql = "INSERT INTO tbl_comments 
          (comment_post_id, comment_parent_id, comment_author_name, comment_author_ip, 
           comment_author_email, comment_content, comment_status, comment_date) 
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $this->setSQL($sql);

        $this->dbc->dbQuery(
            $sql,
            [
            $bind['comment_post_id'],
            $bind['comment_parent_id'],
            $bind['comment_author_name'],
            $bind['comment_author_ip'],
            $bind['comment_author_email'],
            $bind['comment_content'],
            $bind['comment_status']
            ]
        );

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }

        return $this->dbc->dbLastId();
    }

    /**
     * Find Replies by Comment ID (parent)
     *
     * @param int $commentId
     * @param string $orderBy
     * @return array|null
     */
    public function findReplies($commentId, $orderBy = 'ID')
    {
        $sql = "SELECT c.ID, c.comment_post_id, c.comment_parent_id, c.comment_author_name, 
                 c.comment_author_ip, c.comment_author_email, 
                 c.comment_content, c.comment_status, c.comment_date,
                 p.post_title
          FROM tbl_comments AS c 
          INNER JOIN tbl_posts AS p 
          ON c.comment_post_id = p.ID 
          WHERE c.comment_parent_id = ?
          ORDER BY c.ID {$orderBy}";

        $this->setSQL($sql);
        $replies = $this->findAll([(int)$commentId]);
        return (empty($replies)) ? null : $replies;
    }

    /**
     * Find Reply by ID
     *
     * @param int $id
     * @param object $sanitize
     * @return array|null
     */
    public function findReply($id, $sanitize)
    {
        $id_sanitized = $this->filteringId($sanitize, $id, 'sql');

        $sql = "SELECT c.ID, c.comment_post_id, c.comment_parent_id, c.comment_author_name, 
                 c.comment_author_ip, c.comment_author_email, 
                 c.comment_content, c.comment_status, c.comment_date,
                 p.post_title,
                 pc.comment_content AS parent_comment_content,
                 pc.comment_author_name AS parent_comment_author
          FROM tbl_comments AS c 
          LEFT JOIN tbl_posts AS p ON c.comment_post_id = p.ID
          LEFT JOIN tbl_comments AS pc ON c.comment_parent_id = pc.ID
          WHERE c.ID = ?";

        $this->setSQL($sql);
        $reply = $this->findRow([$id_sanitized]);
        return (empty($reply)) ? null : $reply;
    }

    /**
     * Find Replies for a Post (all replies in thread)
     *
     * @param int $postId
     * @param string $orderBy
     * @return array|null
     */
    public function findRepliesByPost($postId, $orderBy = 'ID')
    {
        $sql = "SELECT c.ID, c.comment_post_id, c.comment_parent_id, c.comment_author_name, 
                 c.comment_author_ip, c.comment_author_email, 
                 c.comment_content, c.comment_status, c.comment_date
          FROM tbl_comments AS c 
          WHERE c.comment_post_id = ? AND c.comment_parent_id > 0
          ORDER BY c.ID {$orderBy}";

        $this->setSQL($sql);
        $replies = $this->findAll([(int)$postId]);
        return (empty($replies)) ? null : $replies;
    }

    /**
     * Update Reply
     *
     * @param object $sanitize
     * @param array $bind
     * @param int $ID
     */
    public function updateReply($sanitize, $bind, $ID)
    {
        $idsanitized = $this->filteringId($sanitize, $ID, 'sql');

        $this->modify("tbl_comments", [
            'comment_author_name' => $bind['comment_author_name'],
            'comment_content' => purify_dirty_html($bind['comment_content']),
            'comment_status' => $bind['comment_status']
        ], ['ID' => $idsanitized]);

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }
    }

    /**
     * Delete Reply
     *
     * @param int $id
     * @param object $sanitize
     */
    public function deleteReply($id, $sanitize)
    {
        $idsanitized = $this->filteringId($sanitize, $id, 'sql');
        $this->deleteRecord("tbl_comments", ['ID' => $idsanitized]);

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }
    }

    /**
     * Check Reply ID exists
     *
     * @param int $id
     * @param object $sanitize
     * @return bool
     */
    public function checkReplyId($id, $sanitize)
    {
        $sql = "SELECT ID FROM tbl_comments WHERE ID = ? AND comment_parent_id > 0";
        $idsanitized = $this->filteringId($sanitize, $id, 'sql');
        $this->setSQL($sql);
        $stmt = $this->checkCountValue([$idsanitized]);
        return $stmt > 0;
    }

    /**
     * Get Parent Comment for Reply
     *
     * @param int $parentId
     * @param object $sanitize
     * @return array|null
     */
    public function getParentComment($parentId, $sanitize)
    {
        $id_sanitized = $this->filteringId($sanitize, $parentId, 'sql');

        $sql = "SELECT c.ID, c.comment_post_id, c.comment_author_name, 
                 c.comment_content, c.comment_date, p.post_title
          FROM tbl_comments AS c 
          LEFT JOIN tbl_posts AS p ON c.comment_post_id = p.ID
          WHERE c.ID = ? AND c.comment_parent_id = 0";

        $this->setSQL($sql);
        $parent = $this->findRow([$id_sanitized]);
        return (empty($parent)) ? null : $parent;
    }

    /**
     * Total Reply Records
     *
     * @param array $data
     * @param int|null $parentId
     * @return int
     */
    public function totalReplyRecords($data = null, $parentId = null)
    {
        if ($parentId !== null) {
            $sql = "SELECT ID FROM tbl_comments WHERE comment_parent_id = ?";
            $this->setSQL($sql);
            return $this->checkCountValue([(int)$parentId]);
        }

        $sql = "SELECT ID FROM tbl_comments WHERE comment_parent_id > 0";
        $this->setSQL($sql);
        return (empty($data)) ? $this->checkCountValue([]) : $this->checkCountValue($data);
    }

    /**
     * Dropdown for Reply Status
     *
     * @param string $selected
     * @return string
     */
    public function dropDownReplyStatement($selected = '')
    {
        $name = 'reply_status';
        $reply_status = array(
          'draft' => 'Draft',
          'pending' => 'Pending',
          'approved' => 'Approved',
          'spam' => 'Spam'
        );

        if ($selected !== '') {
            $this->selected = $selected;
        }

        return dropdown($name, $reply_status, $this->selected);
    }
}
