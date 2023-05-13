<?php
/**
 * Form size value validation function
 * 
 * @category  Function
 * @param array $form_fields
 * 
 */
function form_size_validation(array $form_fields)
{
  $exceded_limit = false;

    foreach ($form_fields as $k => $v) {
        
        if(!empty($_POST[$k]) && isset($_POST[$k][$v + 1])) {
            
            $exceded_limit = true;
            
        }
        
    }
 
    return $exceded_limit;
    
}