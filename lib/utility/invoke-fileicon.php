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

    if (
        in_array($media_type, [
        "application/pdf",
        "application/vnd.ms-excel",
        "application/vnd.oasis.opendocument.spreadsheet",
        "application/msword",
        "application/vnd.oasis.opendocument.text",
        "application/vnd.ms-powerpoint",
        "application/zip",
        "application/x-rar-compressed",
        ], true)
    ) {
        $docIcons = [
            "application/pdf" => 'file-pdf-o',
            "application/vnd.ms-excel" => 'file-excel-o',
            "application/vnd.oasis.opendocument.spreadsheet" => 'file-excel-o',
            "application/msword" => 'file-word-o',
            "application/vnd.oasis.opendocument.text" => 'file-word-o',
            "application/vnd.ms-powerpoint" => 'file-powerpoint-o',
            "application/zip" => 'file-zip-o',
            "application/x-rar-compressed" => 'file-zip-o',
        ];
        return '<i class="fa fa-' . $docIcons[$media_type] . ' fa-3x" aria-hidden="true"></i>';
    }

    $group = get_mime_group($media_type);

    switch ($group) {
        case 'audio':
            return '<i class="fa fa-file-audio-o fa-3x" aria-hidden="true"></i>';

        case 'video':
            return '<i class="fa fa-file-video-o fa-3x" aria-hidden="true"></i>';

        case 'image':
            return '<i class="fa fa-file-image-o fa-3x" aria-hidden="true"></i>';

        default:
            return '<i class="fa fa-file-o fa-3x" aria-hidden="true"></i>';
    }
}
