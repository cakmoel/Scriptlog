<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class FrontContentProvider
 * 
 * @category Provider class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
final class FrontContentProvider
{
/**
 * postProviderModel;
 *
 * @var object
 * 
 */    
private static $postProviderModel;  

/**
 * topicProviderModel
 *
 * @var object
 * 
 */
private static $topicProviderModel;

/**
 * tagProviderModel
 *
 * @var object
 * 
 */
private static $tagProviderModel;

/**
 * archivesProviderModel
 *
 * @var object
 * 
 */
private static $archivesProviderModel;

/**
 * pageProviderModel
 *
 * @var object
 * 
 */
private static $pageProviderModel;

/**
 * galleryProviderModel
 *
 * @var object
 * 
 */
private static $galleryProviderModel;

/**
 * commentProviderModel
 *
 * @var object
 * 
 */
private static $commentProviderModel;

/**
 * frontRandomHeadlinesPosts
 *
 * @param PostProviderModel $postProviderModel
 * @return mixed
 * 
 */
public static function frontRandomHeadlines(PostProviderModel $postProviderModel)
{
 self::$postProviderModel = $postProviderModel;
 return self::$postProviderModel->getRandomHeadlines();
}

/**
 * frontLatestPosts
 *
 * @param int|num $position 
 * @param int|num $limit
 * @param PostProviderModel $postProviderModel
 * @param null|string $position
 * @return mixed
 * 
 */
public static function frontLatestPosts($limit, PostProviderModel $postProviderModel, $position = null)
{

 self::$postProviderModel = $postProviderModel;

 if ($position === 'sidebar') {

  return self::$postProviderModel->getPostsOnSidebar($limit);

 } else {

  return self::$postProviderModel->getLatestPosts($limit);

 }
 
}

/**
 * frontRandomPosts
 *
 * @param int|num $limit
 * @param PostProviderModel $postProviderModel
 * @return mixed
 *  
 */
public static function frontRandomPosts($start, $end, PostProviderModel $postProviderModel)
{
 self::$postProviderModel = $postProviderModel;
 return self::$postProviderModel->getRandomPosts($start, $end);
}

/**
 * frontPostById()
 *
 * @param int|number $postId
 * @param PostProviderModel $postProviderModel
 * @return mixed
 * 
 */
public static function frontPostById($postId, PostProviderModel $postProviderModel)
{  
 self::$postProviderModel = $postProviderModel;
 return self::$postProviderModel->getPostById($postId, self::frontSanitizer());
}

/**
 * frontPostsFeed()
 *
 * retrieve all posts records 
 * and display it on rss page
 * 
 * @param int|num $limit
 * @param PostProviderModel $postProviderModel
 * @return mixed
 */
public static function frontPostsFeed($limit, PostProviderModel $postProviderModel)
{
 self::$postProviderModel = $postProviderModel;
 return self::$postProviderModel->getPostFeeds($limit);
}

/**
 * frontRandomStickyPage()
 *
 * @param PageProviderModel $pageProviderModel
 * @return mixed
 * 
 */
public static function frontRandomStickyPage(PageProviderModel $pageProviderModel)
{
 
 self::$pageProviderModel = $pageProviderModel;
 return self::$pageProviderModel->getRandomStickyPages();
 
}

/**
 * frontPageBySlug()
 *
 * @param string $slug
 * @param PageProviderModel $pageProviderModel
 * @return mixed
 * 
 */
public static function frontPageBySlug($slug, PageProviderModel $pageProviderModel)
{
 self::$pageProviderModel = $pageProviderModel;
 return self::$pageProviderModel->getPageBySlug($slug, self::frontSanitizer());
}

/**
 * frontPageById
 *
 * @param int|num $id
 * @param PageProviderModel $pageProviderModel
 * @return mixed
 * 
 */
public static function frontPageById($id, PageProviderModel $pageProviderModel)
{
 self::$pageProviderModel = $pageProviderModel;
 return self::$pageProviderModel->getPageById($id, self::frontSanitizer());
}

/**
 * frontGalleries()
 * 
 * @param GalleryProviderModel $galleryProviderModel
 * @param int $start
 * @param int $limit
 * @return mixed
 */
public static function frontGalleries(GalleryProviderModel $galleryProviderModel, $start, $limit)
{
 self::$galleryProviderModel = $galleryProviderModel;
 return self::$galleryProviderModel->getGalleries($start, $limit);
}

/**
 * frontSidebarTopics
 *
 * @param TopicProviderModel $topicProviderModel
 * @return mixed
 * 
 */
public static function frontSidebarTopics(TopicProviderModel $topicProviderModel)
{
 self::$topicProviderModel = $topicProviderModel;
 return self::$topicProviderModel->getActiveTopicsOnSidebar();
}

/**
 * frontTopicBySlug()
 *
 * @param string $slug
 * @param TopicProviderModel $topicProviderModel
 * @return mixed
 * 
 */
public static function frontTopicBySlug($slug, TopicProviderModel $topicProviderModel)
{
 self::$topicProviderModel = $topicProviderModel;
 return self::$topicProviderModel->getTopicBySlug($slug, self::frontSanitizer());
}

/**
 * frontTopicById
 *
 * @param num|int $topicId
 * @param TopicProviderModel $topicProviderModel
 * @return mixed
 * 
 */
public static function frontTopicById($topicId, TopicProviderModel $topicProviderModel)
{
 self::$topicProviderModel = $topicProviderModel;
 return self::$topicProviderModel->getTopicById($topicId, self::frontSanitizer());
}

/**
 * FrontLinkTopic
 *
 * @param int|numeric $postId
 * @param TopicProviderModel $topicProviderModel
 * @return mixed
 * 
 */
public static function frontLinkTopic($postId, TopicProviderModel $topicProviderModel)
{
 self::$topicProviderModel = $topicProviderModel;
 return self::$topicProviderModel->getLinkTopic($postId, self::frontSanitizer());
}

/**
 * frontLinkTag
 *
 * @param int|numeric $postId
 * @param TagProviderModel $tagProviderModel
 * @return mixed
 */
public static function frontLinkTag($postId, TagProviderModel $tagProviderModel)
{
 self::$tagProviderModel = $tagProviderModel;
 return self::$tagProviderModel->getLinkTag($postId, self::frontSanitizer());
}

/**
 * frontPostsByArchive
 *
 * @param array $values
 * @param ArchivesProviderModel $archivesProviderModel
 * @return mixed
 * 
 */
public static function frontPostsByArchive(array $values, ArchivesProviderModel $archivesProviderModel)
{
 self::$archivesProviderModel = $archivesProviderModel;
 return self::$archivesProviderModel->getPostsByArchive(self::frontPaginator(app_reading_setting()['post_per_archive'], 'p'), self::frontSanitizer(), $values);
}

/**
 * frontPostsByTopic
 *
 * @param num|int $topicId
 * @param TopicProviderModel $topicProviderModel
 * @return mixed
 */
public static function frontPostsByTopic($topicId, TopicProviderModel $topicProviderModel)
{
 self::$topicProviderModel = $topicProviderModel;
 return self::$topicProviderModel->getPostsPublishedByTopic($topicId, self::frontSanitizer(), self::frontPaginator(app_reading_setting()['post_per_page'], 'p'));
}

/**
 * frontBlogPosts
 *
 * @param PostProviderModel $postProviderModel
 * @return mixed
 * 
 */
public static function frontBlogPosts(PostProviderModel $postProviderModel) 
{
 self::$postProviderModel = $postProviderModel;
 return self::$postProviderModel->getAllBlogPosts(self::frontSanitizer(), self::frontPaginator(app_reading_setting()['post_per_page'], 'p'));
}

/**
 * frontPostsByTag
 *
 * @param string $tag
 * @param TagProviderModel $tagProviderModel
 * @return mixed
 * 
 */
public static function frontPostsByTag($tag, TagProviderModel $tagProviderModel)
{
  self::$tagProviderModel = $tagProviderModel;
  return self::$tagProviderModel->getPostsPublishedByTag($tag, self::frontSanitizer(), self::frontPaginator(app_reading_setting()['post_per_page'], 'p'));
}

/**
 * frontSidebarArchives
 *
 * @param ArchivesProviderModel $archivesProviderModel
 * @method mixed frontSidebarArchives()
 * @uses archivesProviderModel::getArchivesOnSidebar
 * @return mixed
 */
public static function frontSidebarArchives(ArchivesProviderModel $archivesProviderModel)
{
 self::$archivesProviderModel = $archivesProviderModel;
 return self::$archivesProviderModel->getArchivesOnSidebar();
}

/**
 * frontCommentsByPost
 *
 * @param int|num $postId
 * @param CommentProviderModel $commentProviderModel
 * @return mixed
 */
public static function frontCommentsByPost($postId, CommentProviderModel $commentProviderModel)
{
 self::$commentProviderModel = $commentProviderModel;
 return self::$commentProviderModel->getCommentsByPost($postId, self::frontSanitizer(), self::frontPaginator(app_reading_setting()['comment_per_post'], 'p'));
}

/**
 * frontNewCommentByPost
 *
 * @param array $bind
 * @param CommentProviderModel $commentProviderModel
 * 
 */
public static function frontNewCommentByPost($bind, CommentProviderModel $commentProviderModel)
{
 self::$commentProviderModel = $commentProviderModel;
 return self::$commentProviderModel->addComment($bind);
}

/**
 * frontTotalCommentByPost
 *
 * @param int|num $postId
 * @param CommentProviderModel $commentProviderModel
 * 
 */
public static function frontTotalCommentByPost($postId, CommentProviderModel $commentProviderModel)
{
 self::$commentProviderModel = $commentProviderModel;
 return self::$commentProviderModel->totalCommentsByPost($postId, self::frontSanitizer());
}

/**
 * frontSanitizer
 * 
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