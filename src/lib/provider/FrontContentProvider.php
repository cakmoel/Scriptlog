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

 if ( $position == 'sidebar') {

  return self::$postProviderModel->getPostsOnSidebar('publish', 0, 3);

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
 * 
 */
public static function frontTopicBySlug($slug, TopicProviderModel $topicProviderModel)
{
 self::$topicProviderModel = $topicProviderModel;
 return self::$topicProviderModel->getTopicBySlug($slug, self::frontSanitizer());
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
 * frontArchivesPublished
 *
 * @param array $arguments
 * @param ArchivesProviderModel $archivesProviderModel
 * @return mixed
 * 
 */
public static function frontArchivesPublished(array $arguments, ArchivesProviderModel $archivesProviderModel)
{
 self::$archivesProviderModel = $archivesProviderModel;
 return self::$archivesProviderModel->getArchivesPublished(self::frontPaginator(app_reading_setting()['post_per_archive'], 'p'), self::frontSanitizer(), $arguments);
}

/**
 * frontSanitizer
 * 
 * @return object
 */
public static function frontSanitizer()
{
$front_sanitizer = new Sanitize();
return $front_sanitizer;
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
 $front_paginator = new Paginator($perPage, $instance);
 return $front_paginator;
}

}