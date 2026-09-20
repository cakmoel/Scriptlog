<?php

namespace Scriptlog\Service;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * ThemeLicenseService
 *
 * HTTP client that validates premium theme licenses against the MyBlog
 * license API (https://myblog.local/api/v2/licenses/*). Requests are
 * HMAC-signed using the per-product license_secret embedded in the
 * theme's theme.ini file.
 *
 * @category Class ThemeLicenseService
 * @author   MyBlog
 * @license  MIT
 * @version  1.0.0
 * @since    Since Release 1.0.0
 */
class ThemeLicenseService
{
    /**
     * Default license API base URL.
     *
     * @var string
     */
    protected $apiBaseUrl;

    /**
     * Timeout in seconds for API calls.
     *
     * @var int
     */
    private $timeout;

    /**
     * Constructor
     *
     * @param string|null $apiBaseUrl Override the license API base URL.
     * @param int         $timeout    Request timeout in seconds.
     */
    public function __construct($apiBaseUrl = null, $timeout = 10)
    {
        $this->apiBaseUrl = $apiBaseUrl ?? 'https://myblog.local';
        $this->timeout = (int)$timeout;
    }

    /**
     * Read the theme.ini configuration for a given theme directory.
     *
     * @param string $themeDirectory
     * @return array
     */
    public function readThemeConfig($themeDirectory)
    {
        $iniFile = APP_ROOT . APP_THEME . basename($themeDirectory) . DIRECTORY_SEPARATOR . 'theme.ini';

        if (!file_exists($iniFile)) {
            return [];
        }

        $config = parse_ini_file($iniFile, true);

        return (is_array($config)) ? $config : [];
    }

    /**
     * Determine whether a theme is a premium theme.
     *
     * @param string $themeDirectory
     * @return bool
     */
    public function isPremiumTheme($themeDirectory)
    {
        $config = $this->readThemeConfig($themeDirectory);

        if (isset($config['info']['theme_type']) && $config['info']['theme_type'] === 'premium') {
            return true;
        }

        return false;
    }

    /**
     * Activate a license key against the MyBlog license API.
     *
     * POST /api/v2/licenses/activate/{product_id}
     * Body: {"license_key": "...", "domain": "..."}
     *
     * @param string $licenseKey      The license key entered by the admin.
     * @param string $domain          The domain where the theme is installed.
     * @param string $productSecret   Per-product license_secret embedded in the theme.
     * @param int    $productId       MyBlog store product ID for this theme.
     * @return array{success: bool, message: string, data?: array}
     */
    public function activate($licenseKey, $domain, $productSecret, $productId)
    {
        $body = json_encode([
            'license_key' => $licenseKey,
            'domain' => $domain,
        ]);

        $url = rtrim($this->apiBaseUrl, '/') . '/api/v2/licenses/activate/' . (int)$productId;

        return $this->post($url, $body, $productSecret);
    }

    /**
     * Validate an already-activated license.
     *
     * POST /api/v2/licenses/validate
     * Body: {"license_key": "...", "product_id": N}
     *
     * @param string $licenseKey
     * @param int    $productId
     * @param string $productSecret
     * @return array{success: bool, message: string, data?: array}
     */
    public function validate($licenseKey, $productId, $productSecret)
    {
        $body = json_encode([
            'license_key' => $licenseKey,
            'product_id' => (int)$productId,
        ]);

        $url = rtrim($this->apiBaseUrl, '/') . '/api/v2/licenses/validate';

        return $this->post($url, $body, $productSecret);
    }

    /**
     * Deactivate a theme license, releasing one activation slot.
     *
     * POST /api/v2/licenses/deactivate
     * Body: {"license_key": "...", "product_id": N}
     *
     * @param string $licenseKey
     * @param int    $productId
     * @param string $productSecret
     * @return array{success: bool, message: string, data?: array}
     */
    public function deactivate($licenseKey, $productId, $productSecret)
    {
        $body = json_encode([
            'license_key' => $licenseKey,
            'product_id' => (int)$productId,
        ]);

        $url = rtrim($this->apiBaseUrl, '/') . '/api/v2/licenses/deactivate';

        return $this->post($url, $body, $productSecret);
    }

    /**
     * Send an HMAC-signed POST request to the license API.
     *
     * Signature scheme (mirrors MyBlog ApiAuthService::verifyHmacSignature):
     *   payload = "{timestamp}.{nonce}.{body}"
     *   signature = hash_hmac('sha256', payload, secret)
     *
     * @param string $url
     * @param string $body
     * @param string $secret
     * @return array{success: bool, message: string, data?: array}
     */
    protected function post($url, $body, $secret)
    {
        $timestamp = (string)time();
        $nonce = bin2hex(random_bytes(16));
        $payload = $timestamp . '.' . $nonce . '.' . $body;
        $signature = hash_hmac('sha256', $payload, $secret);

        $headers = [
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json',
            'X-Request-Timestamp: ' . $timestamp,
            'X-Request-Nonce: ' . $nonce,
            'X-Request-Signature: ' . $signature,
        ];

        if (function_exists('curl_init')) {
            return $this->postCurl($url, $body, $headers);
        }

        return $this->postStream($url, $body, $headers);
    }

    /**
     * Send the request using cURL.
     *
     * @param string $url
     * @param string $body
     * @param array  $headers
     * @return array
     */
    private function postCurl($url, $body, array $headers)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $ch = null;

        if ($response === false) {
            return [
                'success' => false,
                'message' => 'Unable to reach the license server: ' . $curlError,
            ];
        }

        return $this->parseResponse($response, $httpCode);
    }

    /**
     * Send the request using stream context (fallback when cURL is absent).
     *
     * @param string $url
     * @param string $body
     * @param array  $headers
     * @return array
     */
    private function postStream($url, $body, array $headers)
    {
        $headerLine = implode("\r\n", $headers);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headerLine,
                'content' => $body,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return [
                'success' => false,
                'message' => 'Unable to reach the license server.',
            ];
        }

        $httpCode = 200;
        if (isset($http_response_header[0])) {
            if (preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
                $httpCode = (int)$m[1];
            }
        }

        return $this->parseResponse($response, $httpCode);
    }

    /**
     * Parse the JSON response from the license API.
     *
     * @param string $response
     * @param int    $httpCode
     * @return array
     */
    protected function parseResponse($response, $httpCode)
    {
        $data = json_decode($response, true);

        if (!is_array($data)) {
            return [
                'success' => false,
                'message' => 'Invalid response from the license server.',
            ];
        }

        $isError = $httpCode >= 400 || (isset($data['status']) && $data['status'] === 'error');

        if ($isError) {
            $message = '';
            if (isset($data['error']['message'])) {
                $message = $data['error']['message'];
            } elseif (isset($data['message'])) {
                $message = $data['message'];
            }

            if (empty($message)) {
                $message = 'License validation failed.';
            }

            if (isset($data['errors']) && is_array($data['errors'])) {
                $message .= ' ' . implode(' ', array_map('strval', $data['errors']));
            }
            return [
                'success' => false,
                'message' => $message,
                'data' => $data,
                'http_code' => $httpCode,
            ];
        }

        return [
            'success' => true,
            'message' => 'License validated successfully.',
            'data' => $data,
            'http_code' => $httpCode,
        ];
    }
}