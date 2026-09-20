<?php

namespace Scriptlog\Dao;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class CommentDao extends Dao
 *
 * @category Dao Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */

use Scriptlog\Core\Dao;

class CommentDao extends Dao
{
    private $selected;

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
     * When an email is provided, only comments authored by that email are
     * returned (used by GDPR data export so the whole table is never loaded
     * into memory and filtered in PHP).
     *
     * @method public findComments()
     * @param integer|string $orderBy -- default order By Id
     * @param string|null $email -- optional comment author email filter
     * @return array
     *
     */
    public function findComments($orderBy = 'ID', $email = null)
    {
        $allowedColumns = ['ID', 'comment_post_id', 'comment_date'];
        $sortColumn = in_array($orderBy, $allowedColumns, true) ? $orderBy : 'ID';

        $sql = "SELECT c.ID, c.comment_post_id, c.comment_parent_id, c.comment_author_name, 
                  c.comment_author_ip, c.comment_author_email, 
                  c.comment_content, c.comment_status, 
                  c.comment_date, p.post_title 
           FROM tbl_comments AS c 
           INNER JOIN tbl_posts AS p 
           ON c.comment_post_id = p.ID";

        $data = [];

        if ($email !== null) {
            $sql .= " WHERE c.comment_author_email = ?";
            $data[] = $email;
        }

        $sql .= " ORDER BY c.$sortColumn DESC ";

        $this->setSQL($sql);

        $comments = $this->findAll($data);

        return (empty($comments)) ? [] : $comments;
    }

    /**
     * Find approved comments with pagination for API
     *
     * @param integer $limit
     * @param integer $offset
     * @param string $sortBy
     * @param string $sortOrder
     * @param int|null $postId
     * @return array
     */
    public function findApprovedCommentsPaginated($limit, $offset, $sortBy = 'ID', $sortOrder = 'DESC', $postId = null)
    {
        $allowedColumns = ['ID', 'comment_date'];
        $sortColumn = in_array($sortBy, $allowedColumns) ? $sortBy : 'ID';
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT c.ID, c.comment_post_id, c.comment_parent_id,
                       c.comment_author_name, c.comment_author_email,
                       c.comment_content, c.comment_status, c.comment_date,
                       p.post_title, p.post_slug
                FROM tbl_comments c
                LEFT JOIN tbl_posts p ON c.comment_post_id = p.ID
                WHERE c.comment_status = 'approved'";

        $data = [];

        if ($postId !== null) {
            $sql .= " AND c.comment_post_id = ?";
            $data[] = (int)$postId;
        }

        $sql .= " ORDER BY c.$sortColumn $sortDir";
        $sql .= " LIMIT ? OFFSET ?";
        $data[] = (int)$limit;
        $data[] = (int)$offset;

        $this->setSQL($sql);
        $comments = $this->findAll($data);

        return empty($comments) ? [] : $comments;
    }

    /**
     * Count approved comments
     *
     * @param int|null $postId
     * @return integer
     */
    public function countApprovedComments($postId = null)
    {
        $sql = "SELECT COUNT(*) as total FROM tbl_comments WHERE comment_status = 'approved'";

        $data = [];

        if ($postId !== null) {
            $sql .= " AND comment_post_id = ?";
            $data[] = (int)$postId;
        }

        $this->setSQL($sql);
        $result = $this->findRow($data);

        return $result ? (int)$result['total'] : 0;
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

        $sql = "SELECT c.ID, c.comment_post_id, c.comment_parent_id, c.comment_author_name, 
           c.comment_author_ip, c.comment_author_email, 
           c.comment_content, c.comment_status, 
           c.comment_date, p.post_title
           FROM tbl_comments AS c 
           LEFT JOIN tbl_posts AS p
           ON c.comment_post_id = p.ID 
           WHERE c.ID = ?";

        $this->setSQL($sql);

        $commentDetails = $this->findRow([$id_sanitized]);

        return (empty($commentDetails)) ?: $commentDetails;
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
     * DeleteComment
     *
     * @method public deleteComment()
     * @param integer $ID
     * @param object $sanitize
     *
     */
    public function deleteComment($id, $sanitize)
    {
        $idsanitized = $this->filteringId($sanitize, $id, 'sql');
        $this->deleteRecord("tbl_comments", ['ID' => $idsanitized]);

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }
    }

