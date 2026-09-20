<?php
header('Content-Type: application/json');

require_once __DIR__ . '/lib/main.php';

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($post_id <= 0) {
    echo json_encode([]);
    exit;
}

$comments = fetch_comments($post_id, $offset);
echo json_encode($comments, JSON_PRETTY_PRINT);