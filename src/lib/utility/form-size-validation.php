<?php
/**
 * Form size value validation function
 * 
 * @category  Function
 * @package   SCRIPTLOG/LIB/UTILITY
 * @param string $form_fields
 */
function form_size_validation($form_fields)
{
    
    foreach ($form_fields as $k => $v) {
        
        if(!empty($_POST[$k]) && isset($_POST[$k]{$v + 1})) {
            
            triger_error("{$k} </b> is longer then allowed {$v} byte length");
            
        }
        
    }
}