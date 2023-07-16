<?php
/**
 * protect_post
 *
 * @category function
 * @author M.Noermoehammad
 * @param string $post_content
 * @param string $visibility
 * @param string $password
 * @return mixed|array
 * 
 */
function protect_post($post_content, $visibility, $password)
{
  
 $sanitize_content = prevent_injection($post_content);

 $password_shield = password_hash($password, PASSWORD_DEFAULT);
 
 $protected = encrypt_post($sanitize_content, $visibility, $password_shield);

 return array("post_content" => $protected, "post_password" => $password_shield);

}

/**
 * grab_post_protected
 *
 * @param int|num $post_id
 * @return mixed|array
 */
function grab_post_protected($post_id)
{

 $grab_post = null;

 $idsanitized = sanitizer($post_id, 'sql');

 $grab_post = medoo_column_where("tbl_posts", ["ID", "post_content", "post_visibility"], ["ID" => $idsanitized]);
 
 $postId = isset($grab_post['ID']) ? abs((int)$grab_post['ID']) : 0;
 $content = isset($grab_post['post_content']) ? safe_html($grab_post['post_content']) : "";
 $visibility = isset($grab_post['post_visibility']) ? safe_html($grab_post['post_visibility']) : "";

 if (! $grab_post) {

    scriptlog_error("Post protected not found");

 }

 return array('post_id' => $postId, "post_content" => $content, "visibility" => $visibility);

}

/**
 * checking_post_password
 *
 * @param int|num $post_id
 * @param string $post_password
 * 
 */
function checking_post_password($post_id, $post_password)
{
 
$idsanitized = sanitizer($post_id, 'sql');

$grab_post = medoo_column_where("tbl_posts", ["ID", "post_password"], ["ID" => $idsanitized]);

$valid_post_protected = password_verify($post_password, $grab_post['post_password']);

return ($valid_post_protected) ? true : false;
 
}

/**
 * encrypt_post
 *
 * @param string $post_content
 * @param string $visibility
 * @param string $post_password
 * 
 */
function encrypt_post($post_content, $visibility, $post_password)
{
  return ($visibility == 'protected') ? encrypt($post_content, $post_password) : ""; 
}

/**
 * decrypt_post
 *
 * @uses grab_post_protected() function
 * @param int|num $post_id
 * @param string $visibility
 * @param string $post_password
 * 
 */
function decrypt_post($post_id, $post_password)
{
  
  $grab_post = grab_post_protected($post_id); // grab post protected based on post ID
  $id_post = isset($grab_post['post_id']) ? (int)$grab_post['post_id'] : 0;
  $content = isset($grab_post['post_content']) ? escape_html($grab_post['post_content']) : "";
  $visibility = isset($grab_post['visibility']) ? escape_html($grab_post['visibility']) : "";

  if (($visibility == 'protected') && (true === checking_post_password($id_post, $post_password))) {

    $grab_password = medoo_column_where("tbl_posts", "post_password", ["ID" => $id_post]);

    return ['post_content' => decrypt($content, $grab_password['post_password'])];

  }

}

/**
 * save_post_protected
 *
 * @param array $credentials
 * 
 */
function save_post_protected($credentials)
{

$path = __DIR__ . '/../../admin/ui/posts/.credential' . DIRECTORY_SEPARATOR;

$action_allowed = ['administrator', 'manager', 'editor', 'author', 'contributor'];

if (! in_array(user_privilege(), $action_allowed)) {
  
  scriptlog_error("Your are not allowed undertaking this action");

} else {

  if (is_dir($path) === false) {

    create_directory($path);

  }

   // create file for post protected to keep its credentials detail
   generate_post_credentials($path, $credentials);

}

}

/**
 * generate_post_credentials
 *
 * @param string $path
 * @param array $data
 * @return bool
 * 
 */
function generate_post_credentials($path, $data)
{

  $created_at = isset($data['post_date']) ? $data['post_date'] : null;
  $modified_at = isset($data['post_modified']) ?? $data['post_modified'];
  $passphrase = isset($data['passphrase']) ? md5(app_key().$data['passphrase']) : null;
  $credential_path = $path . DIRECTORY_SEPARATOR . $passphrase . '.php';

  $file = '<?php  
    
    return [' . "
                    
            'post' => [

                  'id' => '" . addslashes($data['post_id']) . "',
                  'author' => '" . addslashes($data['post_author']) . "',
                  'created_at' => '" . addslashes($created_at) . "',
                  'modified_at' => '" . addslashes($modified_at) . "'
                  
                ],
        
            'credentials' => [

                   'password'   => '" . addslashes($data['post_password']) . "',
                   'passphrase' => '" . addslashes($passphrase) . "',
                   
                ]

        ];";

  if (isset($_SESSION['post_protected'])) {

    file_put_contents($credential_path, $file, FILE_APPEND | LOCK_EX);

  }
  
  return false;
  
}