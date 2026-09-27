<?php

/**
 * Format size unit function
 *
 * @category  Function
 * @param null|bool|int|float|string $bytes
 * @return string
 *
 */
function format_size_unit($bytes)
{

    if ($bytes >= 1_073_741_824) {
        $bytes = number_format($bytes / 1_073_741_824, 2) . ' GB';
    } elseif ($bytes >= 1_048_576) {
        $bytes = number_format($bytes / 1_048_576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}
