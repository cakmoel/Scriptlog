<?php
/**
 * front_sanitizer()
 *
 * @category function
 * @author Nirmalakhanza
 * @return object|bool
 * 
 */
function front_sanitizer()
{
 return (class_exists('Sanitize')) ? new Sanitize() : "";
}