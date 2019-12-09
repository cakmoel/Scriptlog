<?php
/**
 * Invoke fileicon
 * retrieve file icon (font awesome icon) and check it media type
 * 
 * @category  Function
 * @return string
 * 
 */
function invoke_fileicon($media_type)
{
  
 switch ($media_type) {

    case "application/pdf":
     
       $pdf_icon = '<i class="fa fa-file-pdf-o fa-3x"></i>';
  
       return $pdf_icon;

       break;

    case "application/vnd.ms-excel":
    case "application/vnd.oasis.opendocument.spreadsheet":  
    
       $msexcel_icon = '<i class="fa fa-file-excel-o fa-3x"></i>'; 
    
       return $msexcel_icon;

       break;

    case "application/msword":
    case "application/vnd.oasis.opendocument.text":  

        $msword_icon = '<i class="fa fa-file-word-o fa-3x"></i>';

        return $msword_icon;

        break;

    case "application/vnd.ms-powerpoint":

        $mspwrpoint_icon = '<i class="fa fa-file-powerpoint-o fa-3x"></i>';

        return $mspwrpoint_icon;

        break;

    case "application/zip":
    case "application/x-rar-compressed":
        
        $archive_icon = '<i class="fa fa-file-zip-o fa-3x"></i>';

        return $archive_icon;

        break;
     
    case "audio/mp3":
    case "audio/wav":
    case "audio/ogg":
      
        $audio_icon = '<i class="fa fa-file-audio-o fa-3x"></i>';

        return $audio_icon;

        break;

    case "video/mp4":
    case "video/webm":
    case "video/ogg":  
      
        $video_icon = '<i class="fa fa-file-video-o fa-3x"></i>';

        return $video_icon;

        break;

    case "image/jpeg":
    case "image/png":
    case "image/gif":
    case "image/webp":
        
        $image_icon = '<i class="fa fa-file-image-o fa-3x"></i>';

        return $image_icon;

        break;

   default:
     
      $file_icon = '<i class="fa fa-file-o fa-3x"></i>';
 
      return $file_icon;

     break;

 }
 
}