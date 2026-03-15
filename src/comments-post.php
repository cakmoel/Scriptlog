<?php
/**
 * comments-posts.php
 * 
 * @category comments-post.php file -- processing comment form submission
 * @author M.Noermoehammad
 * @license https://opensource.org/licenses/MIT MIT License
 * @version 1.0
 * 
 */
require __DIR__ . '/lib/main.php';

if (($_SERVER["REQUEST_METHOD"]) && (strtolower($_SERVER["REQUEST_METHOD"]) === 'post')) {

    if (function_exists('processing_comment')) {

        // Sanitize and validate input
        $comment_data = filter_input_array(INPUT_POST, [
            'post_id' => FILTER_SANITIZE_NUMBER_INT,
            'parent_id' => FILTER_SANITIZE_NUMBER_INT,
            'name' => FILTER_SANITIZE_SPECIAL_CHARS,
            'email' => FILTER_VALIDATE_EMAIL,
            'comment' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'csrf' => FILTER_SANITIZE_SPECIAL_CHARS
        ]);
    
        // Ensure data is valid
        if ($comment_data && !in_array(false, $comment_data, true)) {
            
            processing_comment($comment_data);
        } else {

            scriptlog_error("Invalid comment data received.");
        }
        
    } else {

        scriptlog_error("Function processing_comment not found");
    }
}
