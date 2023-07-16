<?php
/**
 * Invoke fileicon
 * retrieve file icon (font awesome icon) and check it media type
 * 
 * @category  Function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 * 
 */
function invoke_fileicon($media_type)
{
  
 switch ($media_type) {

    case "application/pdf":
     
       return '<i class="fa fa-file-pdf-o fa-3x" aria-hidden="true"></i>';
  
       break;

    case "application/vnd.ms-excel":
    case "application/vnd.oasis.opendocument.spreadsheet":  
    
       return '<i class="fa fa-file-excel-o fa-3x" aria-hidden="true"></i>'; 
    
       break;

    case "application/msword":
    case "application/vnd.oasis.opendocument.text":  

        return '<i class="fa fa-file-word-o fa-3x" aria-hidden="true"></i>';

        break;

    case "application/vnd.ms-powerpoint":

        return '<i class="fa fa-file-powerpoint-o fa-3x" aria-hidden="true"></i>';

        break;

    case "application/zip":
    case "application/x-rar-compressed":
        
        return '<i class="fa fa-file-zip-o fa-3x" aria-hidden="true"></i>';

        break;
     
    case "audio/mpeg":
    case "audio/wav":
    case "audio/ogg":
      
        return '<i class="fa fa-file-audio-o fa-3x" aria-hidden="true"></i>';

        break;

    case "video/mp4":
    case "video/webm":
    case "video/ogg":  
      
        return '<i class="fa fa-file-video-o fa-3x" aria-hidden="true"></i>';

        break;
    
    case "image/jpg":
    case "image/jpeg":
    case "image/png":
    case "image/gif":
    case "image/webp":
        
        return '<i class="fa fa-file-image-o fa-3x" aria-hidden="true"></i>';

        break;

   default:
     
      return '<i class="fa fa-file-o fa-3x" aria-hidden="true"></i>';
 
     break;

 }
 
}