<?php

namespace Scriptlog\Service;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * TopicService Class
 *
 * @category  Service Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

use Scriptlog\Core\FormValidator;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\TopicDao;

class TopicService
{
    /**
     * Topic's ID
     *
     * @var integer
     */
    private $topic_id;

    /**
     * Topic's title
     *
     * @var string
     */
    private $topic_title;

    /**
     * Topic's URL-Friendly
     *
     * @var string
     */
    private $topic_slug;

    /**
     * Topic's status
     *
     * @var string
     */
    private $topic_status;

    /**
     * Topic's locale
     *
     * @var string
     */
    private $topic_locale;

    /**
     * TopicDao
     *
     * @var object
     *
     */
    private $topicDao;

    /**
     * validator
     *
     * @var object
     */
    private $validator;

    /**
     * sanitizer
     *
     * @var object
     */
    private $sanitizer;

    public function __construct(TopicDao $topicDao, FormValidator $validator, Sanitize $sanitizer)
    {
        $this->topicDao = $topicDao;
        $this->validator = $validator;
        $this->sanitizer = $sanitizer;
    }

    /**
     * setTopicId
     *
     * @param int $topic_id
     *
     */
    public function setTopicId($topic_id)
    {
        $this->topic_id = $topic_id;
    }

    /**
     * setTopicTitle
     *
     * @param string $topic_title
     *
     */
    public function setTopicTitle($topic_title)
    {
        $this->topic_title = prevent_injection($topic_title);
    }

    /**
     * setTopicSlug
     *
     * @param string $topic_slug
     *
     */
    public function setTopicSlug($topic_slug)
    {
        $this->topic_slug = $topic_slug;
    }

    /**
     * setTopicStatus
     *
     * @param string $topic_status
     *
     */
    public function setTopicStatus($topic_status)
    {
        $this->topic_status = $topic_status;
    }

    /**
     * setTopicLocale
     *
     * @param string $topic_locale
     *
     */
    public function setTopicLocale($topic_locale)
    {
        $this->topic_locale = sanitize_locale($topic_locale);
    }

    /**
     * grabTopics
     *
     * @param string $orderBy
     *
     */
    public function grabTopics($orderBy = 'ID')
    {
        return $this->topicDao->findTopics($orderBy);
    }

    /**
     * grabTopic
     *
     * @param int|numeric $id
     *
     */
    public function grabTopic($id)
    {
        return $this->topicDao->findTopicById($id, $this->sanitizer);
    }

    /**
     * Get paginated active topics for API
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $sortBy
     * @param string $sortOrder
     * @return array
     */
    public function getActiveTopicsApi($page = 1, $perPage = 10, $sortBy = 'ID', $sortOrder = 'DESC')
    {
        $offset = ($page - 1) * $perPage;
        return $this->topicDao->findActiveTopicsPaginated($perPage, $offset, $sortBy, $sortOrder);
    }

    /**
     * Count active topics for API
     *
     * @return integer
     */
    public function countActiveTopicsApi()
    {
        return $this->topicDao->countActiveTopics();
    }

    /**
     * Get a single topic with post count for API
     *
     * @param integer $topicId
     * @return array|false
     */
    public function getTopicApi($topicId)
    {
        return $this->topicDao->findTopicWithPostCount($topicId);
    }

    /**
     * Get paginated posts by topic for API
     *
     * @param integer $topicId
     * @param integer $page
     * @param integer $perPage
     * @param string $sortBy
     * @param string $sortOrder
     * @return array
     */
    public function getPostsByTopicApi($topicId, $page = 1, $perPage = 10, $sortBy = 'ID', $sortOrder = 'DESC')
    {
        $offset = ($page - 1) * $perPage;
        return $this->topicDao->findPostsByTopicPaginated($topicId, $perPage, $offset, $sortBy, $sortOrder);
    }

    /**
     * Count published posts by topic for API
     *
     * @param integer $topicId
     * @return integer
     */
    public function countPostsByTopicApi($topicId)
    {
        return $this->topicDao->countPostsByTopic($topicId);
    }

    /**
     * addTopic
     *
     */
    public function addTopic()
    {

        $this->validator->sanitize($this->topic_title, 'string');

        return $this->topicDao->createTopic([
            'topic_title' => $this->topic_title,
            'topic_slug' => $this->topic_slug,
            'topic_locale' => $this->topic_locale ?? 'en']);
    }

    /**
     * modifyTopic
     *
     */
    public function modifyTopic()
    {
        $this->validator->sanitize($this->topic_id, 'int');
        $this->validator->sanitize($this->topic_title, 'string');

        return $this->topicDao->updateTopic($this->sanitizer, [
             'topic_title' => $this->topic_title,
             'topic_slug' => $this->topic_slug,
             'topic_status' => $this->topic_status,
             'topic_locale' => $this->topic_locale ?? 'en'
            ], $this->topic_id);
    }

    /**
     * removeTopic
     *
     */
    public function removeTopic()
    {

        $this->validator->sanitize($this->topic_id, 'int');

        if (!$this->topicDao->findTopicById($this->topic_id, $this->sanitizer)) {
            $_SESSION['error'] = "topicNotFound";
            direct_page('index.php?load=topics&error=topicNotFound', 404);
        }

        return $this->topicDao->deleteTopic($this->topic_id, $this->sanitizer);
    }

    /**
     * totalTopics
     *
     * @param array $data
     * @return integer|numeric|null
     */
    public function totalTopics(array $data = []): ?int
    {
        return $this->topicDao->totalTopicRecords($data);
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
        return $this->topicDao->dropDownLocale($selected);
    }

    /**
     * Check if a topic slug already exists.
     *
     * @param string $slug
     * @return bool
     */
    public function checkTopicSlugExists($slug)
    {
        return $this->topicDao->findTopicBySlug($slug) !== false;
    }

    /**
     * Create a topic from raw data for the API.
     *
     * @param array $data
     * @return int
     */
    public function createTopicApi(array $data)
    {
        return $this->topicDao->insertTopicApi($data);
    }

    /**
     * Update a topic with raw data for the API.
     *
     * @param int $topicId
     * @param array $data
     * @return void
     */
    public function updateTopicApi($topicId, array $data)
    {
        $this->topicDao->updateTopicApi($topicId, $data);
    }

    /**
     * Delete a topic and its relationships for the API.
     *
     * @param int $topicId
     * @return void
     */
    public function removeTopicApi($topicId)
    {
        $this->topicDao->deleteTopicCascade($topicId);
    }
}
