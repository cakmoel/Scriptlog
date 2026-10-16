<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * upload_media
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $file_location
 * @param string $file_type
 * @param string $file_size
 * @param string $file_name
 *
 */
function upload_media($file_location, $file_type, $file_size, $file_name)
{
    $group = get_mime_group($file_type);

    switch ($group) {
        case 'doc':
            upload_doc($file_location, $file_name);
            break;

        case 'audio':
            upload_audio($file_location, $file_name);
            break;

        case 'image':
            upload_photo($file_location, $file_size, $file_type, $file_name);
            break;

        case 'video':
            upload_video($file_location, $file_name);
            break;

        default:
            scriptlog_error("Error - file type not allowed!", E_USER_WARNING);
            break;
    }
}
