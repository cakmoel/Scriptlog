<?php

require __DIR__ . '/simpletest/autorun.php';
require __DIR__ . '/../lib/utility/get-mime.php';

class MimeTypeTest extends UnitTestCase
{
    public function testMediaType()
    {
      $image_dir = __DIR__ . '/../public/files/pictures/thumbs/thumb_1b3262eb751d9d09f270b0dc9bc2d6f8-20191116.jpg';

      $result = get_mime($image_dir);

      $this->assertEqual($result, "image/jpeg");

    }
}