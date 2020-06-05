<?php
/**
 * Mime types dictionary function
 *
 * @category function
 * @return mixed
 * 
 */
function mime_type_dictionary()
{

  $mime_types = [

      'pdf'  => 'application/pdf', 
      'doc'  => 'application/msword', 
      'rar'  => 'application/rar', 
      'zip'  => 'application/zip', 
      'xls'  => 'application/vnd.ms-excel', 
      'xls'  => 'application/octet-stream', 
      'exe'  => 'application/vnd.microsoft.portable-executable', 
      'ppt'  => 'application/vnd.ms-powerpoint',
      'odt'  => 'application/vnd.oasis.opendocument.text',
      'jpeg' => 'image/jpeg', 
      'jpg'  => 'image/jpeg', 
      'png'  => 'image/png', 
      'gif'  => 'image/gif', 
      'webp' => 'image/webp',
      'mp3'  => 'audio/mpeg', 
      'wav'  => 'audio/wav',
      'ogg'  => 'audio/ogg',
      'mp4'  => 'video/mp4',
      'webm' => 'video/webm',
      'ogg'  => 'video/ogg'

  ];

  return $mime_types;

}