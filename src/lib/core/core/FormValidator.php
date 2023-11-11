<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class FormValidator
 * 
 * @category Core Class
 * @author  SchizoDuckie
 * @see https://stackoverflow.com/questions/737385/easiest-form-validation-library-for-php#738510
 * @copyright SchizoDuckie 2009
 * @version 1.0
 * 
 */
class FormValidator
{

public static $regexes = array(
            'date' => "^[0-9]{4}[-/][0-9]{1,2}[-/][0-9]{1,2}\$",
            'amount' => "^[-]?[0-9]+\$",
            'number' => "^[-]?[0-9,]+\$",
            'alfanum' => "^[0-9a-zA-Z ,.-_\\s\?\!]+\$",
            'not_empty' => "[a-z0-9A-Z]+",
            'words' => "^[A-Za-z]+[A-Za-z \\s]*\$",
            'phone' => "^[0-9]{10,11}\$",
            'zipcode' => "^[1-9][0-9]{3}[a-zA-Z]{2}\$",
            'plate' => "^([0-9a-zA-Z]{2}[-]){2}[0-9a-zA-Z]{2}\$",
            'price' => "^[0-9.,]*(([.,][-])|([.,][0-9]{2}))?\$",
            '2digitopt' => "^\d+(\,\d{2})?\$",
            '2digitforce' => "^\d+\,\d\d\$",
            'anything' => "^[\d\D]{1,}\$",
            'username' => "^[\w]{3,32}\$"
);

private $validations; 

private $sanitations; 

private $mandatories; 

private $equal; 

private $errors; 

private $corrects; 

public function __construct($validations=array(), $mandatories = array(), $sanitations = array(), $equal=array())
{
    $this->validations = $validations;
    $this->sanitations = $sanitations;
    $this->mandatories = $mandatories;
    $this->equal = $equal;
    $this->errors = array();
    $this->corrects = array();
}

/**
 * Validates an array of items (if needed) and returns true or false
 *
 * JP modofied this function so that it checks fields even if they are not submitted.
 * for example the original code did not check for a mandatory field if it was not submitted.
 * Also the types of non mandatory fields were not checked.
 */
public function validate($items)
{
    $this->fields = $items;
    $havefailures = false;

    //Check for mandatories
    foreach ($this->mandatories as $key=>$val) {
        
        if (!array_key_exists($val,$items)) {
            $havefailures = true;
            $this->addError($val);
        }
    }

    //Check for equal fields
    foreach ($this->equal as $key=>$val) {

        //check that the equals field exists
        if (!array_key_exists($key,$items)) {
            $havefailures = true;
            $this->addError($val);
        }

        //check that the field it's supposed to equal exists
        if (!array_key_exists($val,$items)) {
            $havefailures = true;
            $this->addError($val);
        }

        //Check that the two fields are equal
        if ($items[$key] != $items[$val]) {
            $havefailures = true;
            $this->addError($key);
        }
    }

    foreach ($this->validations as $key=>$val) {
            //An empty value or one that is not in the list of validations or one that is not in our list of mandatories
            if (!array_key_exists($key,$items)) {
                $this->addError($key, $val);
                continue;
            }

            $result = self::validateItem($items[$key], $val);

            if ($result === false) {
                $havefailures = true;
                $this->addError($key, $val);
            } else {
                $this->corrects[] = $key;
            }
    }

    return(!$havefailures);
}

/* JP
 * Returns a JSON encoded array containing the names of fields with errors and those without.
 */
public function getJSON() {

    $errors = array();

    $correct = array();

    if (!empty($this->errors)) {            
        foreach ($this->errors as $key=>$val) { $errors[$key] = $val; }            
    }

    if (!empty($this->corrects)) {
        foreach ($this->corrects as $key=>$val) { $correct[$key] = $val; }                
    }

    $output = array('errors' => $errors, 'correct' => $correct);

    return json_encode($output);
}



/**
 *
 * Sanatizes an array of items according to the $this->sanitations
 * sanitations will be standard of type string, but can also be specified.
 * For ease of use, this syntax is accepted:
 * $sanitations = array('fieldname', 'otherfieldname'=>'float');
 */
public function sanitize($items)
{

  foreach ((array) $items as $key => $val) {

    if ((array_search($key, $this->sanitations) === false) && (!array_key_exists($key, $this->sanitations))) {

        continue;
                        
    }

    $items[$key] = self::sanitizeItem($val, $this->validations[$key]);
                   
  }
             
 return($items);

}

/**
 *
 * Adds an error to the errors array.
 */ 
private function addError($field, $type='string')
{
    $this->errors[$field] = $type;
}

/**
 *
 * Sanatize a single var according to $type.
 * Allows for static calling to allow simple sanatization
 */
private static function sanitizeItem($var, $type)
{
    $flags = null;
    switch ($type) {

            case 'url':
                    $filter = FILTER_SANITIZE_URL;
            break;
            case 'int':
                    $filter = FILTER_SANITIZE_NUMBER_INT;
            break;
            case 'float':
                    $filter = FILTER_SANITIZE_NUMBER_FLOAT;
                    $flags = FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND;
            break;
            case 'email':
                    $var = substr($var, 0, 254);
                    $filter = FILTER_SANITIZE_EMAIL;
            break;
            case 'string':
            default:
                    $filter = FILTER_SANITIZE_SPECIAL_CHARS;
                    $flags = FILTER_FLAG_NO_ENCODE_QUOTES;
            break;

    }
    
    $output = filter_var($var, $filter, $flags); 

    return($output);

}

/** 
 *
 * Validates a single var according to $type.
 * Allows for static calling to allow simple validation.
 *
 */
public static function validateItem($var, $type)
{
    if (array_key_exists($type, self::$regexes)) {

        $returnval =  filter_var($var, FILTER_VALIDATE_REGEXP, array("options"=> array("regexp"=>'!'.self::$regexes[$type].'!i'))) !== false;
        
        return($returnval);

    }

    $filter = false;
    switch ($type) {

            default:
            case 'email':
                    $var = substr($var, 0, 254);
                    $filter = FILTER_VALIDATE_EMAIL;        
            break;
            case 'int':
                    $filter = FILTER_VALIDATE_INT;
            break;
            case 'boolean':
                    $filter = FILTER_VALIDATE_BOOLEAN;
            break;
            case 'ip':
                    $filter = FILTER_VALIDATE_IP;
            break;
            case 'url':
                    $filter = FILTER_VALIDATE_URL;
            break;

    }

    return ($filter === false ? false : filter_var($var, $filter) !== false) ? true : false;

}           

}