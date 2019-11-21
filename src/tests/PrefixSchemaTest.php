<?php

require __DIR__ . '/simpletest/autorun.php';
require __DIR__ . '/../lib/utility/add-http.php';

class PrefixSchemaTest extends UnitTestCase
{
   public function testCanAddPrefixHTTP()
   {
       $url = "facebook.com";

       $result = add_http($url);

       $this->assertEqual($result, "http://facebook.com");

   }

   public function testCanAddSchemeHTTP()
   {
       $url = "kartatopia.com";

       $result = add_scheme($url);

       $this->assertEqual($result, "http://kartatopia.com");
       
   }

}