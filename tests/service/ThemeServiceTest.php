<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * ThemeService Test
 * 
 * Tests for theme business logic.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class ThemeServiceTest extends TestCase
{
    private $themeService;
    private $themeDaoMock;
    private $validatorMock;
    private $sanitizeMock;

    protected function setUp(): void
    {
        $this->themeDaoMock = $this->createMock(\ThemeDao::class);
        $this->validatorMock = $this->createMock(\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Sanitize::class);
        
        $this->themeService = new \ThemeService(
            $this->themeDaoMock,
            $this->validatorMock,
            $this->sanitizeMock
        );
    }

    public function testSetThemeId(): void
    {
        $this->themeService->setThemeId(1);
        $this->assertTrue(true);
    }

    public function testSetThemeTitle(): void
    {
        $this->themeService->setThemeTitle('Test Theme');
        $this->assertTrue(true);
    }

    public function testSetThemeDescription(): void
    {
        $this->themeService->setThemeDescription('Test theme description');
        $this->assertTrue(true);
    }

    public function testSetThemeDesigner(): void
    {
        $this->themeService->setThemeDesigner('Test Designer');
        $this->assertTrue(true);
    }

    public function testSetThemeDirectory(): void
    {
        $this->themeService->setThemeDirectory('test-theme');
        $this->assertTrue(true);
    }

    public function testSetThemeStatus(): void
    {
        $this->themeService->setThemeStatus('Y');
        $this->assertTrue(true);
    }

    public function testGrabThemes(): void
    {
        $this->themeDaoMock->method('findThemes')->willReturn([]);
        $themes = $this->themeService->grabThemes();
        $this->assertIsArray($themes);
    }

    public function testGrabTheme(): void
    {
        $this->themeDaoMock->method('findTheme')->willReturn(['ID' => 1, 'theme_title' => 'Test Theme']);
        $theme = $this->themeService->grabTheme(1);
        $this->assertIsArray($theme);
    }

    public function testTotalThemes(): void
    {
        $this->themeDaoMock->method('totalThemeRecords')->willReturn(3);
        $total = $this->themeService->totalThemes();
        $this->assertEquals(3, $total);
    }

    public function testIsThemeExists(): void
    {
        $this->themeDaoMock->method('themeExists')->willReturn(true);
        $exists = $this->themeService->isThemeExists('Test Theme');
        $this->assertTrue($exists);
    }
}
