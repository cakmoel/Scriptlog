<?php
/**
 * Remember Me Integration Test
 *
 * Regression tests for the persistent login ("Remember Me") token lifecycle
 * used by admin/authenticator.php Path 2 and Scriptlog\Core\Authentication.
 *
 * The tests exercise the exact DB operations performed when the remember
 * flag is on, mirroring the production flow:
 *
 *   - Tokenizer::createToken()                     -> random validator/selector
 *   - Tokenizer::setRandomPasswordProtected()      -> bcrypt validator hash
 *   - Tokenizer::setRandomSelectorProtected()      -> encrypted selector hash
 *   - UserTokenDao::createUserToken()              -> persist the token
 *   - UserTokenDao::getTokenByLogin()              -> fetch unexpired/expired
 *   - UserTokenDao::updateTokenExpired()           -> invalidate old token
 *   - UserTokenDao::updateUserToken()              -> renew/rotate (same row)
 *   - UserTokenDao::deleteUserToken()              -> logout cleanup (expired)
 *
 * It also verifies the Tokenizer validator/selector round-trips that
 * authenticator.php relies on to trust the scriptlog_validator /
 * scriptlog_selector cookie pair.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;
use Scriptlog\Core\Registry;
use Scriptlog\Core\Tokenizer;
use Scriptlog\Dao\UserTokenDao;

require_once __DIR__ . '/../../lib/core/Registry.php';
require_once __DIR__ . '/../../lib/core/Tokenizer.php';

class RememberMeIntegrationTest extends TestCase
{
    private const TEST_LOGIN = 'remember_me_test';
    private const TEST_EMAIL = 'remember_me_test@example.com';
    private const TEST_KEY = 'GVXUD7-72HUXD-2TFCDT-8DDC2A';

    private static ?PDO $pdo = null;
    private ?UserTokenDao $userTokenDao = null;

    public static function setUpBeforeClass(): void
    {
        try {
            self::$pdo = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware'
            );
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            self::$pdo = null;
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('Test database not available');
            return;
        }

        $GLOBALS['__test_prefix'] = '';

        $db = new Db();
        $db->setDbConnection([
            'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware'
        ]);

        Registry::set('dbc', $db);

        $this->userTokenDao = new UserTokenDao();

        $this->cleanupTestData();
        $this->insertTestUser();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        unset($GLOBALS['__test_prefix']);
        $this->userTokenDao = null;
    }

    private function cleanupTestData(): void
    {
        if (self::$pdo === null) return;
        try {
            self::$pdo->exec("DELETE FROM tbl_user_token WHERE user_login = '" . self::TEST_LOGIN . "'");
            self::$pdo->exec("DELETE FROM tbl_users WHERE user_login = '" . self::TEST_LOGIN . "'");
        } catch (PDOException $e) {
        }
    }

    private function insertTestUser(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_users (user_login, user_email, user_pass, user_level, user_fullname, user_registered, user_session, user_banned)
            VALUES (?, ?, ?, 'administrator', 'Remember Me Test', NOW(), '', 0)
        ");
        $stmt->execute([self::TEST_LOGIN, self::TEST_EMAIL, 'testpass']);
    }

    /**
     * Mirrors Authentication::login() token persistence (lines 328-355):
     * a login with remember=on must store one non-expired token with a
     * ~30-day expiry, discoverable via getTokenByLogin(login, 0).
     */
    public function testRememberMeLoginCreatesPersistentToken(): void
    {
        $this->createPersistentToken(0);

        $token = $this->userTokenDao->getTokenByLogin(self::TEST_LOGIN, 0);

        $this->assertIsArray($token);
        $this->assertNotEmpty($token['ID']);
        $this->assertEquals(0, (int)$token['is_expired']);
        $this->assertEquals(self::TEST_LOGIN, $token['user_login']);

        $expiry = strtotime($token['expired_date']);
        $this->assertGreaterThan(time() + 2592000 - 3600, $expiry, 'Expiry should be ~30 days out');
        $this->assertLessThan(time() + 2592000 + 3600, $expiry);

        $this->assertStringStartsWith('$2y$', $token['pwd_hash']);
    }

    /**
     * Tokenizer validator round-trip: the bcrypt validator hash must verify
     * via isPasswordValid() — the exact check authenticator.php performs on
     * the scriptlog_validator cookie.
     */
    public function testTokenizerValidatorRoundTrip(): void
    {
        $validator = Tokenizer::createToken(128);
        $hash = Tokenizer::setRandomPasswordProtected($validator);

        $this->assertTrue(Tokenizer::isPasswordValid($validator, $hash));
        $this->assertFalse(Tokenizer::isPasswordValid('wrong-token-value', $hash));
    }

    /**
     * Tokenizer selector round-trip: the selector hash is encrypted with the
     * app key via Laminas block cipher. isSelectorValid() must decrypt and
     * verify — the exact check authenticator.php performs on the
     * scriptlog_selector cookie.
     */
    public function testTokenizerSelectorRoundTrip(): void
    {
        $selector = Tokenizer::createToken(128);
        $encrypted = Tokenizer::setRandomSelectorProtected($selector, self::TEST_KEY);

        $this->assertTrue(Tokenizer::isSelectorValid($encrypted, $selector, self::TEST_KEY));
        $this->assertFalse(Tokenizer::isSelectorValid($encrypted, 'wrong-selector-value', self::TEST_KEY));
        $this->assertFalse(Tokenizer::isSelectorValid($encrypted, $selector, 'WRONG-SECRET-KEY-XYZ-1234'));
    }

    /**
     * Return-visit renewal mirrors authenticator.php Path 2: the old token is
     * marked expired (updateTokenExpired), then renewed in place
     * (updateUserToken) with fresh hashes and is_expired=0. The token row ID
     * must stay stable while the hashes rotate.
     */
    public function testRememberMeTokenRenewalRotatesToken(): void
    {
        $oldValidator = Tokenizer::createToken(128);
        $oldSelector = Tokenizer::createToken(128);
        $this->userTokenDao->createUserToken([
            'user_login' => self::TEST_LOGIN,
            'pwd_hash' => Tokenizer::setRandomPasswordProtected($oldValidator),
            'selector_hash' => Tokenizer::setRandomSelectorProtected($oldSelector, self::TEST_KEY),
            'expired_date' => date('Y-m-d H:i:s', time() + 2592000)
        ]);

        $tokenA = $this->userTokenDao->getTokenByLogin(self::TEST_LOGIN, 0);
        $this->assertNotEmpty($tokenA['ID']);

        // 1. Authenticator marks the old token expired
        $this->userTokenDao->updateTokenExpired($tokenA['ID']);

        // 2. Authenticator renews in place: same row ID, fresh hashes, active
        $newValidator = Tokenizer::createToken(128);
        $newSelector = Tokenizer::createToken(128);
        $this->userTokenDao->updateUserToken([
            'pwd_hash' => Tokenizer::setRandomPasswordProtected($newValidator),
            'selector_hash' => Tokenizer::setRandomSelectorProtected($newSelector, self::TEST_KEY),
            'is_expired' => 0,
            'expired_date' => date('Y-m-d H:i:s', time() + 2592000)
        ], self::TEST_LOGIN);

        $renewed = $this->userTokenDao->getTokenByLogin(self::TEST_LOGIN, 0);

        $this->assertIsArray($renewed);
        $this->assertEquals($tokenA['ID'], $renewed['ID'], 'Renewal must keep the same token row ID');
        $this->assertEquals(0, (int)$renewed['is_expired']);

        // Fresh validator/selector must verify against the renewed hashes
        $this->assertTrue(Tokenizer::isPasswordValid($newValidator, $renewed['pwd_hash']));
        $this->assertTrue(Tokenizer::isSelectorValid($renewed['selector_hash'], $newSelector, self::TEST_KEY));
        $this->assertFalse(Tokenizer::isPasswordValid($oldValidator, $renewed['pwd_hash']));
    }

    /**
     * An expired persistent token must NOT satisfy the remember-me login
     * check: getTokenByLogin(login, 0) must be empty once marked expired,
     * while getTokenByLogin(login, 1) still finds it.
     */
    public function testExpiredTokenIsNotReturnedForLogin(): void
    {
        $this->createPersistentToken(0);

        $token = $this->userTokenDao->getTokenByLogin(self::TEST_LOGIN, 0);
        $this->assertNotEmpty($token['ID']);

        $this->userTokenDao->updateTokenExpired($token['ID']);

        $this->assertTrue($this->userTokenDao->getTokenByLogin(self::TEST_LOGIN, 0) === true, 'Active lookup must be empty');

        $expired = $this->userTokenDao->getTokenByLogin(self::TEST_LOGIN, 1);
        $this->assertIsArray($expired);
        $this->assertEquals(1, (int)$expired['is_expired']);
    }

    /**
     * Logout cleanup mirrors Authentication::clearAuthCookies() ->
     * UserTokenDao::deleteUserToken(): the expired persistent token row is
     * removed so the remember-me cookie pair can no longer be validated
     * against the database.
     */
    public function testLogoutClearsExpiredPersistentTokens(): void
    {
        $this->createPersistentToken(0);

        $token = $this->userTokenDao->getTokenByLogin(self::TEST_LOGIN, 0);
        $this->assertNotEmpty($token['ID']);

        // Mark expired, as happens when the token has been rotated/abandoned
        $this->userTokenDao->updateTokenExpired($token['ID']);

        $this->userTokenDao->deleteUserToken(self::TEST_LOGIN);

        $this->assertTrue($this->userTokenDao->getTokenByLogin(self::TEST_LOGIN, 0) === true, 'Active lookup must be empty after logout');
        $this->assertTrue($this->userTokenDao->getTokenByLogin(self::TEST_LOGIN, 1) === true, 'Expired lookup must be empty after logout');
    }

    /**
     * Creates a persistent token row exactly as Authentication::login() does
     * (createUserToken does not set is_expired; the DB default keeps it 0).
     * When $isExpired is 1 the row is first created active then flipped via
     * updateTokenExpired to reflect a previously rotated/abandoned token.
     */
    private function createPersistentToken(int $isExpired): void
    {
        $this->userTokenDao->createUserToken([
            'user_login' => self::TEST_LOGIN,
            'pwd_hash' => Tokenizer::setRandomPasswordProtected(Tokenizer::createToken(128)),
            'selector_hash' => Tokenizer::setRandomSelectorProtected(Tokenizer::createToken(128), self::TEST_KEY),
            'expired_date' => date('Y-m-d H:i:s', time() + 2592000)
        ]);

        if ($isExpired === 1) {
            $token = $this->userTokenDao->getTokenByLogin(self::TEST_LOGIN, 0);
            if (is_array($token) && !empty($token['ID'])) {
                $this->userTokenDao->updateTokenExpired($token['ID']);
            }
        }
    }
}
