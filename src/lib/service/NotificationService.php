<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * NotificationService Class
 * 
 * Service for sending email notifications using Symfony Mailer
 * Supports SMTP configuration for various email providers
 *
 * @category  Service Class
 * @author    Scriptlog
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class NotificationService
{

    /**
     * Symfony Mailer Dsn
     * @var string
     */
    private $dsn;

    /**
     * From email address
     * @var string
     */
    private $fromEmail;

    /**
     * From name
     * @var string
     */
    private $fromName;

    /**
     * Constructor
     * 
     * Load SMTP configuration from config.php
     */
    public function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * Load mail configuration
     * 
     * @return void
     */
    private function loadConfiguration()
    {
        $config = [];
        
        if (file_exists(APP_ROOT . '/config.php')) {
            $config = require APP_ROOT . '/config.php';
        }

        $mailConfig = $config['mail'] ?? [];
        $smtp = $mailConfig['smtp'] ?? [];
        
        $host = $smtp['host'] ?? 'smtp.example.com';
        $port = $smtp['port'] ?? 587;
        $encryption = $smtp['encryption'] ?? 'tls';
        $username = $smtp['username'] ?? '';
        $password = $smtp['password'] ?? '';
        
        $from = $mailConfig['from'] ?? [];
        $this->fromEmail = $from['email'] ?? 'noreply@example.com';
        $this->fromName = $from['name'] ?? 'Application';

        $this->dsn = sprintf(
            'smtp://%s:%s@%s:%d?encryption=%s',
            urlencode($username),
            urlencode($password),
            $host,
            $port,
            $encryption
        );
    }

    /**
     * Send email notification
     * 
     * @param string $to Email address to send to
     * @param string $subject Email subject
     * @param string $body Email body (HTML or plain text)
     * @param array $options Additional options
     * @return bool
     */
    public function send($to, $subject, $body, $options = [])
    {
        $isHtml = $options['is_html'] ?? true;
        $Cc = $options['Cc'] ?? [];
        $Bcc = $options['Bcc'] ?? [];
        $replyTo = $options['reply_to'] ?? null;

        try {
            
            $transport = new \Symfony\Component\Mailer\Transport\DsnTransport(
                new \Symfony\Component\Mailer\Dsn\Dsn(
                    $this->dsn
                ),
                new \Symfony\Component\Mailer\Transport\SmtpTransport(
                    new \Symfony\Component\Mailer\Transport\StreamBufferFactory(
                        new \Symfony\Component\Mailer\Transport\StreamOptions()
                    )
                )
            );

            $mailer = new \Symfony\Component\Mailer\Mailer($transport);

            $email = (new \Symfony\Component\Mime\Email())
                ->from($this->fromEmail, $this->fromName)
                ->to($to)
                ->subject($subject);

            if ($isHtml) {
                $email->html($body);
            } else {
                $email->text($body);
            }

            if (!empty($Cc)) {
                foreach ($Cc as $cc) {
                    $email->addCc($cc);
                }
            }

            if (!empty($Bcc)) {
                foreach ($Bcc as $bcc) {
                    $email->addBcc($bcc);
                }
            }

            if ($replyTo) {
                $email->replyTo($replyTo);
            }

            $mailer->send($email);

            return true;

        } catch (\Exception $e) {
            error_log('Email send failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send GDPR data request confirmation email to user
     * 
     * @param string $email User's email address
     * @param string $requestType Type of request (export/deletion)
     * @param string $requestId Request ID
     * @return bool
     */
    public function sendDataRequestConfirmation($email, $requestType, $requestId)
    {
        $subject = 'Data ' . ucfirst($requestType) . ' Request Received';
        
        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3c8dbc; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Data Request Received</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>We have received your request to {$requestType} your personal data.</p>
            <p><strong>Request ID:</strong> #{$requestId}</p>
            <p>We will process your request within 30 days as required by GDPR.</p>
            <p>If you did not make this request, please contact us immediately.</p>
        </div>
        <div class="footer">
            <p>This is an automated message from Blogware.</p>
            <p><a href="{{unsubscribe_url}}">Unsubscribe</a> | <a href="{{privacy_url}}">Privacy Policy</a></p>
        </div>
    </div>
</body>
</html>
HTML;

        return $this->send($email, $subject, $body);
    }

    /**
     * Send notification to admin about new data request
     * 
     * @param string $adminEmail Admin email address
     * @param string $userEmail User's email address
     * @param string $requestType Type of request
     * @param string $requestId Request ID
     * @return bool
     */
    public function sendAdminNotification($adminEmail, $userEmail, $requestType, $requestId)
    {
        $subject = 'New GDPR Data Request - ' . ucfirst($requestType);
        
        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f0ad4e; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Data Request</h1>
        </div>
        <div class="content">
            <p>A new GDPR data request has been submitted:</p>
            <ul>
                <li><strong>Request Type:</strong> {$requestType}</li>
                <li><strong>User Email:</strong> {$userEmail}</li>
                <li><strong>Request ID:</strong> #{$requestId}</li>
                <li><strong>Date:</strong> {date('Y-m-d H:i:s')}</li>
            </ul>
            <p>Please review and process this request in the admin panel.</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $this->send($adminEmail, $subject, $body);
    }

    /**
     * Send data request completion email
     * 
     * @param string $email User's email address
     * @param string $requestType Type of request
     * @return bool
     */
    public function sendRequestCompleted($email, $requestType)
    {
        $subject = 'Your Data ' . ucfirst($requestType) . ' Request Has Been Processed';
        
        $actionText = ($requestType === 'deletion') 
            ? 'Your personal data has been anonymized as requested.' 
            : 'Your data export is ready for download.';
        
        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #5cb85c; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Request Completed</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>{$actionText}</p>
            <p>If you have any questions, please contact us.</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $this->send($email, $subject, $body);
    }

    /**
     * Send profile deletion confirmation
     * 
     * @param string $email User's email address
     * @return bool
     */
    public function sendProfileDeletionConfirmation($email)
    {
        $subject = 'Profile Deletion Confirmation';
        
        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #d9534f; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Profile Deleted</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Your profile has been permanently deleted from our system.</p>
            <p>As per GDPR requirements, your personal data has been anonymized.</p>
            <p>If you did not request this, please contact us immediately.</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $this->send($email, $subject, $body);
    }

    /**
     * Get SMTP configuration (for admin settings page)
     * 
     * @return array
     */
    public function getSmtpConfig()
    {
        $config = [];
        
        if (file_exists(APP_ROOT . '/config.php')) {
            $config = require APP_ROOT . '/config.php';
        }

        return $config['mail']['smtp'] ?? [];
    }
}
