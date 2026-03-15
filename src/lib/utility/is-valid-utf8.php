<?php
/**
 * is_valid_utf8
 *
 * @category function
 * @author Nirmala Khanza <nirmala.adiba.khanza@gmail.com>
 * @see https://stackoverflow.com/questions/68690422/php-fastest-way-to-check-if-string-is-utf-8
 * @see https://stackoverflow.com/questions/6723562/how-can-i-detect-a-malformed-utf-8-string-in-php
 * @see https://stackoverflow.com/questions/7979567/php-convert-any-string-to-utf-8-without-knowing-the-original-character-set-or?noredirect=1&lq=1
 * @param string $args
 * @return string|false
 * 
 */
function is_valid_utf8($args)
{

    if (preg_match('/\A(?:
        [\x00-\x7F]++                      # ASCII
      | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
      |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
      | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
      |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
      |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
      | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
      |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
      )*+\z/x', $args)) {
    
    return $args;

  } elseif (mb_check_encoding($args, 'UTF-8')) {

    return $args;

  } else {
    return @iconv(mb_detect_encoding($args, mb_detect_order(), true), "UTF-8", $args);
  }

}