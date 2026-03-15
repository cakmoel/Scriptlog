<?php
/**
 * sitemap.php 
 * 
 * @category sitemap.php file to generate sitemap.xml
 * @author nirmala.adiba.khanza <nirmala.adiba.khanza@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @version 1.0
 * 
 */
require __DIR__ . '/lib/main.php';

$sitemap_index_path = __DIR__ . '/sitemap-index.xml';
$sitemap_path = __DIR__ . '/sitemap.xml';

// Delete existing sitemap files
if (file_exists($sitemap_index_path) && file_exists($sitemap_path)) {
    
    if (!unlink($sitemap_index_path)) {
        scriptlog_error("Failed to delete $sitemap_index_path");
    }

    if (!unlink($sitemap_path)) {
        scriptlog_error("Failed to delete $sitemap_path");
    }
} 

// Generate sitemap
if  (function_exists('sitemap_generator')) {

    if (false === sitemap_generator()) {
        scriptlog_error("Problem in generating sitemap");
    }
} else {
    scriptlog_error("sitemap_generator function not found");
}


