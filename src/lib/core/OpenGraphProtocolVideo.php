<?php
/**
 * Structured properties representations of Open Graph protocol media: image, video, audio
 *
 * @link http://ogp.me/#structured Open Graph protocol structured properties
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 1.3
 * @copyright Public Domain
 */
class OpenGraphProtocolVideo extends OpenGraphProtocolVisualMedia {
	/**
	 * Map a file extension to a registered Internet media type
	 * Include Flash as a video type due to its popularity as a wrapper
	 *
	 * @link http://www.iana.org/assignments/media-types/video/index.html IANA video types
	 * @param string $extension file extension
	 * @return string Internet media type in the format video/* or Flash
	 */
	public static function extension_to_media_type( $extension ) {
		if ( empty($extension) || ! is_string($extension) )
			return;
		if ( $extension === 'swf' )
			return 'application/x-shockwave-flash';
		else if ( $extension === 'mp4' )
			return 'video/mp4';
		else if ( $extension === 'ogv' )
			return 'video/ogg';
		else if ( $extension === 'webm' )
			return 'video/webm';
	}

	/**
	 * Set the Internet media type. Allow only video types + Flash wrapper.
	 *
	 * @param string $type Internet media type
	 */
	public function setType( $type ) {
		if ( $type === 'application/x-shockwave-flash' || substr_compare( $type, 'video/', 0, 6 ) === 0 )
			$this->type = $type;
		return $this;
    }
    
}