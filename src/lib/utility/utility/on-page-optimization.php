<?php
/**
 * on-page-optimization.php
 * 
 * This file contain functions generate on-page optimization for better SEO
 * generate_schema_org
 * @category Function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
use Melbahja\Seo\Schema;
use Melbahja\Seo\Schema\Thing;
use Melbahja\Seo\MetaTags;

/**
 * generate_schema_org()
 * 
 * generating schema.org tag for structured data on the internet
 * 
 * @category Function
 * @author M.Noermoehammad
 * @license MIT
 * @uses new Schema()
 * @uses Thing()
 * @param string $url
 * @param string $name
 * @param string $image
 * @param string $description
 * @param string $text
 * @param string $thumbnailUrl
 * @param string $datePublished
 * @param string $dateModified
 * @version 1.0
 * 
 */
function generate_schema_org($name = null, $url = null, $image = null, $description = null, $text = null, $thumbnailUrl = null, $datePublished = null, $dateModified = null)
{

$contentReferenceTime = date(DATE_ATOM);

return new Schema(
    new Thing('Blog', [

        'abstract' => $description,
        'url' => $url,
        'name' => $name,
        'image' => $image,
        'description' => $description,
        'text' => $text, 
        'thumbnailUrl' => $thumbnailUrl,
        'datePublished' => $datePublished,
        'dateModified' => $dateModified,
        'contentReferenceTime' => $contentReferenceTime,
        'maintainer' => APP_TITLE
        
    ]));

}

/**
 * generate_meta_tags
 * 
 * @param string|null $title
 * @param string|null $description
 *
 */
function generate_meta_tags($title = null, $description = "", $keywords = "", $author = "", $image = "", $canonical = "")
{

$metatags = new MetaTags();

$metatags
    ->title($title)
    ->description($description)
    ->meta('keywords', $keywords)
    ->meta('author', $author)
    ->image($image)
    ->canonical($canonical);

return $metatags;

}