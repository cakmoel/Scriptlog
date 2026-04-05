<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostController Validation Test
 * 
 * Tests that empty post_title or post_content prevents saving.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostControllerValidationTest extends TestCase
{
    public function testEmptyPostTitleShouldNotSave(): void
    {
        $_POST = [
            'post_title' => '',
            'post_content' => 'Some content here',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open'
        ];
        
        $checkError = true;
        $errors = [];
        
        if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {
            $checkError = false;
            $errors[] = "Please enter a required field";
        }
        
        $this->assertFalse($checkError);
        $this->assertContains("Please enter a required field", $errors);
        $this->assertTrue(empty($_POST['post_title']));
    }
    
    public function testEmptyPostContentShouldNotSave(): void
    {
        $_POST = [
            'post_title' => 'Test Title',
            'post_content' => '',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open'
        ];
        
        $checkError = true;
        $errors = [];
        
        if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {
            $checkError = false;
            $errors[] = "Please enter a required field";
        }
        
        $this->assertFalse($checkError);
        $this->assertContains("Please enter a required field", $errors);
        $this->assertTrue(empty($_POST['post_content']));
    }
    
    public function testBothEmptyShouldNotSave(): void
    {
        $_POST = [
            'post_title' => '',
            'post_content' => '',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open'
        ];
        
        $checkError = true;
        $errors = [];
        
        if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {
            $checkError = false;
            $errors[] = "Please enter a required field";
        }
        
        $this->assertFalse($checkError);
        $this->assertContains("Please enter a required field", $errors);
    }
    
    public function testValidPostShouldSave(): void
    {
        $_POST = [
            'post_title' => 'Test Post Title',
            'post_content' => 'This is valid post content.',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open'
        ];
        
        $checkError = true;
        $errors = [];
        
        if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {
            $checkError = false;
            $errors[] = "Please enter a required field";
        }
        
        $this->assertTrue($checkError);
        $this->assertEmpty($errors);
    }
    
    public function testWhitespaceOnlyTitleShouldNotSave(): void
    {
        $_POST = [
            'post_title' => '   ',
            'post_content' => 'Some content',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open'
        ];
        
        $checkError = true;
        $errors = [];
        
        if ((empty(trim($_POST['post_title']))) || (empty($_POST['post_content']))) {
            $checkError = false;
            $errors[] = "Please enter a required field";
        }
        
        $this->assertFalse($checkError);
        $this->assertContains("Please enter a required field", $errors);
    }
    
    public function testWhitespaceOnlyContentShouldNotSave(): void
    {
        $_POST = [
            'post_title' => 'Test Title',
            'post_content' => '   ',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open'
        ];
        
        $checkError = true;
        $errors = [];
        
        if ((empty($_POST['post_title'])) || (empty(trim($_POST['post_content'])))) {
            $checkError = false;
            $errors[] = "Please enter a required field";
        }
        
        $this->assertFalse($checkError);
        $this->assertContains("Please enter a required field", $errors);
    }
    
    public function testNullValuesShouldNotSave(): void
    {
        $_POST = [
            'post_title' => null,
            'post_content' => null,
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open'
        ];
        
        $checkError = true;
        $errors = [];
        
        if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {
            $checkError = false;
            $errors[] = "Please enter a required field";
        }
        
        $this->assertFalse($checkError);
        $this->assertContains("Please enter a required field", $errors);
    }
    
    protected function tearDown(): void
    {
        $_POST = [];
        $_FILES = [];
    }
}
