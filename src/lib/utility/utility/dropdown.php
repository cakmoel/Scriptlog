<?php
/**
 * dropdown()
 * 
 * @category function
 * @see   https://www.phpro.org/tutorials/Dropdown-Select-With-PHP-and-MySQL.html
 * @param  string $name
 * @param  array  $options
 * @param  string $selected
 * @return string
 * 
 */
function dropdown($name, array $options, $selected=null)
{
    
    $dropdown = '<select class="form-control select2" name="'.$name.'" id="'.$name.'">'. PHP_EOL;

    $item = $selected;

    /*** loop over the options ***/
    foreach ($options as $key => $option) {
      
          /*** assign a selected value ***/
          $select = $item === $key ? '  selected' : null;
        
          /*** add each option to the dropdown ***/
          $dropdown .= '<option value="'.$key.'"'.$select.'>'.$option.'</option>'. PHP_EOL;
        
    }

    /*** close the select ***/
    $dropdown .= '</select>'. PHP_EOL;
    
    /*** and return the completed dropdown ***/
    return $dropdown;
    
}