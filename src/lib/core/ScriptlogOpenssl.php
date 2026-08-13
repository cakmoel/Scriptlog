<?php

namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class ScriptlogOpenssl
 *
 * Thin project-level subclass of Laminas\Crypt\Symmetric\Openssl that declares
 * the $supportedAlgos property explicitly.
 *
 * laminas/laminas-crypt 3.8.0 (the latest release supporting PHP 7.4) does not
 * declare this property, yet Openssl::getSupportedAlgorithms() assigns to it
 * dynamically. Under PHP 8.2+ that triggers the deprecation warning
 * "Creation of dynamic property ... is deprecated" whenever the remember-me
 * token encryption (ScriptlogCryptonize::cipherMessage/decipherMessage) runs.
 *
 * Declaring the property mirrors the upstream fix shipped in laminas-crypt
 * 3.9.0, which could not be adopted because it drops PHP 7.4 support. The
 * encryption/decryption format remains byte-for-byte identical, so existing
 * tbl_user_token rows and remember-me cookies stay valid.
 *
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 *
 */
class ScriptlogOpenssl extends \Laminas\Crypt\Symmetric\Openssl
{
    /**
     * The encryption algorithms supported by the OpenSSL extension.
     *
     * Declared to keep PHP 8.2+ dynamic property deprecation away; populated
     * lazily by the inherited getSupportedAlgorithms() method.
     *
     * @var array|null
     * @psalm-suppress PossiblyUnusedProperty -- written dynamically by the
     *     inherited getSupportedAlgorithms() in laminas-crypt 3.8.0, which
     *     Psalm cannot see.
     */
    public $supportedAlgos;
}
