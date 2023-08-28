<?php

require __DIR__ . '/../lib/main.php';

$postDao = class_exists('PostDao') ? new PostDao() : "";
$sanitize = class_exists('Sanitize') ? new Sanitize() : "";
$id = 1;
$author = 2;

$getPostById = $postDao->findPost($id, $sanitize, $author);

if (is_iterable($getPostById)) {

    $post_content = isset($getPostById['post_content']) ? safe_html($getPostById['post_content']) : "";

    echo $post_content;
}


