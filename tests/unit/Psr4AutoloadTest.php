<?php
/**
 * PSR-4 Autoload Verification Test.
 *
 * Tests that the PSR-4 autoloading configuration in composer.json
 * works correctly and that existing classmap autoloading is preserved.
 *
 * @category Tests
 * @version  1.0
 * @since    1.0
 */

use PHPUnit\Framework\TestCase;

class Psr4AutoloadTest extends TestCase
{
    /**
     * Test that a PSR-4 namespaced class can be autoloaded.
     *
     * @return void
     */
    public function testPsr4NamespacedClassAutoloads(): void
    {
        $this->assertTrue(
            class_exists('Scriptlog\Core\Psr4AutoloadTest'),
            'PSR-4 namespaced class Scriptlog\Core\Psr4AutoloadTest must be autoloadable'
        );
    }

    /**
     * Test that the PSR-4 class returns the expected value.
     *
     * @return void
     */
    public function testPsr4ClassMethodReturnsExpected(): void
    {
        $this->assertTrue(class_exists('Scriptlog\Core\Psr4AutoloadTest'));
        $this->assertSame(
            'PSR-4 autoloading is working correctly',
            \Scriptlog\Core\Psr4AutoloadTest::hello()
        );
    }

    /**
     * Test that the PSR-4 class returns the correct FQCN.
     *
     * @return void
     */
    public function testPsr4ClassFqcnIsCorrect(): void
    {
        $this->assertTrue(class_exists('Scriptlog\Core\Psr4AutoloadTest'));
        $this->assertSame(
            'Scriptlog\Core\Psr4AutoloadTest',
            \Scriptlog\Core\Psr4AutoloadTest::fqcn()
        );
    }

    /**
     * Test that ALL existing global-namespace classes still autoload
     * via the classmap (backward compatibility).
     *
     * @return void
     */
    public function testExistingGlobalNamespaceClassesStillAutoload(): void
    {
        $expectedClasses = [
            'Bootstrap',
            'Dao',
            'PostDao',
            'UserDao',
            'Registry',
            'Dispatcher',
            'PostService',
            'UserService',
            'PostController',
            'UserController',
            'PostModel',
            'DbFactory',
            'Authentication',
            'FrontHelper',
            'HandleRequest',
            'Bootstrap',
            'View',
            'Sanitize',
            'FormValidator',
            'Paginator',
            'ThemeDao',
            'PluginDao',
            'CommentDao',
            'TopicDao',
        ];

        foreach ($expectedClasses as $className) {
            $this->assertTrue(
                class_exists($className),
                "Global-namespace class {$className} must still be autoloadable via classmap"
            );
        }
    }

    /**
     * Test that the composer.json autoload configuration is correct.
     *
     * @return void
     */
    public function testComposerJsonHasPsr4Config(): void
    {
        $composerJson = json_decode(
            file_get_contents(__DIR__ . '/../../composer.json'),
            true
        );

        $this->assertNotNull($composerJson, 'composer.json must be valid JSON');
        $this->assertArrayHasKey('autoload', $composerJson);
        $this->assertArrayHasKey('classmap', $composerJson['autoload']);
        $this->assertArrayHasKey('psr-4', $composerJson['autoload']);
        $this->assertArrayHasKey('Scriptlog\\', $composerJson['autoload']['psr-4']);
        $this->assertSame(
            ['lib/'],
            $composerJson['autoload']['classmap']
        );
        $this->assertSame(
            'lib/',
            $composerJson['autoload']['psr-4']['Scriptlog\\']
        );
    }

    /**
     * Test that the PSR-4 prefix is registered in the Composer autoloader.
     *
     * @return void
     */
    public function testPsr4PrefixRegisteredInAutoloader(): void
    {
        $loader = require __DIR__ . '/../../lib/vendor/autoload.php';
        $prefixes = $loader->getPrefixesPsr4();

        $this->assertArrayHasKey(
            'Scriptlog\\',
            $prefixes,
            'Scriptlog\\ PSR-4 prefix must be registered'
        );
        $this->assertContains(
            realpath(__DIR__ . '/../../lib'),
            array_map('realpath', $prefixes['Scriptlog\\']),
            'Scriptlog\\ prefix must map to lib/ directory'
        );
    }
}
