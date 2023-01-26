<?php
$request_path = new RequestPath();

echo "<pre></pre>";
echo "<pre>";
echo "Matched: " . $request_path->matched;
echo "<br>";
echo "Param1: " . $request_path->param1;
echo "<br>";
echo "Param2: " . $request_path->param2;