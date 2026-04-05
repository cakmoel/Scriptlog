<?php

/**
 * human_login_id()
 *
 * generate random numbers for loginId
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return void
 *
 */
function human_login_id()
{
    return form_id("login");
}

/**
 * Verify_human_login_id()
 *
 * a function to verify human_login_id
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $loginId
 * @return void
 *
 */
function verify_human_login_id($loginId)
{

    if ((! isset($_SESSION['human_login_id'], $loginId)) || ($_SESSION['human_login_id'] !== $loginId)) {
        return false;
    }

    return true;
}

/**
 * review_login_attempt
 *
 * @category function
 * @author M.Noermoehammad
 * @param string $ip
 * @return bool
 *
 */
function review_login_attempt($ip)
{

    return alert_login_attempt($ip)['alert_login_attempt'] >= 20 ? true : false;
}

/**
 * checking_login_request()
 *
 * checking form login request whether
 * it is valid login requested or not
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $ip
 * @param integer|number $loginId
 * @param string $uniqueKey
 * @param array $values
 * @return void
 *
 */
function checking_login_request($ip, $loginId, $uniqueKey, array $values)
{

    if (function_exists('check_form_request') && check_form_request($values, ['login', 'user_pass', 'scriptpot_name', 'scriptpot_email', 'captcha_login', 'remember', 'csrf', 'LogIn']) === false) {
        header(APP_PROTOCOL . ' 413 Payload Too Large', true, 413);
        header('Status: 413 Payload Too Large');
        header('Retry-After: 3600');
        exit("413 Payload Too Large");
    }

    if (false === verify_human_login_id($loginId)) {
        http_response_code(400);
        exit("400 Bad Request");
    }

    if (! isset($uniqueKey) && ($uniqueKey !== md5(app_key() . $ip))) {
        http_response_code(400);
        exit("400 Bad Request ");
    }

    if (review_login_attempt($ip) === true) {
        write_log($ip, 'unpleasant login attempt!');

        delete_login_attempt($ip);
    }

    if (midfielder() === true) {
        (function_exists('sleep')) ? sleep(5) : "";

        defender();
    }
}

/**
 * Validates the login request context
 * Replaces checking_login_request logic with faster checks
 */
function validate_login_context($ip, $loginId, $uniqueKey, array $values)
{

    // 1. Check Payload Size & Required Fields (Fail Fast)
    if (function_exists('check_form_request') && check_form_request($values, ['login', 'user_pass', 'csrf', 'LogIn']) === false) {
        http_response_code(413);
        exit("413 Payload Too Large");
    }

    // 2. CSRF Check (Security)
    if (empty($values['csrf']) || !verify_form_token('login_form', $values['csrf'])) {
        throw new Exception("Session expired or invalid request. Please refresh.");
    }

    // 3. Honeypot Check (Anti-Bot) - Silent Fail
    // If hidden fields are filled, it's a bot.
    if (!empty($values['scriptpot_name']) || !empty($values['scriptpot_email'])) {
        // Return true to pretend it worked, but log it internally if needed.
        // Don't process DB queries.
        return false;
    }

    // 4. Session/Human Check
    if (!verify_human_login_id($loginId)) {
        http_response_code(400);
        exit("400 Bad Request - ID Mismatch");
    }

    // 5. Unique Key Check
    // Compare strictly
    if ($uniqueKey !== md5(app_key() . $ip)) {
        http_response_code(400);
        exit("400 Bad Request - Key Mismatch");
    }

    return true;
}

/**
 * processing_human_login()
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.1.0
 * @param object $authenticator
 * @param string $ip
 * @param integer|number $loginId
 * @param string $uniqueKey
 * @param array $values
 * @return array
 *
 */
