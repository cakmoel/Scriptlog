<?php
/**
 * class AtomWriter
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */

use Laminas\Feed\Writer\Feed;

class AtomWriter
{

  private $frontContentProvider;

  private $atomFeed;

  private $entry;

  private function grabPostFeed($limit)
  {

    $postProviderModel = new PostProviderModel();

    $this->frontContentProvider = FrontContentProvider::frontPostsFeed($limit, $postProviderModel);

    return $this->frontContentProvider;
  }

  private function initializeFeed()
  {
    $this->atomFeed = new Feed();

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

  public function generateAtomFeed($title, $uri, $feed_link, $limit)
  {

    $this->entries = is_iterable($this->grabPostFeed($limit)) ? $this->grabPostFeed($limit) : "";

    $feed = $this->setHeaderFeed($title, $uri, $feed_link);

    $this->entry = $feed->createEntry();

    foreach ($this->entries as $entry) {

      $feed_id = isset($entry['ID']) ? intval((int)$entry['ID']) : "";
      $feed_title = isset($entry['post_title']) ? htmlout($entry['post_title']) : "";
      $feed_created = isset($entry['post_date']) ? htmlout(strtotime($entry['post_date'] )): "";
      $feed_created = new DateTime("@$feed_created");
      $feed_created->format('d-m-Y H:i:s');
      
      $feed_modified = isset($entry['post_modified']) ? htmlout(strtotime($entry['post_modified'])) : "";
      $feed_modified = new DateTime("@$feed_modified");
      $feed_modified->format('d-m-Y H:i:s');
      
      $feed_permalinks = isset($entry['ID']) ? permalinks($feed_id)['post'] : "";
      $feed_author = (isset($entry['user_login']) || isset($entry['user_fullname']) ? htmlout($entry['user_login']) : htmlout($entry['user_fullname']));
      $feed_content = isset($entry['post_content']) ? htmlout(strip_tags(nl2br(html_entity_decode($entry['post_content'])))) : "";
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