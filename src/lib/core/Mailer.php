<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class Mailer
 * Send e-mail via mail php function  
 * 
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class Mailer
{

	/**
	 * 1st destination
	 * send e-mail
	 * @var string
	 */
	private $to;

	/**
	 * 2nd destination
	 * send e-mail
	 * @var string
	 */
	private $cc;

	/**
	 * 3rd destination
	 * send e-mail
	 * @var string
	 */
	private $bcc;

	/**
	 * email's sender
	 * @var string
	 */
	private $from;

	/**
	 * email's subject
	 * @var string
	 */
	private $subject;

	/**
	 * set properties
	 * for sending text message
	 * @var string
	 */
	private $sendText;

	/**
	 * set properties
	 * text email message body
	 * @var string
	 */
	private $textBody;

	/**
	 * set properties
	 * send email as HTML
	 * @var string
	 */
	private $sendHTML;

	/**
	 * set properties
	 * text HTML message body
	 * @var string
	 */
	private $HTMLBody;

	/**
	 * Initialize the message parts with blank or default values
	 */
	public function __construct()
	{
		$this->to   = '';
		$this->cc   = '';
		$this->bcc  = '';
		$this->from = '';
		$this->subject = '';
		$this->sendText = true;
		$this->textBody = '';
		$this->sendHTML = false;
		$this->HTMLBody = '';
	}

	/**
	 * set send to
	 * @param string $value
	 */
	public function setSendTo($value)
	{
		$this->to = $value;
	}

	/**
	 * set send CC
	 * @param string $value
	 */
	public function setSendCc($value)
	{
		$this->cc = $value;
	}

	/**
	 * set send BCC
	 * @param string $value
	 */
	public function setSendBcc($value)
	{
		$this->bcc = $value;
	}

	/**
	 * set email's sender
	 * @param string $value
	 */
	public function setFrom($value)
	{
		$this->from = $value;
	}

	/**
	 * set email's subject
	 * @param string $value
	 */
	public function setSubject($value)
	{
		$this->subject = $value;
	}

	/**
	 * set whether to send email as text
	 * @param string $value
	 */
	public function setSendText($value)
	{
		$this->sendText = $value;
	}

	/**
	 * set text email message body
	 * @param string $value
	 */
	public function setTextBody($value)
	{
		$this->sendText = true;
		$this->textBody = $value;
	}

	/**
	 * set whether to send email as HTML
	 * @param string $value
	 */
	public function setSendHTML($value)
	{
		$this->sendHTML = $value;
	}

	/**
	 * set text HTML message body
	 * @param string $value
	 */
	public function setHTMLBody($value)
	{
		$this->sendHTML = true;
		$this->HTMLBody = $value;
	}

	/**
	 * Send 
	 * sending email
	 * 
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param string $headers
	 * @return boolean
	 */
	public function send($to = null, $subject = null, $message = null, $headers = null)
	{

		if (!is_null($to) && !is_null($subject) && !is_null($message)) {

			return mail($to, $subject, $message, $headers);
		
		} else {

			$headers = array();

			$eol = PHP_EOL;

			if (!empty($this->from)) {
				$headers[] = 'From: ' . $this->from;
			}

			if (!empty($this->cc)) {
				$headers[] = 'CC: ' . $this->cc;
			}

			if (!empty($this->bcc)) {
				$headers[] = 'BCC: ' . $this->bcc;
			}

			if ($this->sendText && !$this->sendHTML) {
				$message = $this->textBody;
			} elseif (!$this->sendText && $this->sendHTML) {
				$headers[] = 'MIME-Version: 1.0';
				$headers[] = 'Content-Type: text/html; charset="utf-8"';
				$headers[] = 'From: <' . self::getAppEmail() . '>';
				$headers[] = 'Reply-To: ' . self::getAppEmail();

				$message = $this->HTMLBody;

			} elseif ($this->sendText && $this->sendHTML) { //Multipart Message in MIME format

				$headers[] = 'MIME-Version: 1.0';
				$headers[] = "From: <" . self::getAppEmail() . ">";
				$headers[] = "Reply-To:" . self::getAppEmail();

				$message .= 'Content-Type: text/plain; charset="utf-8"';
				$message .= 'Content-Transfer-Encoding: 7bit';
				$message .= $this->textBody . "\n";

				$message .= 'Content-Type: text/html; charset="utf-8"' . "\n";
				$message .= 'Content-Transfer-Encoding: 7bit' . "\n";
				$message .= $this->HTMLBody . "\n";
			}

			return mail($this->to, $this->subject, $message, implode($eol, $headers));

		}
	}

	/**
	 * getAppEmail
	 *
	 * @method private static getAppEmail()
	 * 
	 * @return string
	 * 
	 */
	private static function getAppEmail()
	{

		$email_config = AppConfig::readConfiguration(invoke_config());

		return $email_config['app']['email'];

	}
}
