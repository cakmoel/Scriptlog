<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class FrontContentModel
 *
 * @category Model class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 *
 */
final class FrontContentModel extends BaseModel
{
    /**
     * postModel;
     *
     * @var object
     *
     */
    private static $postModel;

    /**
     * topicModel
     *
     * @var object
     *
     */
    private static $topicModel;

    /**
     * tagModel
     *
     * @var object
     *
     */
    private static $tagModel;

    /**
     * archivesModel
     *
     * @var object
     *
     */
    private static $archivesModel;

    /**
     * pageModel
     *
     * @var object
     *
     */
    private static $pageModel;

    /**
     * galleryModel
     *
     * @var object
     *
     */
    private static $galleryModel;

    /**
     * commentModel
     *
     * @var object
     *
     */
    private static $commentModel;

    /**
     * frontRandomHeadlinesPosts
     *
     * @param postModel $postModel
     * @return mixed
     *
     */
    public static function frontRandomHeadlines(PostModel $postModel)
    {
        self::$postModel = $postModel;
        return self::$postModel->getRandomHeadlines();
    }

    /**
     * frontLatestPosts
     *
     * @param int|num $position
     * @param int|num $limit
     * @param PostModel $postModel
     * @param null|string $position
     * @return mixed
     *
     */
    public static function frontLatestPosts($limit, PostModel $postModel, $position = null)
    {

        self::$postModel = $postModel;

        if ($position === 'sidebar') {
            return self::$postModel->getPostsOnSidebar($limit);
        } else {
            return self::$postModel->getLatestPosts($limit);
        }
    }

    /**
     * frontRandomPosts
     *
     * @param int|num $limit
     * @param PostModel $postModel
     * @return mixed
     *
     */
    public static function frontRandomPosts($start, $end, PostModel $postModel)
    {
        self::$postModel = $postModel;
        return self::$postModel->getRandomPosts($start, $end);
    }

    /**
     * frontPostById()
     *
     * @param int|number $postId
     * @param PostModel $postModel
     * @return mixed
     *
     */
    public static function frontPostById($postId, PostModel $postModel)
    {
        self::$postModel = $postModel;
        return self::$postModel->getPostById($postId, self::frontSanitizer());
    }

    /**
     * frontPostsFeed()
     *
     * retrieve all posts records
     * and display it on rss page
     *
     * @param int|num $limit
     * @param PostModel $postModel
     * @return mixed
     */
    public static function frontPostsFeed($limit, PostModel $postModel)
    {
        self::$postModel = $postModel;
        return self::$postModel->getPostFeeds($limit);
    }

    /**
     * frontRandomStickyPage()
     *
     * @param PageModel $pageModel
     * @return mixed
     *
     */
    public static function frontRandomStickyPage(PageModel $pageModel)
    {

        self::$pageModel = $pageModel;
        return self::$pageModel->getRandomStickyPages();
    }

    /**
     * frontPageBySlug()
     *
     * @param string $slug
     * @param PageModel $pageModel
     * @return mixed
     *
     */
    public static function frontPageBySlug($slug, PageModel $pageModel)
    {
        self::$pageModel = $pageModel;
        return self::$pageModel->getPageBySlug($slug, self::frontSanitizer());
    }

    /**
     * frontPageById
     *
     * @param int|num $id
     * @param PageModel $pageModel
     * @return mixed
     *
     */
    public static function frontPageById($id, PageModel $pageModel)
    {
        self::$pageModel = $pageModel;
        return self::$pageModel->getPageById($id, self::frontSanitizer());
    }

    /**
     * frontGalleries()
     *
     * @param GalleryModel $galleryModel
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public static function frontGalleries(GalleryModel $galleryModel, $start, $limit)
    {
        self::$galleryModel = $galleryModel;
        return self::$galleryModel->getGalleries($start, $limit);
    }

    /**
     * frontSidebarTopics
     *
     * @param TopicModel $topicModel
     * @return mixed
     *
     */
    public static function frontSidebarTopics(TopicModel $topicModel)
    {
        self::$topicModel = $topicModel;
        return self::$topicModel->getActiveTopicsOnSidebar();
    }

    /**
     * frontTopicBySlug()
     *
     * @param string $slug
     * @param TopicModel $topicModel
     * @return mixed
     *
     */
    public static function frontTopicBySlug($slug, TopicModel $topicModel)
    {
        self::$topicModel = $topicModel;
        return self::$topicModel->getTopicBySlug($slug, self::frontSanitizer());
    }

