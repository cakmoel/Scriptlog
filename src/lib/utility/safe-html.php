<?php
/**
 * safe_html
 * 
 * Escaping output text results from database
 * 
 * @category function
 * @param string $text
 * @return string
 * 
 */
function safe_html($data)
{   
 return htmlspecialchars(stripslashes(trim(strip_tags($data))), ENT_QUOTES|ENT_HTML5, 'UTF-8', false);
}

/**
 *  safe_filter_html
 *
 * @category function
 * @param string $data
 * 
 */
function safe_filter_html($data)
{
 $html_filter = new Html();
 return $html_filter->filter($data); 
}