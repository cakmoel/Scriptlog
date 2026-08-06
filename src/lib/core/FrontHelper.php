<?php

namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * class FrontHelper
 *
 * FrontHelper class will be useful for theme functionality
 * to retrieves particular content needed on theme layout
 * and theme meta.
 *
 * @deprecated 1.0 Use Scriptlog\Service\FrontService instead. This facade is
 *             kept for backward compatibility only; every data-access method
 *             delegates to the FrontService instance registered by Bootstrap
 *             through FrontHelper::setFrontService(). When no service is
 *             registered, the methods return null / an empty array rather than
 *             falling back to raw SQL (the legacy inline queries were removed
 *             because they drifted from FrontService, used string interpolation,
 *             and one path was mysqli-only and broke on PDO).
 *
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 *
 */

use Scriptlog\Service\FrontService;

class FrontHelper
{
    /**
     * FrontService instance for delegated data access
     *
     * @var FrontService|null
     */
    private static ?FrontService $frontService = null;

    /**
     * Set the FrontService instance
     *
     * @param FrontService|null $service
     */
    public static function setFrontService(?FrontService $service): void
    {
        self::$frontService = $service;
    }

    /**
     * grabSimpleFrontPost
     *
     * @deprecated Use Scriptlog\Service\FrontService::getSimplePost()
     * @param int $id
     * @return array|null
     *
     */
    public static function grabSimpleFrontPost($id)
    {
        return self::$frontService ? self::$frontService->getSimplePost($id) : null;
    }

    /**
     * grabSimpleFrontTopic
     *
     * @deprecated Use Scriptlog\Service\FrontService::getSimpleTopic()
     * @param int $id
     * @return array|null
     *
     */
    public static function grabSimpleFrontTopic($id)
    {
        return self::$frontService ? self::$frontService->getSimpleTopic($id) : null;
    }

    /**
     * grabSimpleFrontArchive
     *
     * @deprecated Use Scriptlog\Service\FrontService::getSimpleArchive()
     * @return array
     *
     */
    public static function grabSimpleFrontArchive()
    {
        return self::$frontService ? self::$frontService->getSimpleArchive() : [];
    }

    /**
     * grabSimpleFrontPage
     *
     * @deprecated Use Scriptlog\Service\FrontService::getSimplePage()
     * @param int $id
     * @return array|null
     *
     */
    public static function grabSimpleFrontPage($id)
    {
        return self::$frontService ? self::$frontService->getSimplePage($id) : null;
    }

    /**
     * grabFrontTag
     *
     * implementing a simple MySQL full-text searching
     *
     * @deprecated Use Scriptlog\Service\FrontService::searchTag()
     * @param string $tag
     * @return array
     *
     */
    public static function simpleSearchingTag($tag)
    {
        if (empty($tag)) {
            return [];
        }

        return self::$frontService ? self::$frontService->searchTag($tag) : [];
    }

    /**
     * grabTagLists()
     *
     * @deprecated Use Scriptlog\Service\FrontService::getTagLists()
     * @return array
     *
     */
    public static function grabTagLists()
    {
        return self::$frontService ? self::$frontService->getTagLists() : [];
    }

    /**
     * grabPreparedFrontPostById
     *
     * @deprecated Use Scriptlog\Service\FrontService::getPublishedPost()
     * @param int $id
     * @return array|null
     *
     */
    public static function grabPreparedFrontPostById($id)
    {
        return self::$frontService ? self::$frontService->getPublishedPost((int)$id) : null;
    }

    /**
     * frontPageBySlug
     *
     * @deprecated Use Scriptlog\Service\FrontService::getPublishedPage()
     * @param string $slug
     * @return array|null
     *
     */
    public static function grabPreparedFrontPageBySlug($slug)
    {
        return self::$frontService ? self::$frontService->getPublishedPage($slug) : null;
    }

    /**
     * grabPreparedFrontTopicBySlug
     *
     * @deprecated Use Scriptlog\Service\FrontService::getPublishedTopic()
     * @param string $slug
     * @return array|null
     *
     */
    public static function grabPreparedFrontTopicBySlug($slug)
    {
        return self::$frontService ? self::$frontService->getPublishedTopic($slug) : null;
    }

    /**
     * grabPreparedFrontTopicByID
     *
     * @deprecated Use Scriptlog\Service\FrontService::getPublishedTopicById()
     * @param int $id
     * @return array|null
     *
     */
    public static function grabPreparedFrontTopicByID($id)
    {
        return self::$frontService ? self::$frontService->getPublishedTopicById((int)$id) : null;
    }

    /**
     * grabPreparedFrontArchive
     *
     * @deprecated Use Scriptlog\Service\FrontService::getArchivePosts()
     * @param array $values
     * @return array|null
     *
     */
    public static function grabPreparedFrontArchive($values)
    {
        return self::$frontService ? self::$frontService->getArchivePosts($values) : null;
    }

    /**
     * frontGalleries
     *
     * @deprecated Use Scriptlog\Service\FrontService::getGalleries()
     * @param int $start
     * @param int $limit
     * @return array|null
     *
     */
    public static function grabPreparedFrontGalleries($start, $limit)
    {
        return self::$frontService ? self::$frontService->getGalleries($start, $limit) : null;
    }
}
