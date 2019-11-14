<?php

$path_requested = find_request(0);
$path1 = find_request(1);
$path2 = find_request(2);

$dispatcher =  new Dispatcher();
$findParam = $dispatcher -> findRequestParam();

$matched = (is_array($findParam) && array_key_exists(0, $findParam)) ? $findParam[0] : '';
$param1 = (is_array($findParam) && array_key_exists(1, $findParam)) ? $findParam[1] : '';
$param2 = (is_array($findParam) && array_key_exists(2, $findParam)) ? $findParam[2] : '';

echo '<br>';
echo '<pre>';
echo "first call request with <b>findRequestParam</b> function: ".$matched;
echo '<br>';
echo "second call request with <b>findRequestPath</b> function: " .$path_requested . DS . $path1 . DS . $path2;
echo '<br>';
echo "<b>findRequestParam</b> 2nd parameter matched by rules: ".$param1." and equal to <b>findRequestPath</b> 2nd path parameter requested: ".$path1;
echo '</pre>';