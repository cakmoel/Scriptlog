<?php

/**
 * checking_comment_author_email
 * 
 * @category function
 * @param string $email
 * @return bool|false
 * 
 */

use Egulias\EmailValidator\Validation\RFCValidation;

function checking_author_email($email)
{

   if ((!email_validation($email, new RFCValidation)) && (!email_multiple_validation($email))) {

      return false;
   }
}

/**
 * checking_comment_author_name
 *
 * @category function
 * @param string $name
 * 
 */
function checking_author_name($name)
{
   return (!preg_match("/^[a-zA-Z-' ]*$/", $name)) ? false : true;
}

/**
 * checking_comment_size
 *
 * @param array $fields
 */
function checking_comment_size($fields)
{
   return (true === form_size_validation($fields)) ? false : true;
}

/**
 * checking_form_payload
 *
 * @param array $values
 * @return void
 */
function checking_form_payload(array $values)
{

   if (check_form_request($values, ['post_id', 'author_name', 'author_email', 'comment_content', 'csrf']) === false) {

      header(APP_PROTOCOL . ' 413 Payload Too Large', true, 413);
      header('Status: 413 Payload Too Large');
      header('Retry-After: 3600');
      exit("413 Payload Too Large");
   }
   
}

/**
 * checking_comment_request
 *
 */
function checking_comment_request()
{

   if ('POST' !== $_SERVER['REQUEST_METHOD']) {

      $protocol = isset($_SERVER['SERVER_PROTOCOL']) ?:  $_SERVER['SERVER_PROTOCOL'];

      if (!in_array($protocol, array('HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3'), true)) {
         $protocol = 'HTTP/1.0';
      }

      header('Allow: POST');
      header("$protocol 405 Method Not Allowed");
      header('Content-Type: text/plain');
      exit;
   }
}

/**
 * checking_block_csrf
 *
 * @param string $csrf
 */
function checking_block_csrf($csrf)
{
   $valid = !empty($csrf) && verify_form_token('comment_form', $csrf);

   if (!$valid) {

      http_response_code(405);
      exit("405 Method Not Allowed");
   }

}

/**
 * checking_form_input()
 *
 * @param string $author_name
 * @param string $author_email
 * @param string $comment_content
 * 
 */
function checking_form_input($author_name, $author_email, $comment_content)
{

   $errors = [];

   $form_fields = ['author_name' => 90, 'author_email' => 120, 'comment_content' => 320];

   if (checking_comment_size($form_fields) === false) {

      $errors['errorMessage'] = "Form data is longer than allowed";
   }

   if ((!empty($author_email)) && (checking_author_email($author_email) === false)) {

      $errors['errorMessage'] = MESSAGE_INVALID_EMAILADDRESS;
   }

   if (empty($author_name) || empty($comment_content)) {

      $errors['errorMessage'] = "All column required must be filled";
   }

   if (checking_author_name($author_name) === false) {

      $errors['errorMessage'] = "Please enter a valid name";
   }

   return array($errors);

}

/**
 * processing_comment
 *
 * @param array $values
 * 
 */
function processing_comment(array $values)
{

   $postId = ( isset($values['post_id']) && $values['post_id'] == $_POST['post_id'] ? abs((int)$_POST['post_id']) : 0);
   $author_name = (isset($values['author_name']) && $values['author_name'] == $_POST['author_name'] ? prevent_injection($values['author_name']) : null);
   $author_email = (isset($values['author_email']) && $values['author_email'] == $_POST['author_email'] ? prevent_injection($values['author_email']) : null);
   $comment_content = (isset($values['comment_content']) && $values['comment_content'] == $_POST['comment_content'] ? prevent_injection($values['comment_content']) : null);
   $csrf = (isset($values['csrf']) && $values['csrf'] == $_POST['csrf'] ? $values['csrf'] : "");
   $comment_at = date("Y-m-d H:i:s");
   
   $commentProvider = new CommentProviderModel();

   checking_comment_request();

   checking_form_payload($values);

   checking_block_csrf($csrf);

   checking_form_input($author_name, $author_email, $comment_content);

   $bind = [

      'comment_post_id' => $postId,
      'comment_author_name' => $author_name,
      'comment_author_ip' => get_ip_address(),
      'comment_author_email' => $author_email,
      'comment_content' => $comment_content,
      'comment_date' => $comment_at 
    ];
    
   FrontContentProvider::frontNewCommentByPost($bind, $commentProvider);
    
}
