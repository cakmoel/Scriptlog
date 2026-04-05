<?php

/**
 * protect_post
 *
 * Encrypts post content for password protection
 *
 * @param string $post_content
 * @param string $visibility
 * @param string $password
 * @return array
 */
function protect_post($post_content, $visibility, $password)
{
    $sanitize_content = prevent_injection($post_content);
    $password_shield = password_hash($password, PASSWORD_DEFAULT);
    $passphrase_key = md5(app_key() . $password);
    $protected = encrypt_post($sanitize_content, $visibility, $passphrase_key);

    return array(
        "post_content" => $protected, 
        "post_password" => $password_shield,
        "passphrase" => $passphrase_key
    );
}

/**
 * grab_post_protected
 *
 * Fetches protected post data from database
 *
 * @param int $post_id
 * @return array
 */
function grab_post_protected($post_id)
{
    $idsanitized = sanitizer($post_id, 'sql');
    $grab_post = medoo_column_where("tbl_posts", ["ID", "post_content", "post_visibility", "passphrase"], ["ID" => $idsanitized]);

    $first_row = is_array($grab_post) ? ($grab_post[0] ?? []) : $grab_post;

    $postId = isset($first_row['ID']) ? abs((int)$first_row['ID']) : 0;
    $content = isset($first_row['post_content']) ? safe_html($first_row['post_content']) : "";
    $visibility = isset($first_row['post_visibility']) ? safe_html($first_row['post_visibility']) : "";
    $passphrase = isset($first_row['passphrase']) ? $first_row['passphrase'] : "";

    if (empty($first_row)) {
        scriptlog_error("Post protected not found");
    }

    return array(
        'post_id' => $postId, 
        "post_content" => $content, 
        "visibility" => $visibility,
        "passphrase" => $passphrase
    );
}

/**
 * checking_post_password
 *
 * Verifies password against bcrypt hash stored in database
 *
 * @param int $post_id
 * @param string $post_password
 * @return bool
 */
function checking_post_password($post_id, $post_password)
{
    $idsanitized = sanitizer($post_id, 'sql');
    $grab_post = medoo_column_where("tbl_posts", ["ID", "post_password"], ["ID" => $idsanitized]);

    $post_password_hash = is_array($grab_post) ? ($grab_post[0]['post_password'] ?? '') : ($grab_post['post_password'] ?? '');
    
    if (empty($post_password_hash)) {
        return false;
    }

    return password_verify($post_password, $post_password_hash);
}

/**
 * encrypt_post
 *
 * Encrypts post content using AES-256-CBC
 *
 * @param string $post_content
 * @param string $visibility
 * @param string $passphrase_key
 * @return string
 */
function encrypt_post($post_content, $visibility, $passphrase_key)
{
    return ($visibility == 'protected') ? encrypt($post_content, $passphrase_key) : "";
}

/**
 * decrypt_post
 *
 * Decrypts post content using passphrase from database
 *
 * @param int $post_id
 * @param string $password - the actual password
 * @return array
 */
function decrypt_post($post_id, $password)
{
    $grab_post = grab_post_protected($post_id);
    $id_post = isset($grab_post['post_id']) ? (int)$grab_post['post_id'] : 0;
    $content = isset($grab_post['post_content']) ? escape_html($grab_post['post_content']) : "";
    $visibility = isset($grab_post['visibility']) ? escape_html($grab_post['visibility']) : "";
    $passphrase = isset($grab_post['passphrase']) ? $grab_post['passphrase'] : "";

    if (($visibility == 'protected') && (true === checking_post_password($id_post, $password))) {
        if (!empty($passphrase)) {
            return ['post_content' => decrypt($content, $passphrase)];
        }
    }
    
    return ['post_content' => ''];
}

/**
 * decrypt_post_admin
 *
 * Admin-only decryption - decrypts protected post without password verification
 * Used by admin panel to display protected post content for editing
 *
 * @param int $post_id
 * @return array
 */
function decrypt_post_admin($post_id)
{
    $grab_post = grab_post_protected($post_id);
    $content = isset($grab_post['post_content']) ? escape_html($grab_post['post_content']) : "";
    $visibility = isset($grab_post['visibility']) ? escape_html($grab_post['visibility']) : "";
    $passphrase = isset($grab_post['passphrase']) ? $grab_post['passphrase'] : "";

    if ($visibility == 'protected') {
        if (!empty($passphrase)) {
            $decrypted = decrypt($content, $passphrase);
            return ['post_content' => $decrypted];
        }
    }
    
    return ['post_content' => $content];
}

/**
 * track_failed_unlock_attempt
 *
 * Tracks failed password attempts for rate limiting
 * Uses file-based tracking with IP address + post ID
 *
 * @param int $post_id
 * @return bool
 */
function track_failed_unlock_attempt($post_id)
{
    $ip = get_ip_address();
    $identifier = md5($ip . '_' . $post_id);
    $log_dir = APP_ROOT . '/public/log/unlock_attempts/';
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . $identifier . '.json';
    $now = time();
    
    $attempts = [];
    if (file_exists($log_file)) {
        $data = @file_get_contents($log_file);
        if ($data) {
            $attempts = json_decode($data, true) ?: [];
        }
    }
    
    $attempts[] = $now;
    
    $attempts = array_filter($attempts, function($timestamp) use ($now) {
        return ($now - $timestamp) < 900;
    });
    
    file_put_contents($log_file, json_encode($attempts), LOCK_EX);
    
    return true;
}

/**
 * get_failed_unlock_attempts
 *
 * Gets the number of failed attempts for a post from an IP
 *
 * @param int $post_id
 * @return int
 */
function get_failed_unlock_attempts($post_id)
{
    $ip = get_ip_address();
    $identifier = md5($ip . '_' . $post_id);
    $log_file = APP_ROOT . '/public/log/unlock_attempts/' . $identifier . '.json';
    
    if (!file_exists($log_file)) {
        return 0;
    }
    
    $data = @file_get_contents($log_file);
    if (!$data) {
        return 0;
    }
    
    $attempts = json_decode($data, true) ?: [];
    $now = time();
    
    $recent_attempts = array_filter($attempts, function($timestamp) use ($now) {
        return ($now - $timestamp) < 900;
    });
    
    return count($recent_attempts);
}

/**
 * clear_failed_unlock_attempts
 *
 * Clears failed attempts after successful unlock
 *
 * @param int $post_id
 * @return bool
 */
function clear_failed_unlock_attempts($post_id)
{
    $ip = get_ip_address();
    $identifier = md5($ip . '_' . $post_id);
    $log_file = APP_ROOT . '/public/log/unlock_attempts/' . $identifier . '.json';
    
    if (file_exists($log_file)) {
        @unlink($log_file);
    }
    
    return true;
}

/**
 * is_unlock_rate_limited
 *
 * Checks if the unlock attempt should be rate limited
 * Allows max 5 attempts per 15 minutes per post per IP
 *
 * @param int $post_id
 * @return bool
 */
function is_unlock_rate_limited($post_id)
{
    $attempts = get_failed_unlock_attempts($post_id);
    return ($attempts >= 5);
}

/**
 * check_post_password_strength
 *
 * Validates password meets minimum security requirements
 *
 * @param string $password
 * @return bool
 */
function check_post_password_strength($password)
{
    if (strlen($password) < 8) {
        return false;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    
    return true;
}
