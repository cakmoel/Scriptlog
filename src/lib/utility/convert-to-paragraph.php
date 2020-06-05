<?php
/**
 * Convert to Paragraph function
 * To display text retrieved from database as genuine paragraphs
 * 
 * @param [type] $text
 * @return void
 */
function convert_to_paragraph($text)
{

 $text = trim($text);
 $text = htmlspecialchars($text, ENT_COMPAT|ENT_HTML5, 'UTF-8', false);
 return '<p>' . preg_replace('/[\r\n]+/', "</p>\n<p>", $text) . "</p>\n";

}