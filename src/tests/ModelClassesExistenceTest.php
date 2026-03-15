<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class ModelClassesExistenceTest extends TestCase
{
    
    public function testPostModelClassExists(): void
    {
        $this->assertTrue(class_exists('PostModel'));
    }
    
    public function testTopicModelClassExists(): void
    {
        $this->assertTrue(class_exists('TopicModel'));
    }
    
    public function testTagModelClassExists(): void
    {
        $this->assertTrue(class_exists('TagModel'));
    }
    
    public function testPageModelClassExists(): void
    {
        $this->assertTrue(class_exists('PageModel'));
    }
    
    public function testGalleryModelClassExists(): void
    {
        $this->assertTrue(class_exists('GalleryModel'));
    }
    
    public function testFrontContentModelClassExists(): void
    {
        $this->assertTrue(class_exists('FrontContentModel'));
    }
    
    public function testDownloadModelClassExists(): void
    {
        $this->assertTrue(class_exists('DownloadModel'));
    }
    
    public function testCommentModelClassExists(): void
    {
        $this->assertTrue(class_exists('CommentModel'));
    }
    
    public function testArchivesModelClassExists(): void
    {
        $this->assertTrue(class_exists('ArchivesModel'));
    }
}
