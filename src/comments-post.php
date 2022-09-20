<?php
require __DIR__ . '/lib/main.php';

if ( isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] === 'POST') {

processing_comment($_POST);

}