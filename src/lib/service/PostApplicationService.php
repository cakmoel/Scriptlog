<?php

namespace Scriptlog\Service;
defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\Sanitize;

/**
 * Application service orchestrating post creation and update workflows.
 *
 * Contains the business logic extracted from PostController:
 * filter definitions, image processing, protected-content encryption,
 * PostService field assembly. The controller delegates here after
 * validation and security checks.
 *
 * @category Service
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class PostApplicationService
{

    /**
     * PostService instance.
     * @var PostService
     */
    private $postService;

    /**
     * Constructor.
     *
     * @param PostService $postService
     */
    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * Create a new post.
     *
     * Handles filter retrieval, distillation, image processing,
     * PostService field population and the final addPost() call.
     *
     * @param string $file_location
     * @param string $file_type
     * @param string $file_name
     * @param int    $file_size
     * @param string $file_extension
     * @param string $new_filename
     * @param string $user_level
     * @param array|null $filtered Pre-distilled data; computed from $_POST when null
     * @return void
     */
    public function createPost(
        $file_location,
        $file_type,
        $file_name,
        $file_size,
        $file_extension,
        $new_filename,
        $user_level,
        ?array $filtered = null
    ) {
        $filters = $this->getPostFilters();

        if ($filtered === null) {
            $filtered = distill_post_request($filters);
        }

        list($width, $height) = ($file_location)
            ? getimagesize($file_location)
            : getimagesize(__DIR__ . '/../../' . APP_IMAGE . 'nophoto.jpg');

        $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] === 'publish'))
            ? 'public'
            : 'private';

        $this->postService->processPostImage(
            $file_location, $file_type, $file_name, $file_size,
            $file_extension, $new_filename, $width, $height,
            $media_access, $user_level, $filtered, false, null
        );

        $this->setPostServiceData($filters, $filtered);
        $this->postService->addPost();
    }

    /**
     * Update an existing post.
     *
     * Handles filter retrieval, distillation, image processing,
     * protected-content re-encryption, PostService field population
     * and the final modifyPost() call.
     *
     * @param int    $id
     * @param string $file_location
     * @param string $file_type
     * @param string $file_name
     * @param int    $file_size
     * @param string $file_extension
     * @param string $new_filename
     * @param string $user_level
     * @param int|null $oldMediaId
     * @param array|null $filtered Pre-distilled data; computed from $_POST when null
     * @return void
     */
    public function updatePost(
        $id,
        $file_location,
        $file_type,
        $file_name,
        $file_size,
        $file_extension,
        $new_filename,
        $user_level,
        $oldMediaId = null,
        ?array $filtered = null
    ) {
        $filters = $this->getPostUpdateFilters();

        if ($filtered === null) {
            $filtered = distill_post_request($filters);
        }

        $this->postService->setPostId((int)$filtered['post_id']);
        $this->postService->setPostAuthor($this->postService->postAuthorId());
        $this->postService->setPostTitle($filtered['post_title']);
        $this->postService->setPostSlug($filtered['post_title']);
        $this->postService->setPublish($filtered['post_status']);

        if (isset($_POST['catID']) && $_POST['catID'] == 0) {
            $this->postService->setTopics(0);
        } else {
            $this->postService->setTopics($filtered['catID']);
        }

        list($width, $height) = (!empty($file_location))
            ? getimagesize($file_location)
            : getimagesize(__DIR__ . '/../../' . APP_IMAGE . 'nophoto.jpg');

        $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] == 'publish'))
            ? 'public'
            : 'private';

        $this->postService->processPostImage(
            $file_location, $file_type, $file_name, $file_size,
            $file_extension, $new_filename, $width, $height,
            $media_access, $user_level, $filtered, true, $oldMediaId
        );

        $this->postService->setComment($filtered['comment_status']);
        $this->postService->setMetaDesc($filtered['post_summary']);
        $this->postService->setPostTags($filtered['post_tags']);
        $this->postService->setPostLocale($filtered['post_locale']);

        if (empty($_POST['post_modified'])) {
            $this->postService->setPostModified(date_for_database());
        } else {
            $this->postService->setPostModified(date_for_database($filtered['post_modified']));
        }

        $this->setProtectedPostContent($id, $filters, $filtered);
        $this->setPostHeadlines($filters, $filtered);

        $this->postService->modifyPost();
    }

    /**
     * Return filter definitions for new-post form.
     *
     * @return array
     */
    private function getPostFilters()
    {
        return [
            'post_title' => isset($_POST['post_title']) ? Sanitize::strictSanitizer($_POST['post_title']) : "",
            'post_content' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'post_date' => isset($_POST['post_date']) ? Sanitize::mildSanitizer($_POST['post_date']) : "",
            'image_id' => isset($_POST['image_id']) ? FILTER_SANITIZE_NUMBER_INT : "",
            'catID' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY],
            'post_summary' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'post_tags' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'post_status' => isset($_POST['post_status']) ? Sanitize::mildSanitizer($_POST['post_status']) : "",
            'visibility' => isset($_POST['visibility']) ? Sanitize::mildSanitizer($_POST['visibility']) : "",
            'post_password' => isset($_POST['post_password']) ? FILTER_SANITIZE_FULL_SPECIAL_CHARS : "",
            'post_headlines' => FILTER_SANITIZE_NUMBER_INT,
            'comment_status' => isset($_POST['comment_status']) ? Sanitize::mildSanitizer($_POST['comment_status']) : "",
            'post_locale' => isset($_POST['post_locale']) ? Sanitize::mildSanitizer($_POST['post_locale']) : "en"
        ];
    }

    /**
     * Return filter definitions for edit-post form.
     *
     * @return array
     */
    private function getPostUpdateFilters()
    {
        return [
            'post_id' => FILTER_SANITIZE_NUMBER_INT,
            'post_title' => isset($_POST['post_title']) ? Sanitize::strictSanitizer($_POST['post_title']) : "",
            'post_content' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'post_modified' => isset($_POST['post_modified']) ? Sanitize::mildSanitizer($_POST['post_modified']) : "",
            'image_id' => isset($_POST['image_id']) ? FILTER_SANITIZE_NUMBER_INT : "",
            'catID' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY],
            'post_summary' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'post_status' => isset($_POST['post_status']) ? Sanitize::mildSanitizer($_POST['post_status']) : "",
            'visibility' => isset($_POST['visibility']) ? Sanitize::mildSanitizer($_POST['visibility']) : "",
            'post_password' => isset($_POST['post_password']) ? FILTER_SANITIZE_FULL_SPECIAL_CHARS : "",
            'post_tags' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'post_headlines' => FILTER_SANITIZE_NUMBER_INT,
            'comment_status' => isset($_POST['comment_status']) ? Sanitize::mildSanitizer($_POST['comment_status']) : "",
            'post_locale' => isset($_POST['post_locale']) ? Sanitize::mildSanitizer($_POST['post_locale']) : "en"
        ];
    }

    /**
     * Populate PostService fields for a new post.
     *
     * @param array      $filters
     * @param array|null $filtered
     * @return void
     */
    private function setPostServiceData($filters, ?array $filtered = null)
    {
        if ($filtered === null) {
            $filtered = distill_post_request($filters);
        }

        if (isset($_POST['catID']) && $_POST['catID'] == 0) {
            $this->postService->setTopics(0);
        } else {
            $this->postService->setTopics($filtered['catID']);
        }

        $this->postService->setPostAuthor($this->postService->postAuthorId());

        if (empty($_POST['post_date'])) {
            $this->postService->setPostDate(date_for_database());
        } else {
            $this->postService->setPostDate(date_for_database($filtered['post_date']));
        }

        $this->postService->setPostTitle($filtered['post_title']);
        $this->postService->setPostSlug($filtered['post_title']);

        if (isset($_POST['visibility']) && $_POST['visibility'] == 'protected') {
            if (!empty($_POST['post_password'])) {
                $protected = protect_post($filtered['post_content'], $filtered['visibility'], $filtered['post_password']);
                $this->postService->setPostContent($protected['post_content']);
                $this->postService->setProtected($protected['post_password']);
                $this->postService->setPassPhrase($filtered['post_password']);
                $_SESSION['post_protected'] = $filtered['post_password'];
            }
        } else {
            $this->postService->setPostContent($filtered['post_content']);
        }

        $this->postService->setPublish($filtered['post_status']);
        $this->postService->setVisibility($filtered['visibility']);

        if (empty($_POST['post_headlines'])) {
            $this->postService->setHeadlines(0);
        } else {
            $this->postService->setHeadlines($filtered['post_headlines']);
        }

        $this->postService->setComment($filtered['comment_status']);
        $this->postService->setMetaDesc($filtered['post_summary']);
        $this->postService->setPostLocale($filtered['post_locale']);

        if (isset($_POST['post_tags'])) {
            $this->postService->setPostTags($filtered['post_tags']);
        }
    }

    /**
     * Handle protected-post content re-encryption for updates.
     *
     * Three branches:
     * 1. visibility=protected + new password → encrypt with new passphrase
     * 2. visibility=protected + no password → re-encrypt with existing passphrase
     * 3. visibility ≠ protected → store plain content
     *
     * @param int        $id
     * @param array      $filters
     * @param array|null $filtered
     * @return void
     */
    private function setProtectedPostContent($id, $filters, ?array $filtered = null)
    {
        if ($filtered === null) {
            $filtered = distill_post_request($filters);
        }

        if (isset($_POST['visibility']) && $_POST['visibility'] == 'protected') {
            if (!empty($_POST['post_password'])) {
                $protected = protect_post($filtered['post_content'], $filtered['visibility'], $filtered['post_password']);
                $this->postService->setProtected($protected['post_password']);
                $this->postService->setPostContent($protected['post_content'], true);
                $this->postService->setPassPhrase($filtered['post_password']);
                $_SESSION['post_protected'] = $filtered['post_password'];
            } else {
                $existing_post = $this->postService->grabPost($id);
                if ($existing_post && !empty($existing_post['passphrase'])) {
                    $reencrypted = encrypt($filtered['post_content'], $existing_post['passphrase']);
                    $this->postService->setPostContent($reencrypted, true);
                } else {
                    $this->postService->setPostContent($filtered['post_content']);
                }
            }
            $this->postService->setVisibility($filtered['visibility']);
        } else {
            $this->postService->setVisibility($filtered['visibility']);
            $this->postService->setPostContent($filtered['post_content']);
        }
    }

    /**
     * Set post headline flag (featured / not featured).
     *
     * @param array      $filters
     * @param array|null $filtered
     * @return void
     */
    private function setPostHeadlines($filters, ?array $filtered = null)
    {
        if ($filtered === null) {
            $filtered = distill_post_request($filters);
        }

        if (empty($_POST['post_headlines'])) {
            $this->postService->setHeadlines(0);
        } else {
            $this->postService->setHeadlines($filtered['post_headlines']);
        }
    }
}
