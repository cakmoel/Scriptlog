<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class CoreClassesExistenceTest extends TestCase
{
    
    public function testSanitizeClassExists(): void
    {
        $this->assertTrue(class_exists('Sanitize'));
    }
    
    public function testFormValidatorClassExists(): void
    {
        $this->assertTrue(class_exists('FormValidator'));
    }
    
    public function testPaginatorClassExists(): void
    {
        $this->assertTrue(class_exists('Paginator'));
    }
    
    public function testViewClassExists(): void
    {
        $this->assertTrue(class_exists('View'));
    }
    
    public function testDispatcherClassExists(): void
    {
        $this->assertTrue(class_exists('Dispatcher'));
    }
    
    public function testAuthenticationClassExists(): void
    {
        $this->assertTrue(class_exists('Authentication'));
    }
    
    public function testDbFactoryClassExists(): void
    {
        $this->assertTrue(class_exists('DbFactory'));
    }
    
    public function testDaoClassExists(): void
    {
        $this->assertTrue(class_exists('Dao'));
    }
    
    public function testDbInterfaceExists(): void
    {
        $this->assertTrue(interface_exists('DbInterface'));
    }
    
    public function testRegistryClassExists(): void
    {
        $this->assertTrue(class_exists('Registry'));
    }
    
    public function testHtmlClassExists(): void
    {
        $this->assertTrue(class_exists('Html'));
    }
    
    public function testBootstrapClassExists(): void
    {
        $this->assertTrue(class_exists('Bootstrap'));
    }
    
    public function testHandleRequestClassExists(): void
    {
        $this->assertTrue(class_exists('HandleRequest'));
    }
    
    public function testRequestPathClassExists(): void
    {
        $this->assertTrue(class_exists('RequestPath'));
    }
    
    public function testDbExceptionClassExists(): void
    {
        $this->assertTrue(class_exists('DbException'));
    }
    
    public function testSessionMakerClassExists(): void
    {
        $this->assertTrue(class_exists('SessionMaker'));
    }
    
    public function testAppConfigClassExists(): void
    {
        $this->assertTrue(class_exists('AppConfig'));
    }
    
    public function testTokenizerClassExists(): void
    {
        $this->assertTrue(class_exists('Tokenizer'));
    }
    
    public function testRandomClassExists(): void
    {
        $this->assertTrue(class_exists('Random'));
    }
    
    public function testRemoteAddressClassExists(): void
    {
        $this->assertTrue(class_exists('RemoteAddress'));
    }
    
    public function testCSRFGuardClassExists(): void
    {
        $this->assertTrue(class_exists('CSRFGuard'));
    }
    
    public function testAuthorizationClassExists(): void
    {
        $this->assertTrue(class_exists('Authorization'));
    }
    
    public function testFrontHelperClassExists(): void
    {
        $this->assertTrue(class_exists('FrontHelper'));
    }
    
    public function testBlogSchemaClassExists(): void
    {
        $this->assertTrue(class_exists('BlogSchema'));
    }
    
    public function testRSSWriterClassExists(): void
    {
        $this->assertTrue(class_exists('RSSWriter'));
    }
    
    public function testAtomWriterClassExists(): void
    {
        $this->assertTrue(class_exists('AtomWriter'));
    }
    
    public function testSitemapClassExists(): void
    {
        $this->assertTrue(class_exists('Sitemap'));
    }
    
    public function testPaginationClassExists(): void
    {
        $this->assertTrue(class_exists('Pagination'));
    }
    
    public function testMessageLogClassExists(): void
    {
        $this->assertTrue(class_exists('MessageLog'));
    }
    
    public function testLogErrorClassExists(): void
    {
        $this->assertTrue(class_exists('LogError'));
    }
    
    public function testBoardInterfaceExists(): void
    {
        $this->assertTrue(interface_exists('BoardInterface'));
    }
    
    public function testAppInterfaceExists(): void
    {
        $this->assertTrue(interface_exists('AppInterface'));
    }
    
    public function testIThrowableExists(): void
    {
        $this->assertTrue(interface_exists('IThrowable'));
    }
    
    public function testUtilClassExists(): void
    {
        $this->assertTrue(class_exists('Util'));
    }
    
    public function testBaseModelClassExists(): void
    {
        $this->assertTrue(class_exists('BaseModel'));
    }
    
    public function testBaseAppClassExists(): void
    {
        $this->assertTrue(class_exists('BaseApp'));
    }
    
    public function testPassPhraseKeyClassExists(): void
    {
        $this->assertTrue(class_exists('PassPhraseKey'));
    }
    
    public function testPageCacheClassExists(): void
    {
        $this->assertTrue(class_exists('PageCache'));
    }
    
    public function testDbMySQLiClassExists(): void
    {
        $this->assertTrue(class_exists('DbMySQLi'));
    }
    
    public function testDebugRouteClassExists(): void
    {
        $this->assertTrue(class_exists('DebugRoute'));
    }
    
    public function testScriptlogScannerClassExists(): void
    {
        $this->assertTrue(class_exists('ScriptlogScanner'));
    }
    
    public function testActionConstClassExists(): void
    {
        $this->assertTrue(class_exists('ActionConst'));
    }
    
    public function testDateGeneratorClassExists(): void
    {
        $this->assertTrue(class_exists('DateGenerator'));
    }
    
    public function testSearchFinderClassExists(): void
    {
        $this->assertTrue(class_exists('SearchFinder'));
    }
    
    public function testMailerClassExists(): void
    {
        $this->assertTrue(class_exists('Mailer'));
    }
    
    public function testCleanClassExists(): void
    {
        $this->assertTrue(class_exists('Clean'));
    }
    
    public function testResizeClassExists(): void
    {
        $this->assertTrue(class_exists('Resize'));
    }
    
    public function testDashboardClassExists(): void
    {
        $this->assertTrue(class_exists('Dashboard'));
    }
    
    public function testSessionClassExists(): void
    {
        $this->assertTrue(class_exists('Session'));
    }
    
    public function testUbenchClassExists(): void
    {
        $this->assertTrue(class_exists('Ubench'));
    }
    
    public function testHZipClassExists(): void
    {
        $this->assertTrue(class_exists('HZip'));
    }
    
    public function testImgCompressorClassExists(): void
    {
        $this->assertTrue(class_exists('ImgCompressor'));
    }
    
    public function testResponsiveCaptchaClassExists(): void
    {
        $this->assertTrue(class_exists('ResponsiveCaptcha'));
    }
    
    public function testWallClassExists(): void
    {
        $this->assertTrue(class_exists('Wall'));
    }
    
    public function testMedooInitClassExists(): void
    {
        $this->assertTrue(class_exists('MedooInit'));
    }
    
    public function testScriptlogCryptonizeClassExists(): void
    {
        $this->assertTrue(class_exists('ScriptlogCryptonize'));
    }
}
