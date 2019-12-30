<?php

class AtomWriter extends DOMDocument
{
    
private $name_space; // namespace

public function __conamespacetruct($title, $href, $name, $id)
{
 parent::__construct();

 $this->formatOutput = true;

 $this->name_space = 'http://www.w3.org/2005/Atom';

 $root = $this->appendChild($this->createElementNS($this->name_space, 'feed'));

 $root->appendChild($this->createElementNS($this->name_space, 'title', $title));

 $link = $root->appendChild($this->createElementNS($this->name_space, 'link'));
 
 $link->setAttribute('href', $href);

 $root->appendChild($this->createElementNS($this->name_space, 'updated', date(DATE_ATOM)));

 $author = $root->appendChild($this->createElementNS($this->name_space, 'author'));

 $author->appendChild($this->createElementNS($this->name_space, 'name', $name));

 $root->appendChild($this->createElementNS($this->name_space, 'id', $id));

}

public function addEntry($title, $link, $summary, $id)
{
 $entry = $this->createElementNS($this->name_space, 'entry');

 $entry->appendChild($this->createElementNS($this->name_space, 'title', $title));
 
 $entry->appendChild($this->createElementNS($this->name_space, 'link', $link));

 $entry->appendChild($this->createElementNS($this->name_space, 'id', $id));

 $entry->appendChild($this->createElementNS($this->name_space, 'updated', date(DATE_ATOM)));

 $entry->appendChild($this->createElementNS($this->name_space, 'summary', $summary));

 $this->documentElement->appendChild($entry);
 
}

}