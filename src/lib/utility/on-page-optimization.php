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
function generate_schema_org($name = "", $url = "", $image = "", $text = "", $description = "", $thumbnailUrl = "", $datePublished = "", $dateModified = "")
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

        ])
    );
}

/**
 * generate_meta_tags
 *
 * @param string|null $title
 * @param string|null $description
 *
 */
function generate_meta_tags($title = "", $description = "", $author = "", $image = "", $canonical = "", $robots = false)
{

    $metatags = new MetaTags();

    // standard SEO Tags
    if ($robots === true) {
        $metatags->title($title)
                 ->description($description)
                 ->meta('author', $author)
                 ->meta('keywords', app_info()['site_keywords'])
                 ->meta('robots', "index, follow")
                 ->canonical($canonical);
    } else {
        $metatags->title($title)
                 ->description($description)
                 ->meta('author', $author)
                 ->meta('keywords', app_info()['site_keywords'])
                 ->meta('robots', "index, nofollow")
                 ->canonical($canonical);
    }

    // Open graph (og) Facebook
    $metatags->og('type', 'website')
        ->og('url', $canonical)
        ->og('image', $image)
        ->og('site_name', app_info()['site_name']);

    // Twitter card Tags - X.com
    $metatags->twitter('card', 'summary_large_image')
        ->twitter('site', '@getscriptlog')
        ->twitter('creator', $author)
        ->twitter('image', $image)
        ->twitter('image:alt', $title);

    return $metatags;
}
