<?php
/**
 * Make Date Function
 * 
 * @category Function
 * @param string $value
 * @param null $locale
 * @return string
 */
function make_date($value, $locale = null)
{
    $day = substr($value, 8, 2 );
    $month = generate_month(substr( $value, 5, 2 ), $locale);
    $year = substr($value, 0, 4 );
    
    if ($locale == 'id') {
        
        return $day . ' ' . $month . ' ' . $year;
        
    } else {
        
        return $month . ' ' . $day . ', ' . $year;
        
    }
    
}

/**
 * Generate Mounth Function
 * 
 * @param string $value
 * @param null $locale
 * @return string
 */
function generate_month($value, $locale = null)
{
    
    switch ($value) {
        
        case 1 :
            
            return (!is_null($locale) && $locale == 'id') ? "Januari" : "January";
              
            break;
            
        case 2 :
            
            return (!is_null($locale) && $locale == 'id') ? "Februari" : "February";
               
            break;
            
        case 3 :
            
            return (!is_null($locale) && $locale == 'id') ? "Maret" : "March";
             
            break;
            
        case 4 :
            
            return (!is_null($locale) && $locale == 'id') ? "April" : "April";
            
            break;
            
        case 5 :
            
            return (!is_null($locale) && $locale == 'id') ? "Mei" : "May";
            
            break;
            
        case 6 :
            
            return (!is_null($locale) && $locale == 'id') ? "Juni" : "June";
             
            break;
        
        case 7 :
        
            return (!is_null($locale) && $locale == 'id') ? "Juli" : "July";
            
            break;
            
        case 8 :
            
            return (!is_null($locale) && $locale == 'id') ? "Agustus" : "August";
            
            break;
            
        case 9 :
            
            return (!is_null($locale) && $locale == 'id') ? "September" : "September";
            
            break;
            
        case 10 :
            
            return (!is_null($locale) && $locale == 'id') ? "Oktober" : "October";
                        
            break;
            
        case 11 :
            
            return (!is_null($locale) && $locale == 'id') ? "November" : "November";
            
            break;
            
        case 12 :
            
            return (!is_null($locale) && $locale == 'id') ? "Desember" : "December";
            
            break;
            
    }
    
}