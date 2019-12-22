<?php
/**
 * Function medialib link
 * generate link media location
 * 
 * @param string $media_type
 * @param string $media_filename
 * @return string
 * 
 */
function medialib_link($media_type, $media_filename)
{
 
  switch ($media_type) {

      case "image/jpeg":
      case "image/gif":
      case "image/png":

        $image_link = app_url().APP_IMAGE.'thumbs/thumb_'.rawurlencode(basename($media_filename));

        return $image_link;
          
        break;

      case 'audio/mp3':
      case 'audio/ogg':
      case 'audio/mpeg':
        
        $audio_link = app_url().APP_AUDIO.rawurlencode(basename($media_filename));

        return $audio_link;

        break;
      
      default:
          # code...
          break;
  }
}