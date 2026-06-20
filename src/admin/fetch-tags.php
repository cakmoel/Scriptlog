<?php

/**
 *  fetch-tags.php
 *
 * @category retrieve tags from tabel topics
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 *
 */

require __DIR__ . '/../lib/main.php';

$result = [];

if (isset($_GET['term']) && access_control_list(ActionConst::POSTS)) {
    $term = $_GET['term'];

    $dbc = Registry::get('dbc');
    $sql = "SELECT DISTINCT topic_title FROM tbl_topics WHERE topic_title LIKE ? LIMIT 25";
    $stmt = $dbc->dbQuery($sql, ['%' . $term . '%']);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $result[] = isset($row['topic_title']) ? prevent_injection($row['topic_title']) : '';
    }
} else {
    http_response_code(405);
    exit("Sorry, Method Not Allowed");
}

echo json_encode($result, JSON_PRETTY_PRINT);
