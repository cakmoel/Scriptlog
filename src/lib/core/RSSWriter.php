<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * RSSWriter Class
 * 
 * @category Core Class
 * @author  David Sklar & Adam Trachtenberg
 * @license MIT
 * @version 1.0
 * @since  Since Release 1.0
 * 
 */
class RSSWriter extends DOMDocument
{

private $channel;

public function __construct($title, $link, $description)
{
  parent::__construct();

  $this->formatOutput = true;

  $root = $this->appendChild($this->createElement('rss'));
  $root->setAttribute('version', '2.0');

  $channel = $root->appendChild($this->createElement('channel'));

  $channel->appendChild($this->createElement('title', $title));
  $channel->appendChild($this->createElement('link', $link));
  $channel->appendChild($this->createElement('description', $description));

  $this->channel = $channel;

}

public function addItem($title, $link, $description)
{
  $item = $this->createElement('item');
  $item->appendChild($this->createElement('title', $title));
  $item->appendChild($this->createElement('link', $link));
  $item->appendChild($this->createElement('description', $description));

  $this->channel->appendChild($item);

}

}