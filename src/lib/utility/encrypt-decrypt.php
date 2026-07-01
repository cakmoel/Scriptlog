<?php

/**
 * create_encoded_key
 *
 * Derives the master encryption key material. Uses the Defuse cipher key
 * (stored outside web root) with HKDF for proper key derivation. Falls back
 * to app_key() for environments where the cipher key is not available.
 *
 * @category Function create_encoded_key to produce binary key material
 * @license MIT
 * @version 1.1
 * @return string Base64-encoded 32-byte key material
 */
function create_encoded_key()
{
    // Primary: use the Defuse cipher key (stored outside web root)
    if (class_exists('ScriptlogCryptonize')) {
        try {
            $defuseKey = ScriptlogCryptonize::scriptlogCipherKey();
            $raw = $defuseKey->saveToAsciiSafeString();
            $derived = hash_hkdf('sha256', $raw, 32, 'encrypt-decrypt-v2');
            return base64_encode($derived);
        } catch (Throwable $e) {
            // Fall through to app_key fallback
        }
    }
    // Fallback: use app_key from config.php (backward compatible)
    return base64_encode(app_key());
}

/**
 * encrypt()
 * Encrypting message
 *
 * @category Function encrypt() to encrypt message
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @see https://www.php.net/manual/en/function.openssl-encrypt.php
 * @param string $text
 * @param string $key
 * @return string
 */
function encrypt(string $data, string $key): string
{

    $passphrase = base64_decode(create_encoded_key());

    // For backward compatibility: if key is hex string from md5, convert to binary
    // This ensures consistency between encrypt and decrypt
    $second_passphrase = hex2bin($key);

    $cipher_method = 'aes-256-cbc';

    if (! in_array($cipher_method, openssl_get_cipher_methods())) {
        scriptlog_error("Cipher method not supported");
    }

    $iv_length = openssl_cipher_iv_length($cipher_method);

    if (function_exists('random_bytes')) {
        $iv = random_bytes($iv_length);
    } elseif ('openssl_random_pseudo_bytes') {
        $iv = openssl_random_pseudo_bytes($iv_length);
    } else {
        $iv = ircmaxell_random_generator($iv_length);
    }

    $ciphertext = openssl_encrypt($data, $cipher_method, $passphrase, OPENSSL_RAW_DATA, $iv);

    if (! in_array('sha3-512', hash_algos())) {
        scriptlog_error("Hashing algorithm is not supported");
    }

    $hmac = hash_hmac('sha3-512', $ciphertext, $second_passphrase, true);

    return base64_encode($iv . $hmac . $ciphertext);
}

/**
 * _try_decrypt
 *
 * Internal helper: attempt to decrypt with a given AES passphrase and HMAC key.
 * Returns the plaintext on success, false on failure.
 *
 * @param string $data      Base64-encoded ciphertext (IV+HMAC+payload)
 * @param string $hmac_key  Binary key for HMAC verification
 * @param string $aes_key   Binary key for AES-256-CBC decryption
 * @return string|false
 */
function _try_decrypt(string $data, string $hmac_key, string $aes_key)
{
    $data_decoded = base64_decode($data);
    if ($data_decoded === false) {
        return false;
    }

    $cipher_method = 'aes-256-cbc';
    if (!in_array($cipher_method, openssl_get_cipher_methods())) {
        return false;
    }

    $iv_length = openssl_cipher_iv_length($cipher_method);
    if (strlen($data_decoded) < $iv_length + 64 + 1) {
        return false;
    }

    $iv = substr($data_decoded, 0, $iv_length);
    $stored_hmac = substr($data_decoded, $iv_length, 64);
    $payload = substr($data_decoded, $iv_length + 64);

    $plaintext = openssl_decrypt($payload, $cipher_method, $aes_key, OPENSSL_RAW_DATA, $iv);
    if ($plaintext === false) {
        return false;
    }

    if (!in_array('sha3-512', hash_algos())) {
        return false;
    }

    // Verify HMAC
    $expected_hmac = hash_hmac('sha3-512', $payload, $hmac_key, true);
    if (hash_equals($stored_hmac, $expected_hmac)) {
        return $plaintext;
    }

    return false;
}

/**
 * decrypt()
 * Decrypting message
 *
 * Tries the primary cipher key first (Defuse key outside web root), then
 * falls back to the legacy app_key for backward compatibility with content
 * encrypted before the key derivation was hardened.
 *
 * @category Function decrypt() to decrypt message
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @see https://www.php.net/manual/en/function.openssl-decrypt.php
 * @param string $text
 * @param string $key
 * @return string|false
 */
function decrypt(string $data, string $key)
{
    $hmac_key_new = hex2bin($key);
    $hmac_key_old = base64_decode($key);

    // 1) New AES key (Defuse-derived) + new HMAC key
    $aes_new = base64_decode(create_encoded_key());
    $result = _try_decrypt($data, $hmac_key_new, $aes_new);
    if ($result !== false) {
        return $result;
    }

    // 2) New AES key + old HMAC key (backward compat for HMAC scheme)
    $result = _try_decrypt($data, $hmac_key_old, $aes_new);
    if ($result !== false) {
        return $result;
    }

    // 3) Old AES key (app_key) + new HMAC key (for content encrypted with old AES + new HMAC)
    $aes_old = base64_decode(base64_encode(app_key()));
    $result = _try_decrypt($data, $hmac_key_new, $aes_old);
    if ($result !== false) {
        return $result;
    }

    // 4) Old AES key + old HMAC key (full legacy path)
    $result = _try_decrypt($data, $hmac_key_old, $aes_old);
    if ($result !== false) {
        return $result;
    }

    return false;
}
