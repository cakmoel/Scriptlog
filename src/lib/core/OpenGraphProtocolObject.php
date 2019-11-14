<?php
/**
 * Open Graph protocol global types
 *
 * @link http://ogp.me/#types Open Graph protocol global types
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 1.3
 * @copyright Public Domain
 */
abstract class OpenGraphProtocolObject {
	const PREFIX ='';
	const NS='';

	/**
	 * Output the object as HTML <meta> elements
	 * @return string HTML meta element string
	 */
	public function toHTML() {
		return rtrim( OpenGraphProtocol::buildHTML( get_object_vars($this), static::PREFIX ), PHP_EOL );
	}

	/**
	 * Convert a DateTime object to GMT and format as an ISO 8601 string.
	 * @param DateTime $date date to convert
	 * @return string ISO 8601 formatted datetime string
	 */
	public static function datetime_to_iso_8601( DateTime $date ) {
		$date->setTimezone(new DateTimeZone('GMT'));
		return $date->format('c');
	}

	/**
	 * Test a URL for validity.
	 *
	 * @uses OpenGraphProtocol::is_valid_url if OpenGraphProtocol::VERIFY_URLS is true
	 * @param string $url absolute URL addressable from the public web
	 * @return bool true if URL is non-empty string. if VERIFY_URLS set then URL must also properly respond to a HTTP request.
	 */
	public static function is_valid_url( $url ) {
		if ( is_string($url) && !empty($url) ) {
			if (OpenGraphProtocol::VERIFY_URLS) {
				$url = OpenGraphProtocol::is_valid_url( $url, array( 'text/html', 'application/xhtml+xml' ) );
				if (!empty($url))
					return true;
			} else {
				return true;
			}
		}
		return false;
    }
    
}