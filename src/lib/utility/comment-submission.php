<?php
/**
 * checking_author_email
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
 * checking_author_name
 *
 * @category function
 * @param string $name
 * 
 */
function checking_author_name($name)
{
   return (!preg_match('/^[A-Z \'.-]{2,90}$/i', $name)) ? false : true;
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

   if (check_form_request($values, ['post_id', 'name', 'email', 'comment', 'csrf']) === false) {

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

      $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : "";

      if (!in_array($protocol, array('HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3'), true)) {
         $protocol = 'HTTP/1.0';
      }

      header('Allow: POST');
      header("$protocol 405 Method Not Allowed", true, 405);
      header('Content-Type: text/plain');
      exit;
   }
}

/**
 * checking_form_input
 *
 * @param string $csrf
 * 
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
 * checking_form_input
 *
 * @param string $author_name
 * @param string $author_email
 * @param string $comment_content
 */
function checking_form_input($author_name, $author_email, $comment_content)
{

   $errors = [];

   $form_fields = ['name' => 90, 'email' => 120, 'comment' => 320];

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
 * processing_comment()
 *
 * @param array $values
 */
function processing_comment(array $values)
{

   $errors = array();
   $form_data = array();

   checking_comment_request();
   
   $postId = (isset($values['post_id']) && $values['post_id'] == $_POST['post_id'] ? abs((int)$_POST['post_id']) : 0);
   $author_name = (isset($values['name']) && $values['name'] == $_POST['name'] ? prevent_injection($values['name']) : null);
   $author_email = (isset($values['email']) && $values['email'] == $_POST['email'] ? prevent_injection($values['email']) : null);
   $comment_content = (isset($values['comment']) && $values['comment'] == $_POST['comment'] ? prevent_injection($values['comment']) : null);
   $csrf = (isset($values['csrf']) && $values['csrf'] == $_POST['csrf'] ? $values['csrf'] : "");
   $comment_at = date_for_database();

   checking_form_payload($values);
   checking_block_csrf($csrf);

   $author_ip = get_ip_address();

   list($errors) = checking_form_input($author_name, $author_email, $comment_content);

   $bind = [

      'comment_post_id' => $postId,
      'comment_author_name' => $author_name,
      'comment_author_ip' => $author_ip,
      'comment_author_email' => $author_email,
      'comment_content' => $comment_content,
      'comment_date' => $comment_at
   ];

   $commentProvider = new CommentProviderModel();
   
   if ($commentProvider instanceof CommentProviderModel) {

      FrontContentProvider::frontNewCommentByPost($bind, $commentProvider);
   }
   
   if (!empty($errors)) {

      $form_data['success'] = false;
      $form_data['errors'] = $errors;
   } else {

      $form_data['success'] = true;
      $form_data['success_message'] = 'Comment was submitted successfully';
   }

   echo json_encode($form_data, JSON_PRETTY_PRINT);
}
