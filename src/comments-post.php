<?php
/**
 * comments-posts.php
 * 
 * @category comments-post.php file -- processing comment form submission
 * @author M.Noermoehammad
 * @license https://opensource.org/licenses/MIT MIT License
 * @version 1.0
 * 
 */
require __DIR__ . '/lib/main.php';

if (isset($_SERVER["REQUEST_METHOD"]) && strtolower($_SERVER["REQUEST_METHOD"]) === 'post') {

processing_comment($_POST);

}