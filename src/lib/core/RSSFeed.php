<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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
 * postdao 
 * post database access object 
 *
 * @var obj
 * 
 */
 private $frontContentProvider;
 
 
/**
 * Get posts records from database
 * 
 * @method protected getPostFeed()
 * @return array
 * 
 */
 private function grabPostFeed($limit)
 {

  $postProviderModel = new PostProviderModel();

  $this->frontContentProvider = FrontContentProvider::frontPostsFeed($limit, $postProviderModel);

  return $this->frontContentProvider;
  
 }
  
/**
 * setFileXML
 *
 * @param string $filename
 * @param string $mode
 * 
 */
 private function setFileXML($filename, $mode)
 {
   return fopen($filename, $mode);
 }

/**
 * Generate Feeds
 * 
 * @param string $title
 * @param string $link
 * @param string $description
 * 
 */
 public function generatePostFeed($title, $link, $description, $limit)
 {

   $dataPosts = is_iterable($this->grabPostFeed($limit)) ? $this->grabPostFeed($limit) : "";
   
   $rssFile = $this->setFileXML('rss.xml', 'w');
   
   $headerInit = '<?xml version="1.0" encoding="UTF-8"?> 
                  <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"> 
                  <channel>
                  <atom:link href="'.$link.'/rss.xml" rel="self" type="application/rss+xml" /> 
                  <title>'.$title.'</title> 
                  <link>'.$link.'</link> 
                  <description>'.$description.'</description> 
                  <language>id</language>';
   
   fwrite($rssFile, $headerInit);
   
   foreach ($dataPosts as $dataPost) {
       
     //build the full URL to the post
     //$url = APP_PROTOCOL . '://'. APP_HOSTNAME . dirname($_SERVER['PHP_SELF']) . '/post/'.(int)$dataPost['ID'].'/'.$dataPost['post_slug'];
     $url = permalinks((int)$dataPost['ID'])['post'].DS.'';

     // date post created
     $published = date(DATE_RSS, strtotime($dataPost['post_date']));
     
     // paragraf
     $content = htmlout(strip_tags(nl2br(html_entity_decode($dataPost['post_content']))));
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