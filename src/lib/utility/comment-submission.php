<?php

/**
 * checking_author_email
 *
 * @category function
 * @author M.Noermoehammad
 * @param string $email
 * @return bool|false
 *
 */

use Egulias\EmailValidator\Validation\RFCValidation;

function checking_author_email($email)
{

    if ((!email_validation($email, new RFCValidation())) && (!email_multiple_validation($email))) {
        return false;
    }
    return true;
}

/**
 * checking_author_name
 *
 * @category function
 * @author M.Noermoehammad
 * @param string $name
 *
 */
function checking_author_name($name)
{
    return preg_match('/^[A-Z \'.-]{2,90}$/i', $name); // Return true for valid names
}

/**
 * checking_comment_size
 *
 * @category function checking comment size
 * @author M.Noermoehammad
 * @param array $fields
 */
function checking_comment_size($fields)
{
    return (true === form_size_validation($fields)) ? false : true;
}

/**
 * checking_form_payload
 *
 * @category function checking form checking_form_payload
 * @author M.Noermoehammad
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
    $errors = [];

    $valid = !empty($csrf) && verify_form_token('comment_form', $csrf);

    if (!$valid) {
        $errors['error_message'] = 'Invalid CSRF token.';
    }

    return $errors;
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
        $errors['error_message'] = "Form data is longer than allowed";
    }

    if ((!empty($author_email)) && (checking_author_email($author_email) === false)) {
        $errors['error_message'] = MESSAGE_INVALID_EMAILADDRESS;
    }

    if (empty($author_name) || empty($comment_content)) {
        $errors['error_message'] = "All column required must be filled";
    }

    if (!checking_author_name($author_name)) {
        $errors['error_message'] = "Please enter a valid name";
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

    $postId = (!empty($values['post_id']) && $values['post_id'] == $_POST['post_id']) ? abs((int)$_POST['post_id']) : 0;
    $parent_id = (!empty($values['parent_id']) && $values['parent_id'] == $_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $author_name = (!empty($values['name']) && $values['name'] == filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS)) ? prevent_injection($values['name']) : "";
    $author_email = (!empty($values['email']) && $values['email'] == filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)) ? prevent_injection($values['email']) : null;
    $comment_content = (isset($values['comment']) && $values['comment'] == filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS)) ? prevent_injection($values['comment']) : null;
    $csrf = (isset($values['csrf']) && $values['csrf'] == $_POST['csrf'] ? $values['csrf'] : "");
    $comment_at = date_for_database();
    $author_ip = get_ip_address();

    checking_form_payload($values);

    $csrf_errors = checking_block_csrf($csrf);
    if (!empty($csrf_errors)) {
        $errors = array_merge($errors, $csrf_errors);
    }

    list($errors) = checking_form_input($author_name, $author_email, $comment_content);
    if (!empty($input_errors)) {
        $errors = array_merge($errors, $input_errors);
    }

    // Stop processing if there are errors
    if (!empty($errors)) {
        http_response_code(400);
        $form_data['success'] = false;
        $form_data['error_message'] = $errors;
        echo json_encode($form_data, JSON_PRETTY_PRINT);
        exit;
    }

    $bind = [

       'comment_post_id' => $postId,
       'comment_parent_id' => $parent_id,
       'comment_author_name' => $author_name,
       'comment_author_ip' => $author_ip,
       'comment_author_email' => $author_email,
       'comment_content' => $comment_content,
       'comment_date' => $comment_at
    ];

    $commentProvider = new CommentModel();

    if ($commentProvider instanceof CommentModel) {
        FrontContentModel::frontNewCommentByPost($bind, $commentProvider);
    }

    // Return success response
    http_response_code(200);
    $form_data['success'] = true;
    $form_data['success_message'] = 'Comment was submitted successfully';
    echo json_encode($form_data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * fetch_comments()
 *
 * @category theme function
 * @param int|numeric $postId
 * @param integer $offset
 *
 */
function fetch_comments(int $postId, int $offset = 0, ?int $limit = null): array
{
    $database = medoo_init();
    
    if (!$database) {
        error_log("Database connection failed in fetch_comments_medoo");
        return [];
    }

    $postId = max(0, $postId);
    $offset = max(0, $offset);

    if (is_null($limit)) {
        $settings = app_reading_setting();
        $limit = isset($settings['comment_per_post']) ? (int)$settings['comment_per_post'] : 3;
    }

    if ($postId <= 0) {
        return [];
    }

    // Handle both Medoo and Db class
    if (method_exists($database, 'select')) {
        // Medoo style
        $where = [
            "comment_post_id" => $postId,
            "comment_status" => "approved",
            "ORDER" => ["comment_date" => "DESC"],
            "LIMIT" => [$offset, $limit],
        ];

        try {
            $comments = $database->select("tbl_comments", [
                "ID",
                "comment_post_id",
                "comment_parent_id",
                "comment_author_name",
                "comment_content",
                "comment_status",
                "comment_date",
            ], $where);

            return $comments ?: [];
        } catch (Exception $e) {
            error_log("Error fetching comments: " . $e->getMessage());
            return [];
        }
    } elseif (method_exists($database, 'dbSelect')) {
        // Db class style
        $sql = "SELECT ID, comment_post_id, comment_parent_id, comment_author_name, 
                comment_content, comment_status, comment_date 
                FROM tbl_comments 
                WHERE comment_post_id = ? AND comment_status = 'approved' 
                ORDER BY comment_date DESC LIMIT ? OFFSET ?";
        
        try {
            return $database->dbSelect($sql, [$postId, $limit, $offset], PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching comments: " . $e->getMessage());
            return [];
        }
    }
    
    return [];
}
