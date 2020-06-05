<?php 
/**
 * RssFeed Class
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class RSSFeed
{
/**
 * RSS Writer
 * 
 * @var string
 * 
 */
 private $rsswriter;

/**
 * postdao 
 * post database access object 
 *
 * @var string
 */
 private $postDao;
 
/**
 * Initialize an object properties
 * Constructor
 * 
 * @param string $dbc
 * 
 */
 public function __construct(PostDao $postDao, RSSWriter $rsswriter)
 {
  $this->postDao = $postDao;
  $this->rsswriter = $rsswriter;
 }
 
/**
 * Get posts records from database
 * 
 * @method protected getPostFeed()
 * @return array
 * 
 */
 protected function grabPostFeed($limit)
 {

  return $this->postDao->showPostFeeds($limit);

 }
  
/**
 * Generate Feeds
 * 
 * @param string $title
 * @param string $link
 * @param string $description
 * 
 */
 public function generatePostFeed($title, $link, $description, $attribs, $limit = 5)
 {

  $data_posts = $this->grabPostFeed($limit);
  
   $dataPosts = $this->getPostFeed();
   
   $rssFile = $this->setFileXML('rss.xml', 'w');
   
   $headerInit = '<?xml version="1.0" encoding="UTF-8"?> 
                  <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"> 
                  <channel>
                  <atom:link href="'.$link.'rss.xml" rel="self" type="application/rss+xml" /> 
                  <title>'.$title.'</title> 
                  <link>'.$link.'</link> 
                  <description>'.$description.'</description> 
                  <language>id</language>';
   
   fwrite($rssFile, $headerInit);
   
   foreach ($dataPosts as $dataPost) {
       
     //build the full URL to the post
     $url = APP_PROTOCOL . '://'. APP_HOSTNAME . dirname($_SERVER['PHP_SELF']) . '/post/'.(int)$dataPost['ID'].'/'.$dataPost['post_slug'];
     
     // date post created
     $published = date(DATE_RSS, strtotime($dataPost['post_date']));
     
     // paragraf
     $content = htmlentities(strip_tags(nl2br(html_entity_decode($dataPost['post_content']))));
     $paragraph = substr($content, 0, 220);
     $paragraph = substr($content, 0, strrpos($paragraph," "));
     
     // uniquid
     $guid = uniqid($dataPost['ID']);
     
     $body = '<item>
             <title>'.$dataPost['post_title'].'</title>
             <description>'.$paragraph.'..</description>
             <link>'.$url.'</link>
             <guid isPermaLink="false">'.$guid.'</guid>
             <pubDate>'.$published.'</pubDate>
             </item>';
     
     fwrite($rssFile, $body);
     
   }
 
   $footerInit = "</channel></rss>";
   
   fwrite($rssFile, $footerInit);
   fclose($rssFile);
   
 }
 
}