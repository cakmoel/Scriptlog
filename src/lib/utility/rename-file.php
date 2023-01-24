<?php
/**
 * rename_file
 * 
 * @param mixed $filename
 * @return mixed
 * 
 */
function rename_file($filename)
{
  $filename = preg_replace('/\s+/', '_', basename($filename));
  return filter_filename($filename);
}

/**
 * filter_filename
 *
 * @category Function
 * @see https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
 * @param string $filename
 * @param boolean $beautify
 * @return void
 * 
 */
function filter_filename($filename, $beautify = true)
{

// sanitize filename
 # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
 # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
 # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
 # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt

$filename = preg_replace('~[<>:"/\\|?*]|[\x00-\x1F]|[\x7F\xA0\xAD]|[#\[\]@!$&\'()+,;=]|[{}^\~`] ~x', '-', $filename);
// avoids ".", ".." or ".hiddenFiles"
$filename = ltrim($filename, '.-');
// optional beautification
if ($beautify) {
  $filename = beautify_filename($filename);
}

// maximize filename length to 255 bytes http://serverfault.com/a/9548/44086

$ext = pathinfo($filename, PATHINFO_EXTENSION);

$filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');

return $filename;

}

/**
 * beautify_filename
 *
 * @see https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
 * @category Function
 * @return void
 * 
 */
function beautify_filename($filename)
{

  $filename = preg_replace(array(
    // "file   name.zip" becomes "file-name.zip"
    '/ +/',
    // "file___name.zip" becomes "file-name.zip"
    '/_+/',
    // "file---name.zip" becomes "file-name.zip"
    '/-+/'
), '-', $filename);

$filename = preg_replace(array(
    // "file--.--.-.--name.zip" becomes "file.name.zip"
    '/-*\.-*/',
    // "file...name..zip" becomes "file.name.zip"
    '/\.{2,}/'
), '.', $filename);
// lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
$filename = mb_strtolower($filename, mb_detect_encoding($filename));
// ".file-name.-" becomes "file-name"
$filename = trim($filename, '.-');

return $filename;

}