<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Structured properties representations of Open Graph protocol media: image, video, audio
 *
 * @link http://ogp.me/#structured Open Graph protocol structured properties
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 1.3
 * @copyright Public Domain
 */
class OpenGraphProtocolAudio extends OpenGraphProtocolMedia {
	/**
	 * Map a file extension to a registered Internet media type
	 * Include Flash as a video type due to its popularity as a wrapper
	 *
	 * @link http://www.iana.org/assignments/media-types/audio/index.html IANA video types
	 * @param string $extension file extension
	 * @return string Internet media type in the format audio/* or Flash
	 */
	public static function extension_to_media_type( $extension ) {
		if ( empty($extension) || ! is_string($extension) )
			return;
		if ( $extension === 'swf' )
			return 'application/x-shockwave-flash';
		else if ( $extension === 'mp3' )
			return 'audio/mpeg';
		else if ( $extension === 'm4a' )
			return 'audio/mp4';
		else if ( $extension === 'ogg' || $extension === 'oga' )
			return 'audio/ogg';
	}

	/**
	 * Set the Internet media type. Allow only audio types + Flash wrapper.
	 *
	 * @param string $type Internet media type
	 */
	public function setType( $type ) {
		if ( $type === 'application/x-shockwave-flash' || substr_compare( $type, 'audio/', 0, 6 ) === 0 )
			$this->type = $type;
		return $this;
    }
    
}