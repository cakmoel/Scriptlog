<p class="text-big">
<?php
        
echo "<pre>";
$requestPath = new RequestPath();
echo "Request matched: {$requestPath->matched} <br>";
echo "Request param1: {$requestPath->param1} <br>";
echo "Request param2: {$requestPath->param2} <br>";
echo "Request param3: {$requestPath->param3} <br>";
echo "</pre>";
        
echo "<pre>";
print_r($_SERVER);
echo '</pre>';

echo "<br>Page executed in: ".$time = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);

?>
        
</p>