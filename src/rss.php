<?php
/**
 * rss.php
 * 
 * @category rss.php file to write RSS feeds
 * @author M.Noermoehammad
 * @license https://opensource.org/licenses/MIT MIT License
 * @version 1.0
 * 
 */
require __DIR__ . '/lib/main.php';

$app_title = app_info()['site_name'];
$app_link = app_info()['app_url'];
$app_description = app_info()['site_description'];

$postProviderModel = new PostProviderModel();
$feeds = FrontContentProvider::frontPostsFeed(app_reading_setting()['post_per_rss'], $postProviderModel);

if ( is_iterable($feeds)) {

  $rss = new RSSWriter($app_title, $app_link, $app_description);

  foreach ( $feeds as $feed) {

    $feed_title = isset($feed['post_title']) ? htmlout($feed['post_title']) : "";
    $feed_link = isset($feed['ID']) ? permalinks($feed['ID'])['post'] : "";
    $description = isset($feed['post_content']) ? htmlout(strip_tags(nl2br(html_entity_decode($feed['post_content'])))) : "";
    $feed_description = substr($description, 0, 220);
    $feed_description = substr($description, 0, strrpos($feed_description," "));

    $rss->addItem($feed_title, $feed_link, $feed_description);

    print $rss->saveXML();

  }

}