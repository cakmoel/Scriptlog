<?php
/**
 * File authorizer.php
 * checking whether session or cookies exists or not
 * 
 * @category checking whether cookies or session exists or not
 * @author   Vincy vincy@gmail.com
 * @see     https://phppot.com/php/secure-remember-me-for-login-using-php-session-and-cookies/
 * @see     https://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string
 * 
 */

$current_date = date("Y-m-d H:i:s", time()); 

$isUserLoggedIn = false;

// Check if loggedin session and redirect if session exists
if (!empty($_SESSION['user_id'])) {

    $isUserLoggedIn = true;

} elseif ((!empty($_COOKIE['cookie_user_login'])) && (!empty($_COOKIE['cookie_user_email'])) 
         && (!empty($_COOKIE['random_pwd'])) && (!empty($_COOKIE['random_selector']))) {  // Check if loggedin session exists

    $isPasswordVerified = false;
    $isSelectorVerified = false;
    $isExpiredDateVerified = false;
    
    // retrieve user token info
    $token_info = $authenticator -> findTokenByLogin($_COOKIE['cookie_user_login'], 0);

     // Validate random password cookie with database
    if (password_verify($_COOKIE['random_pwd'], $token_info['pwd_hash'])) {
        $isPasswordVerified = true;
    }

    // Validate random selector cookie with database
    if (password_verify($_COOKIE['random_selector'], $token_info['selector_hash'])) {
        $isSelectorVerified = true;
    }

    // check cookie expiration by date
    if ($token_info['expired_date'] >= $current_date) {
        $isExpiredDateVerified = true;
    }

    /** 
     * Redirect if all cookie based validation retuens true
     * Else, mark the token as expired and clear cookies
     */

    if ((!empty($token_info['ID'])) && $isPasswordVerified && $isSelectorVerified && $isExpiredDateVerified ) {

        $isUserLoggedIn = true;

    } else {

         if (!empty($token_info['ID'])) {

             $userToken -> updateTokenExpired($token_info['ID']);
             
         }

         $authenticator -> clearAuthCookies();
         
    } 

}