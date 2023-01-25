<?php

/**
 * random_generator()
 * 
 * A funtion for generating random strings and numbers
 * 
 * @category function
 * @param int $digits
 * @return string
 * 
 */
function random_generator($digits)
{
    srand(make_seed());
    //Array of alphabets
    $input = array(
        "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j",
        "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"
    );

    $randomGenerator = ""; // Initialize the string to store random numbers
    for ($i = 1; $i < $digits + 1; $i++) { // Loop the number of times of required digits
        if (rand(1, 2) == 1) { // to decide the digit should be numeric or alphabet
            // Add one random alphabet
            $rand_index = array_rand($input);
            $randomGenerator .= $input[$rand_index]; // One char is added
        } else {

            // Add one numeric digit between 1 and 10
            $randomGenerator .= rand(1, 10); // one number is added
        } // end of if else
    } // end of for loop

    return $randomGenerator;
}

/**
 * make_seed
 * 
 * @category function
 * @see http://url.comhttps://www.php.net/manual/en/function.srand
 * 
 */
function make_seed()
{
    list($usec, $sec) = explode(' ', microtime());
    return $sec + $usec * 1000000;
}

/**
 * ircmaxel_generator_string()
 * 
 * A function for generating random string of various strength
 * 
 * @category function
 * @see https://github.com/ircmaxell/RandomLib
 * @param string $strength
 * @param integer $length
 * @param string $character_list
 * @return string
 * 
 */
function ircmaxell_generator_string($strength, $length = 32, $character_list = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+/')
{

    $factory = new RandomLib\Factory;

    switch ($strength) {

        case 'low':

            $generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::LOW));

            return $generator->generateString($length, $character_list) . "\n";

            break;

        case 'high':

            $generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::HIGH));

            return $generator->generateString($length, $character_list) . "\n";

            break;

        case 'medium':

            $generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

            return $generator->generateString($length, $character_list) . "\n";

            break;
    }
}

/**
 * ircmaxell_generator_numbers()
 * 
 * A function for generating random numbers 
 * 
 * @category function
 * @param number|integer $min
 * @param number|integer $max
 * @return number
 */
function ircmaxell_generator_numbers($min, $max)
{

    $factory = new RandomLib\Factory;

    $generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

    return $generator->generateInt($min, $max);
}

/**
 * ircmaxell_random_generator()
 * 
 * generating simple random bytes string
 * 
 * @category function
 * @param int $length
 * @see https://github.com/ircmaxell/RandomLib
 * @return string
 * 
 */
function ircmaxell_random_generator($length)
{

    $factory = new RandomLib\Factory;

    $generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

    return $generator->generate($length);
}

/**
 * ircmaxell_random_compat()
 *
 * @category function
 * https://github.com/ircmaxell/random_compat
 * @return string
 * 
 */
function ircmaxell_random_compat($length = 64)
{

    $random_compat = new Random();

    return bin2hex($random_compat->bytes($length));
}

/**
 * random_password function
 * generates random characters that can be used as insecure password
 * never use this function for storing your password
 * 
 * @see https://thisinterestsme.com/php-random-password/
 * @param integer $length
 * @return string
 * 
 */
function random_password($length)
{

    //A list of characters that can be used in our random password.
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!-.[]?*()';
    //Create a blank string.
    $password = '';
    //Get the index of the last character in our $characters string.
    $characterListLength = mb_strlen($characters, '8bit') - 1;
    //Loop from 1 to the $length that was specified.
    foreach (range(1, $length) as $i) {
        $password .= $characters[random_int(0, $characterListLength)];
    }
    return $password;
}
