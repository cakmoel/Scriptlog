<?php

namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * class Tokenizer
 *
 * implementing minimum requirements for secure user authentication in scriptlog
 * with long-term persistence - login with "Remember Me" Cookies
 *
 * @category Core Class
 *
 * @see https://www.php.net/manual/en/function.random-bytes.php
 * @see https://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string
 * @see https://phppot.com/php/secure-remember-me-for-login-using-php-session-and-cookies/
 * @see https://paragonie.com/blog/2015/05/using-encryption-and-authentication-correctly
 * @see https://gist.github.com/wopot/94e33bdd1d7faaaa56e2
 * @see https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
 * @see https://paragonie.com/blog/2015/11/choosing-right-cryptography-library-for-your-php-project-guide
 * @see https://www.zimuel.it/blog/cryptography-made-easy-with-zend-framework
 *
 * @uses \random_bytes
 * @uses ScriptlogCryptonize::cipherMessage
 * @uses ScriptlogCryptonize::decipherMessage
 *
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 *
 */
final class Tokenizer
{
    /**
     * getSelectorKey
     *
     * Derives a stable 32-byte key for selector encryption from the
     * application key (SHA-256 output is always 32 bytes and deterministic).
     *
     * @method public getSelectorKey()
     * @static
     * @return string 32 raw bytes
     *
     * @psalm-suppress PossiblyUnusedMethod — consumed by TokenizerTest and
     *            future callers; kept public like the rest of the class.
     */
    public static function getSelectorKey(): string
    {
        return (string) hash('sha256', app_key(), true);
    }

    /**
     * Create token
     *
     * Generates a random token from the cryptographically secure random
     * number generator (random_bytes). The raw bytes are hex-encoded with
     * bin2hex(), so each byte yields two lowercase hex characters and the
     * historical token length contract is preserved (createToken(128) returns
     * a 128 character lowercase hex token backed by 64 bytes, i.e. 512 bits of
     * CSPRNG entropy).
     *
     * @method public createToken()
     * @static
     * @param integer|number $length
     * @return string
     *
     */
    public static function createToken($length): string
    {
        $length = (int)$length;

        // random_bytes() is a CSPRNG available on all PHP versions supported
        // by Scriptlog (>= 7.4); bin2hex() renders two lowercase hex characters
        // per byte, keeping the token length contract while removing the
        // legacy medium-strength pseudo-random generator from the auth token
        // path.
        $byteLength = max(1, (int)ceil($length / 2));

        return bin2hex(random_bytes($byteLength));
    }

    /**
     * setRandomPasswordProtected
     *
     * @param string $password
     * @return string
     *
     */
    public static function setRandomPasswordProtected($password)
    {

        $cost = finding_pwd_cost(0.05, 8);

        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * getRandomPasswordProtected
     *
     * @param string $password
     * @param string $hash
     * @return bool
     *
     */
    public static function isPasswordValid($password, $hash)
    {

        return password_verify($password, $hash);
    }

    /**
     * setRandomSelectorProtected
     *
     * @param string $selector
     * @param string $key
     * @return string
     *
     */
    public static function setRandomSelectorProtected($selector, $key)
    {
        $hash_data = hash('sha256', $selector, true);
        $hash_encoded = base64_encode($hash_data);
        $cipher_data = password_hash($hash_encoded, PASSWORD_DEFAULT);

        return ScriptlogCryptonize::cipherMessage($cipher_data, $key);
    }

    /**
     * isSelectorValid
     *
     * @param string $cipher_data
     * @param string $selector
     * @param string $key
     * @return bool
     *
     */
    public static function isSelectorValid($cipher_data, $selector, $key)
    {

        $decipher = ScriptlogCryptonize::decipherMessage($cipher_data, $key);

        $hash_data = hash('sha256', $selector, true);

        $hash_encoded = base64_encode($hash_data);

        return password_verify($hash_encoded, (string) $decipher);
    }
}
