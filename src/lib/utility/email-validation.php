<?php
/**
 * email_validation
 * 
 * @category function
 * @param string $email
 * @license MIT
 * @see https://github.com/egulias/EmailValidator
 * @see https://www.cloudways.com/blog/php-libraries/#email-validator
 * @return void
 * 
 */

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

function validator_email_instance()
{
  
  $validator_email = new EmailValidator();
  
  return $validator_email;

}

function email_validation($email, $validation_type)
{
  return validator_email_instance()->isValid($email, $validation_type);
}

function email_multiple_validation($email, array $validation_type)
{
  $multiple_validations = new MultipleValidationWithAnd($validation_type);

  return validator_email_instance()->isValid($email, $multiple_validations);
}