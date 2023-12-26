<?php
/**
 * limit_word
 *
 * @category function
 * @see https://stackoverflow.com/questions/965235/how-can-i-truncate-a-string-to-the-first-20-words-in-php
 * @param string $text
 * @param  $limit
 * @return string
 * 
 */
function limit_word($args, $limit)
{

  $text = function_exists('is_valid_utf8') ? is_valid_utf8($args) : "";

  if (str_word_count($text, 0) > $limit) {
    $words = str_word_count($text, 2);
    $pos   = array_keys($words);
    $text  = substr($text, 0, $pos[$limit]).'...';
  }

  return $text;
}

/**
 * str_word_count_utf8
 *
 * @param string $str
 * @see https://www.php.net/manual/en/function.str-word-count.php
 * @return void
 */
function str_word_count_utf8($str)
{
    return count(preg_split('~[^\p{L}\p{N}\']+~u',$str));
}