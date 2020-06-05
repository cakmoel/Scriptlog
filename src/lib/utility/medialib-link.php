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

use Intervention\Image\ImageManagerStatic as Image;

function medialib_link($media_type, $media_filename)
{
 
  switch ($media_type) {

      case "image/jpeg":
      case "image/gif":
      case "image/png":
      case "image/webp":

        $image_link = app_url().DS.APP_IMAGE_THUMB.'medium_'.rawurlencode($media_filename);

        return $image_link;
          
        break;
        
      case 'audio/wav':
      case 'audio/ogg':
      case 'audio/mpeg':
        
        $audio_link = app_url().DS.APP_AUDIO.rawurlencode(basename($media_filename));

        return $audio_link;

        break;
      
      case 'video/mp4':
      case 'video/webm':
      case 'video/ogg':
        
        $video_link = app_url().DS.APP_VIDEO.rawurlencode(basename($media_filename));

        return $video_link;

        break;
      
      default:
          
        $media_link = "#";

        return $media_link;

        break;

  }
  
}