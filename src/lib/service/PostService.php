<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class PostService
 *
 * @category  Service class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PostService
{
    /**
     * post's ID
     * @var integer
     */
    private $postId;

    /**
     * post's Image
     * @var integer
     */
    private $post_image;

    /**
     * author
     * @var string
     */
    private $author;

    /**
     * post_date
     * @var string
     */
    private $post_date;

    /**
     * post_modified
     *
     * @var string
     *
     */
    private $post_modified;

    /**
     * post's title
     * @var string
     */
    private $title;

    /**
     * post's URL SEO Friendly
     * @var string
     */
    private $slug;

    /**
     * post's content
     * @var string
     */
    private $content;

    /**
     * post's summary
     * it will be used for meta_description tag
     *
     * @var string
     */
    private $meta_desc;

    /**
     * post's status
     * published or save as draft
     *
     * @var string
     */
    private $post_status;

    /**
     * post's visibility
     * visibility as public, private or protected
     * @var string
     */
    private $post_visibility;

    /**
     * post's password
     *
     * @var string
     */
    private $post_password;

    /**
     * post_headlines
     *
     * @var integer
     *
     */
    private $post_headlines;

    /**
     * comment's status
     * is comment opened(allowed) or closed(not allowed)
     *
     * @var string
     */
    private $comment_status;

    /**
     * passphrase key
     *
     * @var string
     */
    private $passphrase;

    /**
     * post's topic
     *
     * @var integer
     */
    private $topics;

    /**
     * post's tags
     *
     * @var integer
     *
     */
    private $tags;

    /**
     * post's locale
     *
     * @var string
     */
    private $post_locale;

    /**
     * postDao
     *
     * @var object
     *
     */
    private $postDao;

    /**
     * validator
     *
     * @var object
     *
     */
    private $validator;

    /**
     * sanitizer
     *
     * @var object
     *
     */
    private $sanitizer;

    /**
     * Constructor
     *
     * @param object $postDao
     * @param object $validator
     * @param object $sanitizer
     *
     */
    public function __construct(PostDao $postDao, FormValidator $validator, Sanitize $sanitizer)
    {
        $this->postDao = $postDao;
        $this->validator = $validator;
        $this->sanitizer = $sanitizer;
    }

    /**
     * set post's ID
     *
     * @param integer $postId
     */
    public function setPostId($postId)
    {
        $this->postId = $postId;
    }

    /**
     * set post's image
     *
     * @param string $post_image
     *
     */
    public function setPostImage($post_image)
    {
        $this->post_image = $post_image;
    }

    /**
     * set post's author
     *
     * @param string $author
     *
     */
    public function setPostAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * setPostDate
     *
     * @param string $date_created
     *
     */
    public function setPostDate($date_created)
    {
        $this->post_date = $date_created;
    }

    /**
     * setPostModified
     *
     * @param string $date_modified
     *
     */
    public function setPostModified($date_modified)
    {
        $this->post_modified = $date_modified;
    }

    /**
     * set post's title
     *
     * @param string $title
     */
    public function setPostTitle($title)
    {
        $this->title = prevent_injection($title);
    }

    /**
     * set post's URL SEO Friendly
     *
     * @param string $slug
     */
    public function setPostSlug($slug)
    {
        $this->slug = make_slug($slug);
    }

    /**
     * set post's content
     *
     * @param string $content
     * @param bool $skipPurify
     */
    public function setPostContent($content, $skipPurify = false)
    {
        $this->content = $skipPurify ? $content : purify_dirty_html($content);
    }

    /**
     * set post's summary as meta_description tag
     *
     * @param string $meta_desc
     */
    public function setMetaDesc($meta_desc)
    {
        $this->meta_desc = prevent_injection($meta_desc);
    }

    /**
     * set post's status
     * published or save as draft
     *
     * @param string $post_status
     */
    public function setPublish($post_status)
    {
        $this->post_status = $post_status;
    }

    /**
     * setVisibility()
     * setup post visibility whether is public, private or protected with password
     *
     * @param string $post_visibility
     *
     */
    public function setVisibility($post_visibility)
    {
        $this->post_visibility = $post_visibility;
    }

    /**
     * setProtected()
     *
     * Post published with visibility protected will be created with it's own password
     * someone know its password only can read whole post protected
     *
     * @param string $post_password
     *
     */
    public function setProtected($post_password)
    {
        $this->post_password = $post_password;
    }

    /**
     * setHeadlines
     *
     * @param int $post_headlines
     *
     */
    public function setHeadlines($post_headlines)
    {
        $this->post_headlines = $post_headlines;
    }

    /**
     * set comment's status
     * comment allowed(open) or not allowed(close)
     *
     * @param string $comment_status
     */
    public function setComment($comment_status)
    {
        $this->comment_status = $comment_status;
    }

    /**
     * setPassPhrase
     *
     * @param string $passphrase
     *
     */
    public function setPassPhrase($passphrase)
    {
        $this->passphrase = hash('sha256', app_key() . $passphrase);
    }

    /**
     * set post's topic
     *
     * @param integer $topics
     *
     */
    public function setTopics($topics)
    {
        $this->topics = $topics;
    }

    /**
     * setPostTags
     *
     * @param string $tags
     *
     */
    public function setPostTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * setPostLocale
     *
     * @param string $post_locale
     *
     */
    public function setPostLocale($post_locale)
    {
        $this->post_locale = sanitize_locale($post_locale);
    }

    /**
     * Retrieve all posts.
     *
     * @param string $orderBy
     * @param mixed $author
     * @return array|bool|object
     *
     */
    public function grabPosts($orderBy = 'ID', $author = null)
    {
        return $this->postDao->findPosts($orderBy, $author, false);
    }

    /**
     * Retrieve a single post by ID.
     *
     * @param int $postId
     * @return array|bool|object
     */
    public function grabPost($postId)
    {
        return $this->postDao->findPost($postId, $this->sanitizer, null, false);
    }

    /**
     * Insert new post
     *
     * @return integer
     *
     */
    public function addPost()
    {

        $category = new TopicDao();

        $this->validator->sanitize($this->author, 'int');
        $this->validator->sanitize($this->post_image, 'int');
        $this->validator->sanitize($this->title, 'string');

        if ((!empty($this->meta_desc)) || (!empty($this->tags))) {
            $this->validator->sanitize($this->meta_desc, 'string');
        }

        $topic_id = $this->topics;

        if ($this->topics == 0) {
            $categoryId = $category->createTopic(['topic_title' => 'Uncategorized', 'topic_slug' => 'uncategorized']);

            $getCategory = $category->findTopicById($categoryId, $this->sanitizer, PDO::FETCH_ASSOC);

            $topic_id = isset($getCategory['ID']) ? abs((int)$getCategory['ID']) : 0;
        }

        $new_post = [
          'media_id' => $this->post_image,
          'post_author' => $this->author,
          'post_date' => $this->post_date,
          'post_title' => $this->title,
          'post_slug'  => $this->slug,
          'post_content' => $this->content,
          'post_summary' => $this->meta_desc,
          'post_status' => $this->post_status,
          'post_visibility' => $this->post_visibility,
          'post_password' => $this->post_password,
          'post_tags' => $this->tags,
          'post_headlines' => $this->post_headlines,
          'post_locale' => $this->post_locale ?? 'en',
          'comment_status' => $this->comment_status,
          'passphrase' => $this->passphrase
        ];

        return $this->postDao->createPost($new_post, $topic_id);
    }

    /**
     * modifyPost
     *
     * @return void
     *
     */
    public function modifyPost()
    {

        $this->validator->sanitize($this->postId, 'int');
        $this->validator->sanitize($this->author, 'int');
        $this->validator->sanitize($this->post_image, 'int');
        $this->validator->sanitize($this->title, 'string');

        if ((!empty($this->meta_desc)) || (!empty($this->tags))) {
            $this->validator->sanitize($this->meta_desc, 'string');
            $this->validator->sanitize($this->tags, 'string');
        }

        $postData = [
          'post_author' => $this->author,
          'post_modified' => $this->post_modified,
          'post_title' => $this->title,
          'post_slug' => $this->slug,
          'post_content' => $this->content,
          'post_summary' => $this->meta_desc,
          'post_status' => $this->post_status,
          'post_visibility' => $this->post_visibility,
          'post_password' => $this->post_password,
          'post_tags' => $this->tags,
          'post_headlines' => $this->post_headlines,
          'post_locale' => $this->post_locale ?? 'en',
          'comment_status' => $this->comment_status,
          'passphrase' => $this->passphrase
        ];

        if (!empty($this->post_image)) {
            $postData['media_id'] = $this->post_image;
        }

        $this->postDao->updatePost($this->sanitizer, $postData, $this->postId, $this->topics);
    }

    /**
     * removePost
     * removing an existing post record
     *
     */
    public function removePost()
    {

        (version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);

        $this->validator->sanitize($this->postId, 'int');

        $data_post = $this->postDao->findPost($this->postId, $this->sanitizer);
        if (!$data_post) {
            $_SESSION['error'] = "postNotFound";
            direct_page('index.php?load=posts&error=postNotFound', 404);
            return false;
        }

        $media_id = $data_post['media_id'] ?? 0;

        // Handle media operation
        if (class_exists('MediaDao')) {
            $medialib = new MediaDao();

            if (method_exists($medialib, 'findMediaBlog') && $media_id) {
                $media_data = $medialib->findMediaBlog((int)$media_id);
                $media_filename = isset($media_data['media_filename']) && preg_match('/^[a-zA-Z0-9_\-\.]+$/', $media_data['media_filename']) ? basename($media_data['media_filename']) : '';

                if (!empty($media_filename)) {
                    // define file path
                    $base_path = __DIR__ . '/../../' . APP_IMAGE;

                    $files_to_delete = [
                      $base_path . $media_filename,
                      $base_path . APP_IMAGE_LARGE . 'large_' . $media_filename,
                      $base_path . APP_IMAGE_MEDIUM . 'medium_' . $media_filename,
                      $base_path . APP_IMAGE_SMALL . 'small_' . $media_filename,
                    ];

                    foreach ($files_to_delete as $file) {
                        if (file_exists($file) && is_writable($file)) {
                            unlink($file);
                        }
                    }

                    // Delete media record
                    if (method_exists($medialib, 'deleteMedia')) {
                        $medialib->deleteMedia((int) $media_id, $this->sanitizer);
                    }
                }
            }
        }

        // Delete the post
        $this->postDao->deletePost($this->postId, $this->sanitizer);
    }

    /**
     * Process post image - orchestrates default or uploaded image handling.
     *
     * @param string $file_location
     * @param string $file_type
     * @param string $file_name
     * @param int $file_size
     * @param string $file_extension
     * @param string $new_filename
     * @param int $width
     * @param int $height
     * @param string $media_access
     * @param string $user_level
     * @param array $filtered
     * @param bool $isUpdate
     * @param mixed $oldMediaId
     * @return void
     */
    public function processPostImage($file_location, $file_type, $file_name, $file_size, $file_extension, $new_filename, $width, $height, $media_access, $user_level, array $filtered, $isUpdate = false, $oldMediaId = null)
    {
        if (empty($file_location)) {
            $mediaId = $this->processDefaultImage($filtered, $width, $height, $media_access, $user_level, $isUpdate);
            if ($mediaId !== null) {
                $this->setPostImage($mediaId);
            }
            return;
        }

        $this->processUploadedImage($file_location, $file_type, $file_name, $file_size, $file_extension, $new_filename, $width, $height, $media_access, $user_level, $filtered, $oldMediaId);
    }

    /**
     * Process default image (nophoto or existing image_id).
     *
     * @param array $filtered
     * @param int $width
     * @param int $height
     * @param string $media_access
     * @param string $user_level
     * @param bool $isUpdate
     * @return int|null
     */
    private function processDefaultImage(array $filtered, $width, $height, $media_access, $user_level, $isUpdate = false)
    {
        if (isset($_POST['image_id'])) {
            return (int)$filtered['image_id'];
        }

        if ($isUpdate) {
            return null;
        }

        clearstatcache();
        $mediaLib = new MediaDao();

        $media_metavalue = array(
          'Origin' => "nophoto.jpg",
          'File type' => "image/jpg",
          'File size' => format_size_unit(filesize(__DIR__ . '/../../' . APP_IMAGE . "nophoto.jpg")),
          'Uploaded at' => date("Y-m-d H:i:s"),
          'Dimension' => $width . 'x' . $height
        );

        $bind_media = [
          'media_filename' => "nophoto.jpg",
          'media_caption' => prevent_injection($filtered['post_title']),
          'media_type' => "image/jpg",
          'media_target' => 'blog',
          'media_user' => $user_level,
          'media_access' => $media_access,
          'media_status' => '1'
        ];

        $append_media = $mediaLib->createMedia($bind_media);
        $mediaLib->createMediaMeta([
          'media_id' => $append_media,
          'meta_key' => "nophoto.jpg",
          'meta_value' => json_encode($media_metavalue)
        ]);

        return $append_media;
    }

    /**
     * Process uploaded image - handles file upload and media record.
     *
     * @param string $file_location
     * @param string $file_type
     * @param string $file_name
     * @param int $file_size
     * @param string $file_extension
     * @param string $new_filename
     * @param int $width
     * @param int $height
     * @param string $media_access
     * @param string $user_level
     * @param array $filtered
     * @param mixed $oldMediaId
     * @return void
     */
    private function processUploadedImage($file_location, $file_type, $file_name, $file_size, $file_extension, $new_filename, $width, $height, $media_access, $user_level, array $filtered, $oldMediaId = null)
    {
        $mediaLib = new MediaDao();

        if ($oldMediaId) {
            $sanitizer = new Sanitize();
            $oldMedia = $mediaLib->findMediaById($oldMediaId, $sanitizer);
            if ($oldMedia && !empty($oldMedia['media_filename']) && $oldMedia['media_filename'] !== 'nophoto.jpg') {
                $mediaLib->deleteMedia($oldMediaId, $sanitizer);
            }
        }

        $media_metavalue = array();

        if (in_array($file_extension, ['jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp'])) {
            $media_metavalue = array(
              'Origin' => rename_file($file_name),
              'File type' => $file_type,
              'File size' => format_size_unit($file_size),
              'Uploaded at' => date("Y-m-d H:i:s"),
              'Dimension' => $width . 'x' . $height
            );
        }

        if (is_uploaded_file($file_location)) {
            upload_media($file_location, $file_type, $file_size, basename($new_filename));
        }

        $bind_media = [
          'media_filename' => $new_filename,
          'media_caption' => prevent_injection($filtered['post_title']),
          'media_type' => $file_type,
          'media_target' => 'blog',
          'media_user' => $user_level,
          'media_access' => $media_access,
          'media_status' => '1'
        ];

        $append_media = $mediaLib->createMedia($bind_media);
        $mediaLib->createMediaMeta([
          'media_id' => $append_media,
          'meta_key' => $new_filename,
          'meta_value' => json_encode($media_metavalue)
        ]);

        $this->setPostImage($append_media);
    }

    /**
     * Drop down post status
     *
     * @param string $selected
     * @return string
     *
     */
    public function postStatusDropDown($selected = "")
    {
        return $this->postDao->dropDownPostStatus($selected);
    }

    /**
     * Drop down comment status
     *
     * @param string $selected
     * @return string
     *
     */
    public function commentStatusDropDown($selected = "")
    {
        return $this->postDao->dropDownCommentStatus($selected);
    }

    /**
     * visibilityDropDown
     *
     * @param string $selected
     * @return string
     *
     */
    public function visibilityDropDown($selected = "")
    {
        return $this->postDao->dropDownVisibility($selected);
    }

    /**
     * localeDropDown
     *
     * @param string $selected
     * @return string
     *
     */
    public function localeDropDown($selected = "")
    {
        return $this->postDao->dropDownLocale($selected);
    }

    /**
     * postAuthorId
     * Checking whether author cookie_id or session_id exists
     *
     * @return string
     *
     */
    public function postAuthorId()
    {

        if (isset(Session::getInstance()->scriptlog_session_id)) {
            return Session::getInstance()->scriptlog_session_id;
        }

        return false;
    }

    /**
     * postAuthorLevel
     *
     * Checking whether author cookie_level or session_level exists.
     *
     * @return string|bool
     *
     */
    public function postAuthorLevel()
    {
        return user_privilege();
    }

    /**
     * Total posts records.
     *
     * @param array $data
     * @return int|null
     *
     */
    public function totalPosts(array $data = []): ?int
    {
        return $this->postDao->totalPostRecords($data);
    }
}
