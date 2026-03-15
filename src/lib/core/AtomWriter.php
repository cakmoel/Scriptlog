<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class AtomWriter
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */

class AtomWriter
{

  /**
   * frontContentProvider
   *
   * @var object
   */
  private $frontContentProvider;

  /**
   * atommFeed
   *
   * @var object
   * 
   */
  private $atomFeed;

  /**
   * entry
   *
   * @var object
   */
  private $entry;

  /**
   * grabPostFeed
   *
   * @param int|num $limit
   * @return mixed
   */
  private function grabPostFeed($limit)
  {

    $postProviderModel = class_exists('PostModel') ? new PostModel() : "";

    $this->frontContentProvider = class_exists('FrontContentModel') ? FrontContentModel::frontPostsFeed($limit, $postProviderModel) : "";

    return $this->frontContentProvider;
  }

  /**
   * initializedFeed
   *
   */
  private function initializeFeed()
  {
    if (!isset($this->atomFeed)) {
      $this->atomFeed = new \Laminas\Feed\Writer\Feed();
    }

    return $this->atomFeed;
  }

  private function setHeaderFeed($title, $uri, $feed_link)
  {
    $feed = $this->initializeFeed();

    $feed->setTitle($title);
    $feed->setLink($uri);
    $feed->setFeedLink($feed_link, 'atom');
    $feed->addAuthor([
      'name' => app_info()['site_name'],
      'email' => app_info()['site_email'],
      'uri' => app_info()['app_url'],
    ]);

    $feed->setDateModified(time());

    return $feed;

  }

  /**
   * generateAtomFeed
   *
   * @param string $title
   * @param string $uri
   * @param string $feed_link
   * @param int|num $limit
   * 
   */
  public function generateAtomFeed($title, $uri, $feed_link, $limit)
  {

    $entries = is_iterable($this->grabPostFeed($limit)) ? $this->grabPostFeed($limit) : "";

    $feed = $this->setHeaderFeed($title, $uri, $feed_link);

    $this->entry = $feed->createEntry();

    foreach ($entries as $feed_post) {

      $feed_id = isset($feed_post['ID']) ? intval((int)$feed_post['ID']) : "";
      $feed_title = isset($feed_post['post_title']) ? htmlout($feed_post['post_title']) : "";
      
      $timestamp_created = isset($feed_post['post_date']) ? strtotime($feed_post['post_date']) : time();
      $feed_created = new DateTime();
      $feed_created->setTimestamp($timestamp_created);
      
      $timestamp_modified = isset($feed_post['post_modified']) && !empty($feed_post['post_modified']) 
        ? strtotime($feed_post['post_modified']) 
        : (isset($feed_post['post_date']) ? strtotime($feed_post['post_date']) : time());
      $feed_modified = new DateTime();
      $feed_modified->setTimestamp($timestamp_modified);
      
      $feed_permalinks = isset($feed_post['ID']) ? permalinks($feed_id)['post'] : "";
      $feed_author = (isset($feed_post['user_login']) || isset($feed_post['user_fullname']) ? htmlout($feed_post['user_login']) : htmlout($feed_post['user_fullname']));
      $feed_content = isset($feed_post['post_content']) ? htmlout(strip_tags(nl2br(html_entity_decode($feed_post['post_content'])))) : "";
      $feed_description = substr($feed_content, 0, 220);
      $feed_description = substr($feed_content, 0, strrpos($feed_description, " "));

      $this->entry->setTitle($feed_title);
      $this->entry->setLink($feed_permalinks);
      $this->entry->addAuthor([

        'name' => $feed_author,
        'email' => app_info()['site_email'],
        'uri' => app_info()['app_url'],

      ]);

      $this->entry->setDateModified($feed_modified);
      $this->entry->setDateCreated($feed_created);
      $this->entry->setDescription($feed_description);

      $feed->addEntry($this->entry);

      return $feed->export('atom');

    }
  }
}