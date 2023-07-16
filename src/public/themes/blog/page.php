<?php 

$retrieve_page = function_exists('rewrite_status') && rewrite_status() === 'yes' ? retrieve_page(request_path()->param1, 'yes') : retrieve_page(HandleRequest::isQueryStringRequested()['value'], 'no');

$page_img = isset($retrieve_page['media_filename']) ? htmlout($retrieve_page['media_filename']) : "";
$page_id = isset($retrieve_page['ID']) ? (int)$retrieve_page['ID'] : "";
$page_title = isset($retrieve_page['post_title']) ? htmlout($retrieve_page['post_title']) : "";

?>

<div class="container">
    
</div>
<p class="text-big">
<?php
        
echo "<pre>";
$requestPath = new RequestPath();
echo "Request matched: {$requestPath->matched} <br>";
echo "Request param1: {$requestPath->param1} <br>";
echo "Request param2: {$requestPath->param2} <br>";
echo "Request param3: {$requestPath->param3} <br>";
echo "</pre>";
echo "<br>";
echo "<pre>";
print_r($_SERVER);
echo '</pre>';

echo "<br>Page executed in: ".$time = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);

?>
        
</p>