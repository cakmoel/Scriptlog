<?php
/**
 * validator_email_instance
 * 
 * @category function
 * @author M.Noermoehammad
 * @author Eduardo Gulias Davis author of EmailValidator Package
 * @license MIT
 * @version 1.0
 * @param string $email
 * @see https://github.com/egulias/EmailValidator
 * @see https://www.cloudways.com/blog/php-libraries/#email-validator
 * @uses \Egulias\EmailValidator\EmailValidator
 * @return object
 * 
 */

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

/**
 * validator_email_instance()
 *
 * @return object
 * 
 */
function validator_email_instance()
{
  
  $validator_email = new EmailValidator();
  
  return $validator_email;

}

/**
 * email_validation
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $email
 * @param string $validation_type
 * @return object
 * 
 */
function email_validation($email, $validation_type)
{
  return validator_email_instance()->isValid($email, $validation_type);
}

/**
 * email_multiple_validation
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $email
 * @param array $validation_type
 * @uses \Egulias\EmailValidator\Validation\MultipleValidationWithAnd
 * @uses \Egulias\EmailValidator\Validation\DNSCheckValidation
 * @uses \Egulias\EmailValidator\Validation\RFCValidation
 * @return bool
 * 
 */
function email_multiple_validation($email)
{
  $multiple_validations = new MultipleValidationWithAnd([new RFCValidation(), new DNSCheckValidation()]);

  return validator_email_instance()->isValid($email, $multiple_validations);
}