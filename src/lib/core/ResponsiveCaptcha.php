<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Generate random text-based CAPTCHAs with simple arithmetic and logic questions
 * Website: https://github.com/theodorejb/responsive-Captcha
 * Updated: 2013-08-01
 *
 * @author Theodore Brown
 * @version 1.0.1
 * 
 */

class ResponsiveCaptcha 
{

 private $sessionVariableName = "ResponsiveCaptchaAnswer";

 public function __construct() 
 {

	if (session_id() == '') {

		// no session has been started; try starting it

		if (!session_start()) {

			throw new Exception("Unable to start session");

		}	else {

			session_regenerate_id();

		}

	}

 }

/**
 * Checks whether a user's response matches the stored answer
 *
 * @param string $answer The user's submitted response
 * @return boolean TRUE if the answer is correct
 * @throws Exception
 */

 public function checkAnswer($answer) 
 {

   // convert the answer to lower case and trim any whitespace
   $answer = strtolower(trim($answer));

	// ensure that the session answer variable is set

	if (!isset($_SESSION[$this->sessionVariableName])) {

		throw new Exception("The captcha answer session variable is not set");

	} else {

	   $storedAnswer = $_SESSION[$this->sessionVariableName];

		unset($_SESSION[$this->sessionVariableName]);

		// both numeric and textual answers are acceptable

		if ($answer == $storedAnswer || $answer === $this->getWordFromNumber($storedAnswer)) {

			return true;

		} else {

			throw new Exception("Incorrect captcha response");

		}

		return false;

	}

 }

/**
 * Generate a random question string and store the answer in the session
 * 
 * Important: call this method AFTER checking the user's response since it will replace the session answer variable
 * 
 */

public function getNewQuestion() 
{

	$function = rand(0, 2);

	if ($function == 0) {

		return $this->getLetterProblem();

	} elseif ($function == 1) {

		return $this->getNumberProblem();

	} else {
				
		// get a random arithmetic question
		// get a random number between 1 and 4 to determine whether to add, subtract, multiply, or divide

		$function = rand(1, 4);

				if ($function == 1)

					return $this->getAdditionProblem(); // add

					elseif ($function == 2)

					return $this->getSubtractionProblem(); // subtract

					elseif ($function == 3)

					return $this->getMultiplicationProblem(); // multiply

					else

						return $this->getDivisionProblem(); // divide

			}

	}



	/**

	* Returns a random addition problem after adding the answer to the session

	* Example: "What is the sum of five and six?"

	*

	* @return string A random addition problem

	*/

	private function getAdditionProblem() {

		$num1 = rand(0, 10);

		$num2 = rand(0, 10);



		$this->storeAnswer($num1 + $num2);



		$num1Name = $this->getWordFromNumber($num1);

		$num2Name = $this->getWordFromNumber($num2);



		if (rand(0, 1))

			return "What is the sum of $num1Name and $num2Name?";

			else

				return "What is $num1Name plus $num2Name?";

	}



	/**

	* Returns a random subtraction problem

	* Example: "What is eight minus four?"

	*

	* @return string A random subtraction problem

	*/

	private function getSubtractionProblem() {

		// the smaller (or equal) number should be subtracted from the larger number

		$numbers[] = rand(0, 10);

		$numbers[] = rand(0, 10);

		sort($numbers, SORT_NUMERIC); // the first array element is smaller (or equal)



		$smallerNumber = $numbers[0];

		$largerNumber = $numbers[1];



		$smallerNumberName = $this->getWordFromNumber($smallerNumber);

		$largerNumberName = $this->getWordFromNumber($largerNumber);

		$this->storeAnswer($largerNumber - $smallerNumber);



		return "What is $largerNumberName minus $smallerNumberName?";

	}



	/**

	* Returns a random multiplication problem

	* Example: "What is two multiplied by seven?"

	*

	* @return string A random multiplication problem

	*/

	private function getMultiplicationProblem() {

		$num1 = rand(0, 10);

		$num2 = rand(0, 10);



		$this->storeAnswer($num1 * $num2);



		$num1Name = $this->getWordFromNumber($num1);

		$num2Name = $this->getWordFromNumber($num2);



		if (rand(0, 1))

			return "What is $num1Name multiplied by $num2Name?";

			else

				return "What is $num1Name times $num2Name?";

	}



	/**

	* Returns a random division problem

	* Example: "What is twenty divided by two?"

	*

	* @return string A random division question

	*/

	private function getDivisionProblem() {

		$quotient = rand(1, 10); // this will be the answer

		$divisor = rand(1, 5); // keep it simple

		$dividend = $quotient * $divisor;



		$dividendName = $this->getWordFromNumber($dividend);

		$divisorName = $this->getWordFromNumber($divisor);

		$this->storeAnswer($quotient);

		return "What is $dividendName divided by $divisorName?";

	}



	/**

	* Get a random letter position question

	* Example: "What is the fifth letter in Tokyo?"

	*/

