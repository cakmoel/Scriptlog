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
class OpenGraphProtocolImage extends OpenGraphProtocolVisualMedia {
	/**
	 * Map a file extension to a registered Internet media type
	 *
	 * @link http://www.iana.org/assignments/media-types/image/index.html IANA image types
	 * @param string $extension file extension
	 * @return string Internet media type in the format image/* 
	 */
	public static function extension_to_media_type( $extension ) {
		if ( empty($extension) || ! is_string($extension) )
			return;
		if ( $extension === 'jpeg' || $extension === 'jpg' )
			return 'image/jpeg';
		else if ( $extension === 'png' )
			return 'image/png';
		else if ( $extension === 'gif' )
			return 'image/gif';
		else if ( $extension === 'svg' )
			return 'image/svg+sml';
		else if ( $extension === 'ico' )
			return 'image/vnd.microsoft.icon';
	}

	/**
	 * Set the Internet media type. Allow only image types.
	 *
	 * @param string $type Internet media type
	 */
	public function setType( $type ) {
		if ( substr_compare( $type, 'image/', 0, 6 ) === 0 )
			$this->type = $type;
		return $this;
    }
    
}
