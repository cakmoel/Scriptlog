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
            
            $month = (!is_null($locale) && $locale == 'id') ? "Januari" : "January";
            
            return $month;
            
            break;
            
        case 2 :
            
            $month = (!is_null($locale) && $locale == 'id') ? "Februari" : "February";
            
            return $month;
            
            break;
            
        case 3 :
            
            $month = (!is_null($locale) && $locale == 'id') ? "Maret" : "March";
            
            return $month;
            
            break;
            
        case 4 :
            
            $month = (!is_null($locale) && $locale == 'id') ? "April" : "April";
            
            return $month;
            
            break;
            
        case 5 :
            
            $month = (!is_null($locale) && $locale == 'id') ? "Mei" : "May";
            
            return $month;
            
            break;
            
        case 6 :
            
            $month = (!is_null($locale) && $locale == 'id') ? "Juni" : "June";
            
            return $month;
            
            break;
        
        case 7 :
        
            $month = (!is_null($locale) && $locale == 'id') ? "Juli" : "July";
            
            return $month;
            
            break;
            
        case 8 :
            
            $month = (!is_null($locale) && $locale == 'id') ? "Agustus" : "August";
            
            return $month;
            
            break;
            
        case 9 :
            
            $month = (!is_null($locale) && $locale == 'id') ? "September" : "September";
            
            return $month;
            
            break;
            
        case 10 :
            
            $month = (!is_null($locale) && $locale == 'id') ? "Oktober" : "October";
            
            return $month;
                        
            break;
            
        case 11 :
            
            $month = (!is_null($locale) && $locale == 'id') ? "November" : "November";
            
            return $month;
           
            break;
            
        case 12 :
            
            $month = (!is_null($locale) && $locale == 'id') ? "Desember" : "December";
            
            return $month;
            
            break;
            
    }
    
}