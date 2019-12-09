<?php

//require __DIR__ . '/simpletest/autorun.php';
require __DIR__ . '/../lib/utility/get-mime.php';
require __DIR__ . '/../lib/utility/check-mime-type.php';
require __DIR__ . '/../lib/utility/invoke-filename.php';

$image_dir = __DIR__ . '/../public/files/pictures/thumbs/thumb_ebc51dfa5747db2814af6849bc7122b9-20191201.jpg';

var_dump(get_mime($image_dir));