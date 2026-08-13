<?php

/**
 * validate_date()
 *
 * date & time validator for all format
 *
 * @see https://www.php.net/manual/en/function.checkdate.php#113205
 * @see https://www.codexworld.com/how-to/validate-date-input-string-in-php/
 * @see https://stackoverflow.com/questions/19271381/correctly-determine-if-date-string-is-a-valid-date-in-that-format
 * @param string $date
 * @param string $format
 * @return bool
 *
 */
function validate_date($date, $format = 'Y-m-d')
{

    // Accept the requested format first, then fall back to the full
    // datetime format so scheduled-posting date/time input validates too.
    $formats = array_unique([$format, 'Y-m-d H:i:s']);

    foreach ($formats as $candidate) {
        $d = DateTime::createFromFormat($candidate, $date);

        if ($d && $d->format($candidate) === $date) {
            return true;
        }
    }

    return false;
}
