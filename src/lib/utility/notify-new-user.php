<?php
/**
 * Mail Sender Function
 * 
 * @category Function
 * @param string $sender
 * @param string $recipient
 * @param string $subject
 * @param string $content
 * 
 */
function notify_new_user($recipient, $user_pass)
{
  
  $site_info = function_exists('app_info') ? app_info() : "";
  $app_url = isset($site_info['app_url']) ? $site_info['app_url'] : "";
  $site_name = isset($site_info['site_name']) ? $site_info['site_name'] : "";
  $activation_key = function_exists('') ? user_activation_key($recipient.get_ip_address()) : "";
  $sender = isset($site_info['email_address']) ? $site_info['email_address'] : "";
  $sanitize_sender = function_exists('sanitize_email') ? sanitize_email($sender) : "";
  
  $mailer = class_exists('Mailer') ? new Mailer() : "";

  $subject = "Join for The Best Team in Town!";
  $content = "<html><body>
              If you never ask to be a user at {$site_name}.
              please feel free to ignore this email.
              But if you are asking for this information,
              here is your profile data:<br />
              <b>Email address:</b>{$recipient}<br />
              <b>Password:</b>{$user_pass}<br />
              Activate your account by clicking the link below:<br />
              <a href={$app_url}".APP_ADMIN.DIRECTORY_SEPARATOR."activate-user.php?key={$activation_key}".">Activate My Account</a><br /><br />
              Thank you, <br />
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
   
    if (filter_var($recipient, FILTER_SANITIZE_EMAIL)) {
        
        if ((filter_var($recipient, FILTER_VALIDATE_EMAIL)) && (false === $mailer->send($recipient, $subject, $content, $email_headers))) {

            scriptlog_error("E-mail notification fail to sent", E_USER_ERROR);
            
        }
        
    }
    
}