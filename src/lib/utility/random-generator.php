<?php
/**
 * Random Generator Function
 * to create random string
 * 
 * @param number $digits
 * @return string|number
 */
function random_generator($digits)
{
    srand((double) microtime() * 10000000);
    //Array of alphabets
    $input = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q",
        "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
    
    $randomGenerator = ""; // Initialize the string to store random numbers
    for ($i = 1; $i < $digits + 1; $i++) { // Loop the number of times of required digits
        if (rand(1, 2) == 1) {// to decide the digit should be numeric or alphabet
            // Add one random alphabet
            $rand_index = array_rand($input);
            $randomGenerator .=$input[$rand_index]; // One char is added
        } else {
            
            // Add one numeric digit between 1 and 10
            $randomGenerator .=rand(1, 10); // one number is added
        } // end of if else
    } // end of for loop
    
    return $randomGenerator;
}