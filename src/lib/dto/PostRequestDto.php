<?php
namespace Scriptlog\Dto;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Data Transfer Object for post form submissions.
 *
 * Encapsulates all $_POST and $_FILES access so controllers
 * never touch superglobals directly. Created via fromGlobals()
 * or constructor injection for testability.
 *
 * @category DTO
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class PostRequestDto
{
    /** @var string|null */
    public $postTitle;

    /** @var string|null */
    public $postContent;

    /** @var string|null */
    public $postSummary;

    /** @var string|null */
    public $postDate;

    /** @var string|null */
    public $postModified;

    /** @var int|null */
    public $imageId;

    /** @var array */
    public $catIds = [];

    /** @var string|null */
    public $postTags;

    /** @var string|null */
    public $postStatus;

    /** @var string|null */
    public $visibility;

    /** @var string|null */
    public $postPassword;

    /** @var int|null */
    public $postHeadlines;

    /** @var string|null */
    public $commentStatus;

    /** @var string */
    public $postLocale = 'en';

    /** @var int|null */
    public $postId;

    /** @var UploadedFileDto|null */
    public $mediaFile;

    public function __construct(array $post, array $files)
    {
        $this->postTitle = isset($post['post_title']) ? $post['post_title'] : null;
        $this->postContent = isset($post['post_content']) ? $post['post_content'] : null;
        $this->postSummary = isset($post['post_summary']) ? $post['post_summary'] : null;
        $this->postDate = isset($post['post_date']) ? $post['post_date'] : null;
        $this->postModified = isset($post['post_modified']) ? $post['post_modified'] : null;
        $this->imageId = (!empty($post['image_id'])) ? (int)$post['image_id'] : null;
        $this->catIds = isset($post['catID']) ? (array)$post['catID'] : [];
        $this->postTags = isset($post['post_tags']) ? $post['post_tags'] : null;
        $this->postStatus = isset($post['post_status']) ? $post['post_status'] : null;
        $this->visibility = isset($post['visibility']) ? $post['visibility'] : null;
        $this->postPassword = isset($post['post_password']) ? $post['post_password'] : null;
        $this->postHeadlines = (!empty($post['post_headlines'])) ? (int)$post['post_headlines'] : null;
        $this->commentStatus = isset($post['comment_status']) ? $post['comment_status'] : null;
        $this->postLocale = isset($post['post_locale']) ? $post['post_locale'] : 'en';
        $this->postId = (!empty($post['post_id'])) ? (int)$post['post_id'] : null;
        $this->mediaFile = (!empty($files['media']['tmp_name']))
            ? new UploadedFileDto($files['media'])
            : null;
    }

    /**
     * Factory method from globals.
     *
     * @return self
     */
    public static function fromGlobals()
    {
        return new self($_POST, $_FILES);
    }

    /**
     * Check whether the post form was submitted.
     *
     * @return bool
     */
    public function isSubmitted()
    {
        return isset($_POST['postFormSubmit']);
    }

    /**
     * Check whether the post is password-protected.
     *
     * @return bool
     */
    public function isProtected()
    {
        return ($this->visibility === 'protected');
    }

    /**
     * Check whether this is a new post (no post_id).
     *
     * @return bool
     */
    public function isNewPost()
    {
        return ($this->postId === null);
    }
}
