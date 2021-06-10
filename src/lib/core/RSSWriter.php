<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * RssFeedGenerator Class
 * 
 * @category Core Class
 * @author  Frank M.Kromann
 * @license MIT
 * @version 1.0
 * @since  Since Release 1.0
 * 
 */
class RSSWriter extends DOMDocument
{

private $channel;

private $rss;

public function __construct($xml = "1.0", $encoding = "UTF-8")
{
  
  parent::__construct($xml, $encoding);

  $this->formatOutput = true;

  $this->rss = $this->createElement("rss");
  
  $this->rss->setAttribute("version", "2.0");

  $this->channel = $this->createElement("channel");

}

public function setChannel($title, $description, $link, $pubdate, $ttl=1800)
{
 
 $element = $this->createElement('title');
 $element->appendChild($this->createTextNode($title));
 $this->channel->appendChild($element);

 $element = $this->createElement('description');
 $element->appendChild($this->createTextNode($description));
 $this->channel->appendChild($element);

 $element = $this->createElement('link');
 $element->appendChild($this->createTextNode($link));
 $this->channel->appendChild($element);

 $element = $this->createElement('pubDate');
 $element->appendChild($this->createTextNode(gmdate("D, d M Y H:i:s", (int)$pubdate) . " GMT"));
 $this->channel->appendChild($element);

 $element = $this->createElement('ttl');
 $element->appendChild($this->createTextNode($ttl));
 $this->channel->appendChild($element);

}

public function addItem($title, $link, $description, $attribs = null)
{
 
 $item = $this->createElement("item");

 if (!empty($title)) {

    $obj = $this->createElement("title");
    $obj->appendChild($this->createTextNode($title));
    $item->appendChild($obj);

 }

 if (!empty($link)) {

   $obj = $this->createElement("link");
   $obj->appendChild($this->createTextNode($link));
   $item->appendChild($obj);

 }

 if (!empty($description)) {

   $obj = $this->createElement("description");
   $obj->appendChild($this->createTextNode($description));
   $item->appendChild($obj);

 }

 if (!empty($attribs["pubDate"])) {

    $obj = $this->createElement("pubDate");
    $obj->appendChild($this->createTextNode(gmdate("D, d M Y H:i:s", (int)$attribs["pubDate"]) . " GMT"));
    $item->appendChild($obj);

 }

 if (!empty($attribs["category"])) {

  $obj = $this->createElement("category");
  $obj->appendChild($this->createTextNode($attribs["category"]));
  $item->appendChild($obj);

 }

 if (!empty($attribs["author"])) {

  $obj = $this->createElement("author");
  $obj->appendChild($this->createTextNode($attribs["author"]));
  $item->appendChild($obj);

 }

 if (!empty($attribs["guid"])) {

  $obj = $this->createElement("guid");
  $obj->appendChild($this->createTextNode($attribs["guid"]));
  $item->appendChild($obj);

 }

 if (!empty($attribs["comments"])) {

  $obj = $this->createElement("comments");
  $obj->appendChild($this->createTextNode($attribs["comments"]));
  $item->appendChild($obj);

 }

 $this->channel->appendChild($item);

}

public function output($file_name = "rss.xml")
{

  $this->rss->appendChild($this->channel);
  $this->appendChild($this->rss);

  header("Content-Type: text/xml; name=\"{$file_name}\"");
  header("Content-Disposition: inline; filename=\"{$file_name}\"");

  echo $this->saveXML();

}

}