    /**
     * frontTopicById
     *
     * @param num|int $topicId
     * @param TopicModel $topicModel
     * @return mixed
     *
     */
    public static function frontTopicById($topicId, TopicModel $topicModel)
    {
        self::$topicModel = $topicModel;
        return self::$topicModel->getTopicById($topicId, self::frontSanitizer());
    }

    /**
     * FrontLinkTopic
     *
     * @param int|numeric $postId
     * @param TopicModel $topicModel
     * @return mixed
     *
     */
    public static function frontLinkTopic($postId, TopicModel $topicModel)
    {
        self::$topicModel = $topicModel;
        return self::$topicModel->getLinkTopic($postId, self::frontSanitizer());
    }

    /**
     * frontLinkTag
     *
     * @param int|numeric $postId
     * @param TagModel $tagModel
     * @return mixed
     */
    public static function frontLinkTag($postId, TagModel $tagModel)
    {
        self::$tagModel = $tagModel;
        return self::$tagModel->getLinkTag($postId, self::frontSanitizer());
    }

    /**
     * frontPostsByArchive
     *
     * @param array $values
     * @param ArchivesModel $archivesModel
     * @return mixed
     *
     */
    public static function frontPostsByArchive(array $values, ArchivesModel $archivesModel)
    {
        self::$archivesModel = $archivesModel;
        return self::$archivesModel->getPostsByArchive(self::frontPaginator(app_reading_setting()['post_per_archive'], 'p'), self::frontSanitizer(), $values);
    }

    /**
     * frontPostsByTopic
     *
     * @param num|int $topicId
     * @param TopicModel $topicModel
     * @return mixed
     */
    public static function frontPostsByTopic($topicId, TopicModel $topicModel)
    {
        self::$topicModel = $topicModel;

        $entries = self::$topicModel->getPostsPublishedByTopic($topicId, self::frontSanitizer(), self::frontPaginator(app_reading_setting()['post_per_page'], 'p'))['entries'];
        $pagination = self::$topicModel->getPostsPublishedByTopic($topicId, self::frontSanitizer(), self::frontPaginator(app_reading_setting()['post_per_page'], 'p'))['paginationLink'];
        return array('entries' => $entries, 'pagination' => $pagination);
    }

    /**
     * frontBlogPosts
     *
     * @param PostModel $postModel
     * @return mixed
     *
     */
    public static function frontBlogPosts(PostModel $postModel)
    {
        self::$postModel = $postModel;
        return self::$postModel->getAllBlogPosts(self::frontSanitizer(), self::frontPaginator(app_reading_setting()['post_per_page'], 'p'));
    }

    /**
     * frontPostsByTag
     *
     * @param string $tag
     * @param TagModel $tagModel
     * @return mixed
     *
     */
    public static function frontPostsByTag($tag, TagModel $tagModel)
    {
        self::$tagModel = $tagModel;
        return self::$tagModel->getPostsPublishedByTag($tag, self::frontSanitizer(), self::frontPaginator(app_reading_setting()['post_per_page'], 'p'));
    }

    /**
     * frontSidebarArchives
     *
     * @param ArchivesModel $archivesModel
     * @method mixed frontSidebarArchives()
      * @uses archivesModel::getArchivesOnSidebar
      * @return mixed
      */
    public static function frontSidebarArchives(ArchivesModel $archivesModel)
    {
        self::$archivesModel = $archivesModel;
        return self::$archivesModel->getArchivesOnSidebar();
    }

    /**
     * frontArchiveIndex
     *
     * @param ArchivesModel $archivesModel
     * @return mixed
     */
    public static function frontArchiveIndex(ArchivesModel $archivesModel)
    {
        self::$archivesModel = $archivesModel;
        return self::$archivesModel->getArchiveIndex();
    }

    /**
     * frontNewCommentByPost
    *
    * @param array $bind
    * @param CommentModel $commentModel
    *
    */
    public static function frontNewCommentByPost($bind, CommentModel $commentModel)
    {
        self::$commentModel = $commentModel;
        return self::$commentModel->addComment($bind);
    }

    /**
     * frontSanitizer
     * a
     * @return object
     */
    public static function frontSanitizer()
    {
        return (function_exists('front_sanitizer')) ? front_sanitizer() : "";
    }

    /**
     * frontPaginator
     *
     * @param int|num $perPage
     * @param string $instance
     * @return object
     *
     */
    private static function frontPaginator($perPage, $instance)
    {
        return (function_exists('front_paginator')) ? front_paginator($perPage, $instance) : "";
    }
}
