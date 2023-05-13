<?php
/**
 * Generate Hash Function
 * 
 * @category Function
 * @param string $quantityChar
 * @return NULL|string
 */
function generate_hash($quantityChar)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $qtyChar = strlen($characters);
    $qtyChar--;
    
    $hash = null;
    
    for ($i=1; $i<=$quantityChar; $i++) {
        
        $position = rand(0, $qtyChar);
        $hash .= substr($characters, $position, 1);
    }
    
    return $hash;
    
}