    /**
     * Anonymize comment by replacing author info
     * Used for GDPR data deletion (Right to be Forgotten)
     *
     * @param int $commentId
     * @return bool
     */
    public function anonymizeComment($commentId)
    {
        $anonymousName = 'Anonymous User';
        $anonymousEmail = 'deleted@user.local';

        $sql = "UPDATE tbl_comments SET 
            comment_author_name = ?,
            comment_author_email = ?,
            comment_author_ip = '0.0.0.0'
            WHERE ID = ?";

        $this->setSQL($sql);
        $this->dbc->dbQuery($sql, [$anonymousName, $anonymousEmail, (int)$commentId]);

        return true;
    }

    /**
     * Anonymize all comments by user email
     *
     * @param string $email
     * @return bool
     */
    public function anonymizeCommentsByEmail($email)
    {
        $anonymousName = 'Deleted User';
        $anonymousEmail = 'deleted@user.local';

        $sql = "UPDATE tbl_comments SET 
            comment_author_name = ?,
            comment_author_email = ?,
            comment_author_ip = '0.0.0.0'
            WHERE comment_author_email = ?";

        $this->setSQL($sql);
        $this->dbc->dbQuery($sql, [$anonymousName, $anonymousEmail, $email]);

        return true;
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
        $idsanitized = $this->filteringId($sanitize, $id, 'sql');
        $this->setSQL($sql);
        $stmt = $this->checkCountValue([$idsanitized]);
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
            $this->selected = $selected;
        }

        return dropdown($name, $comment_status, $this->selected);
    }

    /**
     * TotalCommentRecords
     *
     * @param array $data
     * @return numeric|int
     *
     */
    public function totalCommentRecords($data = null)
    {
        $sql = "SELECT ID FROM tbl_comments";
        $this->setSQL($sql);
        return (empty($data)) ? $this->checkCountValue([]) : $this->checkCountValue($data);
    }

    /**
     * Count Replies for a Comment
     *
     * @param int $commentId
     * @return int
     */
    public function countReplies($commentId)
    {
        $sql = "SELECT COUNT(ID) as reply_count FROM tbl_comments WHERE comment_parent_id = ?";
        $this->setSQL($sql);
        $result = $this->findRow([(int)$commentId]);
        return (isset($result['reply_count'])) ? (int)$result['reply_count'] : 0;
    }

    /**
     * Find a single comment with post info (no sanitize required).
     *
     * @param int $commentId
     * @return array|false
     */
    public function findCommentWithPost($commentId)
    {
        $sql = "SELECT c.*, p.post_title, p.post_slug
                FROM tbl_comments c
                LEFT JOIN tbl_posts p ON c.comment_post_id = p.ID
                WHERE c.ID = ?";
        $this->setSQL($sql);
        return $this->findRow([(int)$commentId]);
    }

    /**
     * Insert a comment with the given data and return the new ID.
     *
     * @param array $data
     * @return int
     */
    public function insertCommentApi(array $data)
    {
        $this->create("tbl_comments", $data);
        return $this->lastId();
    }

    /**
     * Update specific comment fields.
     *
     * @param int $commentId
     * @param array $data
     * @return void
     */
    public function updateCommentApi($commentId, array $data)
    {
        $this->modify("tbl_comments", $data, ['ID' => (int)$commentId]);
    }

    /**
     * Delete replies for a comment, then the comment itself.
     *
     * @param int $commentId
     * @return void
     */
    public function deleteCommentWithReplies($commentId)
    {
        $this->deleteRecord("tbl_comments", ['comment_parent_id' => (int)$commentId]);
        $this->deleteRecord("tbl_comments", ['ID' => (int)$commentId]);
    }

    /**
     * Get comment_status for a post.
     *
     * @param int $postId
     * @return string|null
     */
    public function getPostCommentStatus($postId)
    {
        $sql = "SELECT comment_status FROM tbl_posts WHERE ID = ?";
        $this->setSQL($sql);
        $result = $this->findRow([(int)$postId]);
        return $result ? $result['comment_status'] : null;
    }
}
