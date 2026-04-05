<?php

/**
 * parse_post_id()
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 *
 * @return integer
 *
 */
function parse_post_id(): int
{

    $postId = 0;

    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_segments = explode('/', trim($url_path, '/'));

    if (isset($path_segments[1])) {
        $postId = intval($path_segments[1]);
    }

    if (isset($_GET['p'])) {
        $postId = intval($_GET['p']);
    }

    return purify_dirty_html($postId);
}
