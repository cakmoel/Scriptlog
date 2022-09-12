<?php
require __DIR__ . '/lib/main.php';

if ( isset($_POST['SubmitComment']) ) {

processing_comment($_POST);

}