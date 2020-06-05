<?php
/**
 * Sanitize Email Header Function
 * 
 * @author Kevin Waterson
 * @see https://phpro.org/tutorials/PHP-Security.html
 * @param string $email
 * @return mixed
 * 
 */
function sanitize_email($email)
{
    return preg_replace('((?:\n|\r|\t|%0A|%0D|%08|%09)+)i', '', $email);
}