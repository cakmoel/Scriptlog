<?php
/**
 * reset_password()
 * 
 * sending reset key to user
 * 
 * @param string $recipient
 * @param string $reset_key
 * 
 */
function reset_password($recipient, $reset_key)
{
    
 $site_info = app_info();
 $app_url = $site_info['app_url'];
 $site_name = $site_info['site_name'];
 $sender = $site_info['email_address'];
 $sanitize_sender = sanitize_email($sender);
 $subject = "Password Reset";
 $content = "<html><body>
            If you have never requested an information message about forgotten passwords, please feel free to ignore this email.<br />
            But If you are indeed asking for this information, then please click on the link below: <br /><br />
            <a href={$app_url}".APP_ADMIN."/recover-password.php?tempKey={$reset_key}>Recover Password</a><br /><br />
            Thank You, <br />
            <b>{$site_name}</b>
            </body></html>";

 // Define Headers
 $email_headers = 'From '. $sanitize_sender . "\r\n" .
                  'Reply-To: '. $sanitize_sender . "\r\n" .
                  'Return-Path: '. $sanitize_sender . "\r\n".
                  'MIME-Version: 1.0'. "\r\n".
                  'Content-Type: text/html; charset=utf-8'."\r\n".
                  'X-Mailer: PHP/' . PHP_VERSION . "\r\n" .
                  'X-Priority: 1'. "\r\n".
                  'X-Sender:'.$sanitize_sender."\r\n";

    if ( ( filter_var($recipient, FILTER_SANITIZE_EMAIL) ) && ( filter_var($recipient, FILTER_VALIDATE_EMAIL) ) ) {
        
        if ( ! mail( $recipient, $subject, $content, $email_headers) ) {

            scriptlog_error( "E-mail notification fail to sent" );

        } 
        
    }

}

/**
 * recover_password
 * 
 * changing password
 * 
 * @param string $user_pass
 * 
 */
function recover_password($user_pass, $user_email)
{
    $site_info = app_info();
    $app_url = $site_info['app_url'];
    $site_name = $site_info['site_name'];
    $sender = $site_info['email_address'];
    $sanitize_sender = sanitize_email($sender);

    $subject = "Password Changed";
    $content = "<html><body>
               Your password has been changed.<br />
               Here is your new password: <br /><br />
               <b>Password:</b>{$user_pass}<br />
               You can now login with your new password, by clicking the link below: <br />
               <a href={$app_url}".APP_ADMIN."/login.php><b>Login</b></a><br /><br />
               Thank You, <br />
               <b>{$site_name}</b>
               </body></html>";

    // Define Headers
    $email_headers = 'From '. $sanitize_sender . "\r\n" .
                     'Reply-To: '. $sanitize_sender . "\r\n" .
                     'Return-Path: '. $sanitize_sender . "\r\n".
                     'MIME-Version: 1.0'. "\r\n".
                     'Content-Type: text/html; charset=utf-8'."\r\n".
                     'X-Mailer: PHP/' . phpversion(). "\r\n" .
                     'X-Priority: 1'. "\r\n".
                     'X-Sender:'.$sanitize_sender."\r\n";

   if ( ( filter_var( $user_email, FILTER_SANITIZE_EMAIL) ) && ( filter_var( $user_email, FILTER_VALIDATE_EMAIL) ) ) {

      if ( ! mail($user_email, $subject, $content, $email_headers) ) {

        scriptlog_error( "E-mail notification fail to sent" );

      }
   }

 
}