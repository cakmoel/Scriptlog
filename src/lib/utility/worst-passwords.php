<?php
/**
 * the worst password function
 * these are the most hacked password on the list
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @see https://en.wikipedia.org/wiki/List_of_the_most_common_passwords#cite_note-keeper2016list-14
 * @see https://www.forbes.com/sites/kateoflahertyuk/2018/12/14/these-are-the-top-20-worst-passwords-of-2018/#2d5af4af4541
 * @see https://www.forbes.com/sites/kateoflahertyuk/2019/04/21/these-are-the-worlds-most-hacked-passwords-is-yours-on-the-list/#793adaf3289c
 * @see https://mozilla.github.io/application-services/docs/accounts/50000-most-common-passwords.html
 * @return array
 * 
 */
function worst_passwords()
{
  
  $most_common_passwords = ['password', '12345678', '123456789', 'baseball', 'football', 'qwertyuiop', '1234567890', 'superman', 
  '1qaz2wsx', 'trustno1', 'jennifer', 'sunshine', 'iloveyou', 'starwars', 'computer', 'michelle', '11111111', 'princess', '987654321', 
  'corvette', '1234qwer', '88888888', 'q1w2e3r4t5', 'internet', 'samantha', 'whatever', 'maverick', 'steelers', 'mercedes', 
  '123123123', 'qwer1234', 'hardcore', 'q1w2e3r4', 'midnight', 'bigdaddy', 'victoria', '1q2w3e4r', 'cocacola', 'marlboro', 'asdfasdf', 
  '87654321', '12344321', 'jordan23', 'jonathan', 'liverpoo', 'danielle', 'abcd1234', 'scorpion', 'qazwsxedc', 'password1', 
  'slipknot', 'qwerty123', 'startrek', '12341234', 'redskins', 'butthead', 'asdfghjkl', 'qwertyui', 'liverpol', 'dolphins', 'nicholas', 
  'elephant', 'mountain', 'xxxxxxxx', '1q2w3e4r5t', 'metallic', 'shithead', 'benjamin', 'creative', 'rush2112', 'asdfghjk', 
  '4815162342', 'passw0rd', 'bullshit', '1qazxsw2', 'garfield', '01012011', '69696969', 'december', '11223344', 'godzilla',
  'airborne', 'lifehack', 'brooklyn', 'platinum', 'darkness', 'blink182', '789456123', '12qwaszx', 'snowball', 'pakistan', 'redwings', 
  'williams', 'nintendo', 'guinness', 'november', 'minecraft', 'asdf1234', 'lasvegas', 'babygirl', 'dickhead', '12121212', '147258369',
  'explorer', 'snickers', 'metallica', 'alexande', 'paradise', 'michigan', 'carolina', 'lacrosse', 'christin', 'kimberly', 'kristina', 
  '0987654321', 'poohbear', 'bollocks', 'qweasdzxc', 'drowssap', 'caroline', 'einstein', 'spitfire', 'maryjane', '1232323q', 'champion',
  'svetlana', 'westside', 'courtney', '12345qwert', 'patricia', 'aaaaaaaa', 'anderson', 'security', 'stargate', 'simpsons', 'scarface', 
  '123456789a', '1234554321', 'cherokee', 'usuckballz1', 'veronica', 'semperfi', 'scotland', 'marshall', 'qwerty12', '98765432', 'softball', 
  'passport', 'franklin', 'alexander', '55555555', 'zaq12wsx', 'infinity', 'kawasaki', '77777777', 'vladimir', 'freeuser', 'wildcats', 'budlight',
  'brittany', '00000000', 'bulldogs', 'swordfis', 'patriots', 'pearljam', 'colorado', 'ncc1701d', 'motorola', 'logitech', 'juventus', 'wolverin', 
  'warcraft', 'hello123', 'peekaboo', 'panthers', 'elizabet', '123654789', 'spiderma', 'virginia', 'valentin', 'predator', 'mitchell', '741852963', 
  '1111111111', 'rolltide', 'changeme', 'lovelove', 'fktrcfylh', 'loverboy', 'chevelle', 'cardinal', 'michael1', '147852369', 'american', 'alexandr', 
  'electric', 'wolfpack', 'spiderman', 'darkside', '123456789q', '01011980', 'freepass', '99999999', 'fyfcnfcbz', 'airplane', '22222222', 
  '1029384756', 'cheyenne', 'billybob', 'lawrence', 'pussycat', '01012000', 'chocolat', 'business', 'cjkysirj', '123qweasd', 'stingray', 
  'serenity', 'greenday', 'charlie1', 'firebird', 'blizzard', 'a1b2c3d4', 'sterling', 'password123', 'hercules', 'tarheels', 'remember', 'basketball', 
  'zeppelin', 'swimming', 'pavilion', 'engineer', 'bobafett', '21122112', 'darkstart', 'icecream', 'hellfire', 'fireball', 'rockstar', 
  'defender', 'swordfish', 'airforce', 'abcdefgh', 'srinivas', 'bluebird', 'presario', 'wrangler', 'precious', 'harrison', 'goldfish', 'soso123aljg', 
  'dbrnjhbz', 'thailand', 'longhorn', '123qweasdzxc', '123qweasdzxc', 'wordpass', '31415926', '999999999', 'letmein1', 'assassin', 'testtest',
  'microsoft', 'devildog', 'valentina', 'butterfly', 'lonewolf', 'babydoll', 'atlantis', 'montreal', 'angelina', 'shamrock', 'hotstuff', 'mistress', 
  'deftones', 'cadillac', 'blahblah', 'birthday', '1234abcd', '01011990', 'cavalier', 'veronika', 'qazwsx123', 'mustang1', 'goldberg', 
  '12345678910', 'wolfgang', 'savannah', 'leonardo', 'basketba', 'cristina', 'aardvark', 'sweetpea', '13131313', 'freedom1', 'fredfred', 'manchester', 
  'kathleen', 'hamilton', 'fuckyou2', 'renegade', 'drpepper', 'bigboobs', '1qaz2wsx3edc', 'christia', 'buckeyes', '0123456789', 'stephani',
  'enterpri', 'diamonds', 'wetpussy', 'morpheus', '66666666', 'pornstar', 'thuglife', 'napoleon'];    

  return $most_common_passwords;

}

/**
 * check common password function
 * checking top most hacked password
 *
 * @category function
 * @author  M.Noermoehammad
 * @param string $password
 * @return bool
 * 
 */
function check_common_password($password)
{

  $common_password = false;

   if (in_array(strtolower($password), worst_passwords())) {

     $common_password = true;
      
   } else {

      $common_password = false;

   }

   return $common_password;

}