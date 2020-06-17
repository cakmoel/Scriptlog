<?php
/**
 * Random Generator Function
 * A funtion for generating random strings and numbers
 * 
 * @category function Random generator 
 * @param number $digits
 * @return string|number
 * 
 */
function random_generator($digits)
{
    srand((double) microtime() * 10000000);
    //Array of alphabets
    $input = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", 
                   "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", 
                   "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
    
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

/**
 * ircmaxel generator string function
 * A function for generating random string of various strength
 * 
 * @see https://github.com/ircmaxell/RandomLib
 * @param string $strength
 * @param integer $length
 * @param string $character_list
 * @return void
 * 
 */
function ircmaxell_generator_string($strength, $length = 32, $character_list = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+/')
{

  $factory = new RandomLib\Factory;

  switch ($strength) {

    case 'low':

        $generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::LOW));

        return $generator->generateString($length, $character_list)."\n";

    break;

    case 'high':

        $generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::HIGH));

        return $generator->generateString($length, $character_list)."\n";

    break;

    case 'medium':

        $generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

        return $generator->generateString($length, $character_list)."\n";

    break;
    
  }

}

/**
 * ircmaxell generator numbers function
 * A function for generating random numbers 
 * 
 * @param number|integer $min
 * @param number|integer $max
 * @return void
 */
function ircmaxell_generator_numbers($min, $max)
{

$factory = new RandomLib\Factory;

$generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

$random_int = $generator->generateInt($min, $max);

return $random_int;

}

/**
 * irc_random_generantor function
 *
 * @param int $length
 * @see https://github.com/ircmaxell/RandomLib
 * @return void
 * 
 */
function ircmaxell_random_generator($length)
{

$factory = new RandomLib\Factory;

$generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

$bytes = $generator->generate($length);

return $bytes;

}

/**
 * ircmaxell_random_compat
 *
 * https://github.com/ircmaxell/random_compat
 * @return string
 * 
 */
function ircmaxell_random_compat($length = 16)
{

  $random_compat = new Random();

  return bin2hex($random_compat->bytes($length));

}