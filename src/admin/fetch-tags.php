<?php 
include __DIR__ . '/../lib/main.php';

$result = [];

if (isset($_GET['term']) && access_control_list(ActionConst::POSTS)) {

  $term = escape_html($_GET['term']);
 
  $sql =  "SELECT DISTINCT topic_title FROM tbl_topics WHERE topic_title LIKE '%".$term."%' LIMIT 25";

  $stmt = db_simple_query($sql);

  if ($stmt->num_rows > 0) {

    while ($row = $stmt->fetch_assoc()) {
  
      $result[] = prevent_injection($row['topic_title']);

    }

  }
  
} else {

  http_response_code(405);
  exit("Sorry, Method Not Allowed");

}

echo json_encode($result, JSON_PRETTY_PRINT);