function processing_human_login($authenticator, $ip, $loginId, $uniqueKey, $errors, array $values)
{
    // 1. Initialize Default Return
    $failed_attempt_count = 0;

    try {
        // Run context validation first
        if (validate_login_context($ip, $loginId, $uniqueKey, $values) === false) {
            throw new Exception("Anomaly detected.");
        }

        // 2. Rate Limiting (The "Midfielder/Defender" replacement)
        // Instead of sleeping, we check attempts immediately.
        $login_attempt_data = function_exists('get_login_attempt') ? get_login_attempt($ip) : [];
        $failed_attempt_count = $login_attempt_data['failed_login_attempt'] ?? 0;

        if ($failed_attempt_count >= 20) {
            // Soft Ban: Send HTTP 429 instead of sleep()
            header('HTTP/1.1 429 Too Many Requests');
            header('Retry-After: 900'); // 15 minutes
            throw new Exception("Too many attempts. Please try again in 15 minutes.");
        }

        // 3. Captcha Check (Only if attempts > 5)
        if ($failed_attempt_count >= 5) {
            $sess_captcha = Session::getInstance()->captcha_login ?? null;
            if (!isset($values['captcha_login']) || $values['captcha_login'] !== $sess_captcha) {
                throw new Exception("Incorrect Captcha code.");
            }
        }

        // 4. Input Preparation
        // NOTE: Do NOT sanitize passwords. Only usernames.
        $login_input = isset($values['login']) ? prevent_injection($values['login']) : '';
        $pass_input  = $values['user_pass'] ?? '';

        // 5. Check User Status (Lockout/Ban)
        // Optimize: Fetch minimal data first
        $user_data = function_exists('get_user_signin') ? get_user_signin($login_input) : null;

        if ($user_data) {
            $lockout_time = !empty($user_data['user_locked_until']) ? strtotime($user_data['user_locked_until']) : 0;
            if (time() < $lockout_time) {
                throw new Exception("Account is temporarily locked. Try again later.");
            }
            if (!empty($user_data['user_banned'])) {
                throw new Exception("Account is suspended.");
            }
        }

        // 6. Authentication
        // We do this LAST to save DB resources if previous checks failed
        $is_authenticated = false;
        if (is_a($authenticator, 'Authentication')) {
            $is_authenticated = $authenticator->validateUserAccount($login_input, $pass_input);
        }

        if ($is_authenticated) {
            // SUCCESS
            // Reset counters
            if (isset($user_data['user_signin_count']) && $user_data['user_signin_count'] > 0) {
                signin_count_to_zero($login_input);
                locked_down_to_null($login_input);
            }

            // Clean Session
            unset($_SESSION['human_login_id'], $_SESSION['captcha_login']);

            // Log user in
            $authenticator->login($_POST);
            delete_login_attempt($ip); // Clear IP log

            direct_page('index.php?load=dashboard', 302);
            exit();
        } else {
            // FAILURE
            // Increment IP attempts
            create_login_attempt($ip);
            $failed_attempt_count++; // Update local var for immediate UI feedback

            // Progressive delay - exponential backoff to slow brute force attacks
            if ($failed_attempt_count >= 3) {
                $delay = min(pow(2, $failed_attempt_count - 2), 30); // Max 30 seconds
                sleep($delay);
            }

            // Increment User specific lockouts (if user exists)
            // Note: We perform this silently to avoid enumeration
            if ($user_data) {
                $signin_count = $user_data['user_signin_count'] + 1;
                sign_in_count($signin_count, $login_input);

                // Logic for user locking based on multiples of 5 (reduced from 15)
                if ($signin_count % 5 == 0) {
                    $multiplicator = min(($signin_count / 5), 12); // Cap at 12 (60 minutes max)
                    locked_down_until($signin_count, date('Y-m-d H:i:s', time() + (300 * $multiplicator)), $login_input);
                }
            }

            // GENERIC ERROR MESSAGE (Security Best Practice)
            throw new Exception("Invalid username, email, or password.");
        }
    } catch (Exception $e) {
        $errors['errorMessage'] = $e->getMessage();
    }

    return [$errors, $failed_attempt_count];
}
