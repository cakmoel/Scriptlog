<?php

/**
 * captcha-login.php
 *
 * @category generate captcha code when login attempt failed
 * @author M.Noermoehammad <scriptlog@yandex.com>
 * @license MIT
 * @version 1.0
 *
 */

require __DIR__ . '/../lib/main.php';

use Scriptlog\Core\Session;

try {

    // CSPRNG-backed captcha code. random_bytes() is a cryptographically
    // secure random generator available on all supported PHP versions and
    // throws on failure, so no insecure fallback is permitted.
    $random_alpha = bin2hex(random_bytes(32));

    $captcha_code = substr($random_alpha, 0, 6);

    // Ensure the application session is available.
    class_exists('Session') ? Session::getInstance()->captcha_login = $captcha_code : throw new RuntimeException('Session class unavailable.');

    if (get_extension_funcs('gd') && function_exists('imagecreatetruecolor')) {
        // Moderate visual obfuscation: randomized canvas size, controlled
        // color palette, randomized presentation, noise and interference lines.
        // NOTE: visual obfuscation is a usability control only; the server-side
        // captcha answer check remains the actual security boundary.
        $background_palette = [
            [255, 255, 255],
            [250, 250, 248],
            [255, 250, 240],
            [245, 245, 245],
            [240, 255, 250]
        ];

        $text_palette = [
            [0, 0, 0],
            [40, 40, 40],
            [25, 25, 70]
        ];

        $width = 70 + random_int(0, 20);
        $height = 30 + random_int(0, 12);

        $layer = imagecreatetruecolor($width, $height);

        $background = imagecolorallocate($layer, ...$background_palette[array_rand($background_palette)]);
        imagefill($layer, 0, 0, $background);

        $text_color = imagecolorallocate($layer, ...$text_palette[array_rand($text_palette)]);
        $font = random_int(2, 5);
        $char_x = random_int(0, max(0, $width - 50));
        $char_y = random_int(0, max(0, $height - 20));

        imagestring($layer, $font, $char_x, $char_y, $captcha_code, $text_color);

        // Noise pixels.
        for ($i = 0; $i < 6; $i++) {
            imagesetpixel($layer, random_int(0, $width - 1), random_int(0, $height - 1), imagecolorallocate($layer, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        }

        // Interference lines.
        for ($i = 0; $i < 3; $i++) {
            imageline($layer, random_int(0, $width - 1), random_int(0, $height - 1), random_int(0, $width - 1), random_int(0, $height - 1), imagecolorallocate($layer, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        }

        // Randomized rotation of the generated image.
        $layer = imagerotate($layer, random_int(-20, 20), imagecolorallocate($layer, 255, 255, 255));

        /* Image header */
        header('Content-Type: image/jpeg');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');

        imagejpeg($layer);

        // Release image resource.
        unset($layer);
    }
} catch (\Throwable $th) {
    // Log errors internally.
    error_log(
        '[Scriptlog CAPTCHA] ' . $th->getMessage()
    );

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
    }
}
