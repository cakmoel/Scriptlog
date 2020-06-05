<?php
/**
 * Catch weblink function
 * to extract HTML link from a web page founded within it
 * and return all link on it.
 * 
 * @param string $web_page
 * @return string
 * 
 */
function catch_weblink($web_page)
{

    $contents = file_get_contents($web_page);

    if (!$contents) return null;

    $links = [];

    $dom_doc = new DOMDocument();

    $dom_doc->loadHTML($contents);

    $xpath = new DOMXPath($dom_doc);

    $hrefs = $xpath->evaluate("/html/body//a");

    for ($i=0; $i < $hrefs->length; $i++) { 
   
        $links[$i] = absolute_url($web_page, $hrefs->$item($i)->getAttribute('href'));
        
    }

    return $links;

}