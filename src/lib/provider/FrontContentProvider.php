<?php
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
 * postProviderMpde;
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
 * frontPermalinks
 *
 * @param int|num $id
 * @return boolean|object return false if not return object
 * 
 */
public static function frontPermalinks($id)
{
 return permalinks($id);
}

/**
 * frontRandomHeadlinesPosts
 *
 * @param PostProviderModel $postProviderModel
 * @return mixed
 * 
 */
public static function frontRandomHeadlinesPosts(PostProviderModel $postProviderModel)
{
 self::$postProviderModel = $postProviderModel;
 return self::$postProviderModel->getRandomHeadlinesPosts();
}

/**
 * frontLatestPosts
 *
 * @param int|num $position
 * @param int|num $limit
 * @param PostProviderModel $postProviderModel
 * @param PDO::FETCH_MODE static $fetchMode = null
 * @return mixed
 * 
 */
public static function frontLatestPosts($position, $limit, PostProviderModel $postProviderModel, $fetchMode = null)
{
 self::$postProviderModel = $postProviderModel;
 return self::$postProviderModel->getLatestPosts($position, $limit, $fetchMode);
}

/**
 * frontRandomPosts
 *
 * @param int|num $limit
 * @param PostProviderModel $postProviderModel
 * @return mixed
 *  
 */
public static function frontRandomPosts($limit, PostProviderModel $postProviderModel)
{
 self::$postProviderModel = $postProviderModel;
 return self::$postProviderModel->getRandomPosts($limit);
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
 return self::$postProviderModel->getPostById($postId, new Sanitize);
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
 return self::$pageProviderModel->getPageBySlug($slug, new Sanitize);
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
 return self::$topicProviderModel->getTopicBySlug($slug, new Sanitize);
}

/**
 * FrontPostTopic
 *
 * @param int|numeric $postId
 * @param object $sanitize
 * @return mixed
 * 
 */
public static function frontPostTopic($postId, TopicProviderModel $topicProviderModel)
{
 self::$topicProviderModel = $topicProviderModel;
 return self::$topicProviderModel->getPostTopic($postId, new Sanitize);
}


}