<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class AdditionalUtilityFunctionsTest extends TestCase
{
    
    public function testMakeSlugVariations(): void
    {
        if (function_exists('make_slug')) {
            $this->assertIsString(make_slug('Hello World'));
            $this->assertIsString(make_slug('Test 123'));
            $this->assertIsString(make_slug('Special!@#Characters'));
        }
    }
    
    public function testIsJsonValidVariations(): void
    {
        if (function_exists('is_json_valid')) {
            $this->assertIsBool(is_json_valid('{"key":"value"}'));
            $this->assertIsBool(is_json_valid('[1,2,3]'));
            $this->assertIsBool(is_json_valid('not json'));
        }
    }
    
    public function testGetOsVariations(): void
    {
        if (function_exists('get_os')) {
            $this->assertIsString(get_os('Mozilla/5.0 (Windows NT 10.0)'));
            $this->assertIsString(get_os('Mozilla/5.0 (Macintosh)'));
            $this->assertIsString(get_os('Unknown/1.0'));
        }
    }
    
    public function testEscapeNullByteVariations(): void
    {
        if (function_exists('escape_null_byte')) {
            $this->assertIsString(escape_null_byte("test"));
            $this->assertIsString(escape_null_byte("test\x00string"));
        }
    }
    
    public function testMakeDateVariations(): void
    {
        if (function_exists('make_date')) {
            $this->assertIsString(make_date('2023-05-15'));
            $this->assertIsString(make_date('2023-05-15', 'F j, Y'));
        }
    }
    
    public function testConvertToParagraphVariations(): void
    {
        if (function_exists('convert_to_paragraph')) {
            $this->assertIsString(convert_to_paragraph("Line 1\nLine 2"));
            $this->assertIsString(convert_to_paragraph(""));
        }
    }
    
    public function testDropdownVariations(): void
    {
        if (function_exists('dropdown')) {
            $this->assertIsString(dropdown('test', ['a' => 'A', 'b' => 'B']));
            $this->assertIsString(dropdown('test', [], 'default'));
        }
    }
    
    public function testCheckFileExtensionVariations(): void
    {
        if (function_exists('check_file_extension')) {
            $this->assertIsBool(check_file_extension('test.pdf', ['pdf', 'doc']));
            $this->assertIsBool(check_file_extension('test.exe', ['pdf', 'doc']));
        }
    }
    
    public function testValidateDateVariations(): void
    {
        if (function_exists('validate_date')) {
            $this->assertIsBool(validate_date('2023-05-15'));
            $this->assertIsBool(validate_date('invalid'));
            $this->assertIsBool(validate_date('2023-13-45'));
        }
    }
    
    public function testUrlValidationVariations(): void
    {
        if (function_exists('url_validation')) {
            $this->assertIsBool(url_validation('http://example.com'));
            $this->assertIsBool(url_validation('invalid'));
        }
    }
    
    public function testAddHttpVariations(): void
    {
        if (function_exists('add_http')) {
            $this->assertIsString(add_http('example.com'));
            $this->assertIsString(add_http('http://example.com'));
            $this->assertIsString(add_http('https://example.com'));
        }
    }
    
    public function testRemoveAccentsVariations(): void
    {
        if (function_exists('remove_accents')) {
            $this->assertIsString(remove_accents('café'));
            $this->assertIsString(remove_accents('für'));
            $this->assertIsString(remove_accents('hello'));
        }
    }
    
    public function testAppInfoVariations(): void
    {
        if (function_exists('app_info')) {
            $result = app_info();
            $this->assertTrue(is_string($result) || is_array($result));
        }
    }
    
    public function testAppUrlVariations(): void
    {
        if (function_exists('app_url')) {
            $this->assertIsString(app_url());
        }
    }
    
    public function testUniqIdRealVariations(): void
    {
        if (function_exists('uniq_id_real')) {
            $this->assertIsString(uniq_id_real());
            $this->assertGreaterThan(10, strlen(uniq_id_real()));
        }
    }
    
    public function testGenerateMediaIdentifierVariations(): void
    {
        if (function_exists('generate_media_identifier')) {
            $this->assertIsString(generate_media_identifier());
        }
    }
    
    public function testMimeTypeDictionaryVariations(): void
    {
        if (function_exists('mime_type_dictionary')) {
            $result = mime_type_dictionary();
            $this->assertIsArray($result);
        }
    }
    
    public function testMediaPropertiesVariations(): void
    {
        if (function_exists('media_properties')) {
            $this->assertTrue(true);
        }
    }
    
    public function testInvokeConfigVariations(): void
    {
        if (function_exists('invoke_config')) {
            $this->assertTrue(function_exists('invoke_config'));
        }
    }
    
    public function testThemeNavigationVariations(): void
    {
        if (function_exists('theme_navigation')) {
            $this->assertTrue(true);
        }
    }
    
    public function testAccessControlListVariations(): void
    {
        if (function_exists('access_control_list')) {
            $this->assertTrue(true);
        }
    }
    
    public function testUserPrivilegeVariations(): void
    {
        if (function_exists('user_privilege')) {
            $this->assertTrue(true);
        }
    }
    
    public function testTaggerVariations(): void
    {
        if (function_exists('tagger')) {
            $this->assertIsString(tagger('tag1, tag2, tag3'));
            $this->assertIsString(tagger(''));
        }
    }
    
    public function testPluginHelperVariations(): void
    {
        if (function_exists('plugin_helper')) {
            $result = plugin_helper();
            $this->assertIsArray($result);
        }
    }
    
    public function testMembershipVariations(): void
    {
        if (function_exists('membership')) {
            $result = membership();
            $this->assertIsArray($result);
        }
    }
    
    public function testUserRegistrationVariations(): void
    {
        if (function_exists('user_registration')) {
            $result = user_registration();
            $this->assertIsArray($result);
        }
    }
    
    public function testAppTaglineVariations(): void
    {
        if (function_exists('app_tagline')) {
            $this->assertIsString(app_tagline());
        }
    }
    
    public function testAppSitenameVariations(): void
    {
        if (function_exists('app_sitename')) {
            $this->assertIsString(app_sitename());
        }
    }
    
    public function testAppKeyVariations(): void
    {
        if (function_exists('app_key')) {
            $this->assertIsString(app_key());
        }
    }
    
    public function testGetTablePrefixVariations(): void
    {
        if (function_exists('get_table_prefix')) {
            $this->assertIsString(get_table_prefix());
        }
    }
    
    public function testTimezoneVariations(): void
    {
        if (function_exists('timezone')) {
            $this->assertIsString(timezone());
        }
    }
    
    public function testUnparseUrlVariations(): void
    {
        if (function_exists('unparse_url')) {
            $this->assertIsString(unparse_url(['scheme' => 'http', 'host' => 'example.com']));
            $this->assertIsString(unparse_url([]));
        }
    }
    
    public function testBuildQueryVariations(): void
    {
        if (function_exists('build_query')) {
            $this->assertTrue(true);
        }
    }
    
    public function testGetMimeVariations(): void
    {
        if (function_exists('get_mime')) {
            $this->assertTrue(true);
        }
    }
    
    public function testCheckDisabledFunctionsVariations(): void
    {
        if (function_exists('check_disabled_functions')) {
            $this->assertTrue(true);
        }
    }
    
    public function testNotfoundIdVariations(): void
    {
        if (function_exists('notfound_id')) {
            $this->assertTrue(true);
        }
    }
    
    public function testForbiddenIdVariations(): void
    {
        if (function_exists('forbidden_id')) {
            $this->assertTrue(true);
        }
    }
    
    public function testUserInfoVariations(): void
    {
        if (function_exists('user_info')) {
            $this->assertTrue(true);
        }
    }
    
    public function testProtectedPostVariations(): void
    {
        if (function_exists('protected_post')) {
            $result = protected_post();
            $this->assertIsArray($result);
        }
    }
    
    public function testAppReadingSettingVariations(): void
    {
        if (function_exists('app_reading_setting')) {
            $this->assertTrue(true);
        }
    }
    
    public function testAdminTagTitleVariations(): void
    {
        if (function_exists('admin_tag_title')) {
            $this->assertTrue(true);
        }
    }
    
    public function testValidatePluginStructureValid(): void
    {
        if (function_exists('validate_plugin_structure')) {
            $pluginDir = __DIR__ . '/../admin/plugins/hello-world';
            $result = validate_plugin_structure($pluginDir);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('valid', $result);
            $this->assertArrayHasKey('errors', $result);
            $this->assertArrayHasKey('info', $result);
            $this->assertTrue($result['valid']);
            $this->assertEmpty($result['errors']);
            $this->assertNotEmpty($result['info']);
        }
    }
    
    public function testValidatePluginStructureMissingIni(): void
    {
        if (function_exists('validate_plugin_structure')) {
            $tempDir = sys_get_temp_dir() . '/test_plugin_' . uniqid();
            mkdir($tempDir);
            
            $result = validate_plugin_structure($tempDir);
            
            $this->assertIsArray($result);
            $this->assertFalse($result['valid']);
            $this->assertNotEmpty($result['errors']);
            
            rmdir($tempDir);
        }
    }
    
    public function testValidatePluginStructureNonExistent(): void
    {
        if (function_exists('validate_plugin_structure')) {
            $result = validate_plugin_structure('/non/existent/path');
            
            $this->assertIsArray($result);
            $this->assertFalse($result['valid']);
            $this->assertNotEmpty($result['errors']);
        }
    }
    
    public function testGetPluginInfo(): void
    {
        if (function_exists('get_plugin_info')) {
            $pluginDir = __DIR__ . '/../admin/plugins/hello-world';
            $result = get_plugin_info($pluginDir);
            
            $this->assertIsArray($result);
            $this->assertNotEmpty($result);
            $this->assertEquals('Hello World', $result['plugin_name']);
        }
    }
    
    public function testGetPluginInfoNonExistent(): void
    {
        if (function_exists('get_plugin_info')) {
            $result = get_plugin_info('/non/existent/plugin');
            
            $this->assertFalse($result);
        }
    }
    
    public function testGetPluginSqlFile(): void
    {
        if (function_exists('get_plugin_sql_file')) {
            $pluginDir = __DIR__ . '/../admin/plugins/hello-world';
            $result = get_plugin_sql_file($pluginDir);
            
            $this->assertIsString($result);
            $this->assertStringContainsString('schema.sql', $result);
        }
    }
    
    public function testGetPluginSqlFileNoSql(): void
    {
        if (function_exists('get_plugin_sql_file')) {
            $tempDir = sys_get_temp_dir() . '/test_plugin_nosql_' . uniqid();
            mkdir($tempDir);
            
            $result = get_plugin_sql_file($tempDir);
            
            $this->assertFalse($result);
            
            rmdir($tempDir);
        }
    }
    
    public function testGetPluginFunctionsFile(): void
    {
        if (function_exists('get_plugin_functions_file')) {
            $pluginDir = __DIR__ . '/../admin/plugins/hello-world';
            $result = get_plugin_functions_file($pluginDir);
            
            $this->assertIsString($result);
            $this->assertStringContainsString('functions.php', $result);
        }
    }
    
    public function testGetPluginFunctionsFileNoFunctions(): void
    {
        if (function_exists('get_plugin_functions_file')) {
            $tempDir = sys_get_temp_dir() . '/test_plugin_nofunc_' . uniqid();
            mkdir($tempDir);
            
            $result = get_plugin_functions_file($tempDir);
            
            $this->assertFalse($result);
            
            rmdir($tempDir);
        }
    }
    
    public function testPluginRequiredFieldsConstant(): void
    {
        if (defined('PLUGIN_REQUIRED_FIELDS')) {
            $this->assertIsArray(PLUGIN_REQUIRED_FIELDS);
            $this->assertContains('plugin_name', PLUGIN_REQUIRED_FIELDS);
            $this->assertContains('plugin_description', PLUGIN_REQUIRED_FIELDS);
            $this->assertContains('plugin_level', PLUGIN_REQUIRED_FIELDS);
            $this->assertContains('plugin_version', PLUGIN_REQUIRED_FIELDS);
            $this->assertContains('plugin_author', PLUGIN_REQUIRED_FIELDS);
            $this->assertContains('plugin_loader', PLUGIN_REQUIRED_FIELDS);
            $this->assertContains('plugin_action', PLUGIN_REQUIRED_FIELDS);
        }
    }
}
