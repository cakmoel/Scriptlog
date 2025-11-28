<?php 
/**
 *  fuction sitemap_generator
 * 
 * @category function
 * @author nirmalakhanza nirmala.adiba.khanza@gmail.com 
 * @version 1.0
 * @return mixed|bool|array
 */

function sitemap_generator()
{

   $items = function_exists('medoo_column') ? medoo_column('tbl_posts', 'post_slug') : "";
   $sitemap = class_exists('SitemapService') ? new SiteMapService() : "";
   if (is_array($items)) {
      $sitemapItems = $sitemap->generateSitemap($items);
      return (!empty($sitemapItems)) ? true : false;
   }
   
}