	private function getLetterProblem() {

		$words = array(

				"airplane",

				"basketball",

				"butterfly",

				"chocolate",

				"donkey",

				"dumpling",

				"elephant",

				"football",

				"grandfather",

				"helicopter",

				"island",

				"juniper",

				"kitten",

				"laughter",

				"mirror",

				"nation",

				"orange",

				"piano",

				"pencil",

				"quartet",

				"rainbow",

				"racecar",

				"railroad",

				"snowboard",

				"skyscraper",

				"sunshine",

				"starfish",

				"scriptlog",

				"transparent",

				"ultraviolet",

				"velocity",

				"windshield",

				"xylophone",

				"yesterday",

				"yellow",

				"zebra"

		);



		$numberNames = array(

				"first",

				"second",

				"third",

				"fourth",

				"fifth"

		);



		$randomWordPosition = array_rand($words);

		$randomWord = $words[$randomWordPosition];

		$randomWordLength = strlen($randomWord);

		$letterArray = str_split($randomWord);



		// there should be a chance of getting the last letter

		if (rand(1, $randomWordLength) == $randomWordLength) {

			$letterPosName = 'last';

			$randLetter = end($letterArray); // get the last letter in the word

		} else {

			// ask for one of the first five letters (to keep it simple)

			if ($randomWordLength > 5)

				$max = 5;

				else

					$max = $randomWordLength;



					$randLetterPosition = rand(0, $max - 1);

					$randLetter = $letterArray[$randLetterPosition]; // this is the answer

					$letterPosName = $numberNames[$randLetterPosition];

		}



		$this->storeAnswer($randLetter);

		return "What is the $letterPosName letter in $randomWord?";

	}



	/**

	* For a range of three unique numbers, ask which one is largest or smallest

	* Example: "Which is largest: twenty-one, sixteen, or eighty-four?"

	*/

	private function getNumberProblem() {

		$numbers = $this->getUniqueIntegers(3);



		// make a string containing the names of the numbers (e.g. "one, two, or three")

		$numberString = '';

		for ($i = 0; $i < count($numbers); $i++) {

			$numberName = $this->getWordFromNumber($numbers[$i]);

			if ($i == count($numbers) - 1) {

				// the last number

				$numberString .= "or $numberName";

			}

			else

				$numberString .= "$numberName, ";

		}



		if (rand(0, 1)) {

			// ask which is smallest

			sort($numbers); // so the first element contains the smallest number

			$this->storeAnswer($numbers[0]);

			return "Which is smallest: $numberString?";

		} else {

			// ask which is largest

			rsort($numbers); // so the first element contains the largest number

			$this->storeAnswer($numbers[0]);

			return "Which is largest: $numberString?";

		}

	}



	/**

	* Returns the name of any integer less than or equal to 100

	*

	* @param integer $number A number no greater than 100

	* @return string The name of the integer

	*/

	private function getWordFromNumber($number) {

		$numberNames = array(

				0 => "zero",

				1 => "one",

				2 => "two",

				3 => "three",

				4 => "four",

				5 => "five",

				6 => "six",

				7 => "seven",

				8 => "eight",

				9 => "nine",

				10 => "ten",

				11 => "eleven",

				12 => "twelve",

				13 => "thirteen",

				14 => "fourteen",

				15 => "fifteen",

				16 => "sixteen",

				17 => "seventeen",

				18 => "eighteen",

				19 => "nineteen",

				20 => "twenty",

				30 => "thirty",

				40 => "forty",

				50 => "fifty",

				60 => "sixty",

				70 => "seventy",

				80 => "eighty",

				90 => "ninety",

				100 => "one hundred"

		);



		// make sure the number is an integer

		if (is_int($number)) {

			if (($number >= 0 && $number <= 20) || $number == 100)

				return $numberNames[$number];

				elseif ($number < 100) {

					// split the number into an array of digits

					$numArray = array_reverse(str_split($number));

					$onesPlace = $numArray[0];

					$tensPlace = $numArray[1];



					// get the name of the tens place

					$numGroup = (int) $tensPlace . 0;

					$numberName = $numberNames[$numGroup];



					// add the name of the ones place if it isn't zero

					if ($onesPlace != 0)

						$numberName .= '-' . $numberNames[$onesPlace];

						return $numberName;

				} else {

					throw new Exception("Number is out of range!");

				}

		}

		return FALSE;

	}



	/**

	* Store an answer in the session

	*/

	private function storeAnswer($answer) {

		$_SESSION[$this->sessionVariableName] = $answer;

	}



	/**

	* Returns an array of unique integers between 0 and 100

	*

	* @param int $howMany

	*/

	private function getUniqueIntegers($howMany) {

		$min = 0;

		$max = 100;



		// ensure that the requested number of unique integers does not exceed those in the range

		if ($howMany < $max) {

			$numbers = array();

			for ($i = 0; $i < $howMany; $i++) {

				$newNum = rand($min, $max);

				while (in_array($newNum, $numbers)) {

					$newNum = rand($min, $max);

				}

				$numbers[] = $newNum;

			}

			return $numbers;

		}

		else

			throw new Exception("Requested numbers are out of range!");

	}



}
