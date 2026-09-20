<?php

namespace Scriptlog\Service;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * class ThemeService
 *
 * @category Class ThemeService
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    Since Release 1.0.0
 *
 */

use Scriptlog\Core\FormValidator;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\ThemeDao;
use Scriptlog\Service\ThemeLicenseService;

class ThemeService
{
    /**
     * Theme ID
     *
     * @var integer
     */
    private $theme_id;

    /**
     * Theme title
     *
     * @var string
     */
    private $theme_title;

    /**
     * Theme description
     *
     * @var string
     */
    private $theme_description;

    /**
     * Theme designer
     *
     * @var string
     */
    private $theme_designer;

    /**
     * Theme directory
     *
     * @var string
     */
    private $theme_directory;

    /**
     * Theme Status
     *
     * @var string
     */
    private $theme_status;

    /**
     * ThemeDao Data Access Object for themes
     *
     * @var object
     */
    private $themeDao;

    /**
     * FormValidator Validator for input data
     *
     * @var object
     */
    private $validator;

    /** @var Sanitize Sanitization utility */
    private $sanitize;

    /** @var ThemeLicenseService|null License validation client for premium themes */
    private $licenseService;

    /**
     * Constructor
     *
     * @param ThemeDao $themeDao
     * @param FormValidator $validator
     * @param Sanitize $sanitize
     */
    public function __construct(ThemeDao $themeDao, FormValidator $validator, Sanitize $sanitize)
    {
        $this->themeDao = $themeDao;
        $this->validator = $validator;
        $this->sanitize = $sanitize;
        $this->licenseService = (class_exists('ThemeLicenseService')) ? new ThemeLicenseService() : null;
    }

    /**
     * Set Theme ID
     *
     * @param int $theme_id
     */
    public function setThemeId($theme_id)
    {
        $this->theme_id = $theme_id;
    }

    /**
     * Set Theme Title
     *
     * @param string $theme_title
     */
    public function setThemeTitle($theme_title)
    {
        $this->theme_title = $theme_title;
    }

    /**
     * Set Theme Description
     *
     * @param string $theme_description
     */
    public function setThemeDescription($theme_description)
    {
        $this->theme_description = $theme_description;
    }

    /**
     * Set Theme Designer
     *
     * @param string $theme_designer
     */
    public function setThemeDesigner($theme_designer)
    {
        $this->theme_designer = $theme_designer;
    }

    /**
     * Set Theme Directory
     *
     * @param string $theme_directory
     */
    public function setThemeDirectory($theme_directory)
    {
        $this->theme_directory = $theme_directory;
    }

    /**
     * Set Theme Status
     *
     * @param string $theme_status
     */
    public function setThemeStatus($theme_status)
    {
        $this->theme_status = $theme_status;
    }

    /**
     * Retrieve themes
     *
     * @param string $orderBy Column to order by
     * @return array
     */
    public function grabThemes($orderBy = 'ID')
    {
        return $this->themeDao->findThemes($orderBy);
    }

    /**
     * Retrieve a single theme
     *
     * @param int $id
     * @return array
     */
    public function grabTheme($id)
    {
        return $this->themeDao->findTheme($id, $this->sanitize);
    }

    /**
     * Add a new theme
     *
     */
    public function addTheme()
    {
        $this->validator->sanitize($this->theme_title, 'string');
        $this->validator->sanitize($this->theme_description, 'string');
        $this->validator->sanitize($this->theme_designer, 'string');

        return $this->themeDao->insertTheme([
          'theme_title' => $this->theme_title,
          'theme_desc' => $this->theme_description,
          'theme_designer' => $this->theme_designer,
          'theme_directory' => $this->theme_directory
        ]);
    }

    /**
     * Modify an existing theme
     *
     */
    public function modifyTheme()
    {
        $this->validator->sanitize($this->theme_id, 'int');
        $this->validator->sanitize($this->theme_title, 'string');
        $this->validator->sanitize($this->theme_description, 'string');
        $this->validator->sanitize($this->theme_directory, 'string');

        $theme_config = [];
        $theme_config['info']['theme_name'] = $this->theme_title;
        $theme_config['info']['theme_designer'] = $this->theme_designer;
        $theme_config['info']['theme_description'] = $this->theme_description;
        $theme_config['info']['theme_directory'] = $this->theme_directory;

        write_ini(__DIR__ . '/../../' . APP_THEME . $this->theme_directory . DIRECTORY_SEPARATOR . 'theme.ini', $theme_config);

        return $this->themeDao->updateTheme($this->sanitize, [

              'theme_title' => $this->theme_title,
              'theme_desc' => $this->theme_description,
              'theme_designer' => $this->theme_designer,
              'theme_directory' => $this->theme_directory

              ], $this->theme_id);
    }

    /**
     * Activate an installed theme
     *
     */
    public function activateInstalledTheme()
    {
        $this->validator->sanitize($this->theme_id, 'int');
        return $this->themeDao->activateTheme($this->theme_id, $this->sanitize);
    }

    /**
     * Activate an installed theme, validating the license key first.
     *
     * Free themes activate immediately without a license key.
     * Premium themes require a valid license key that is checked against
     * the MyBlog license API before activation is allowed.
     *
     * @param string|null $licenseKey
     * @return array{success: bool, message: string}
     */
    public function activateInstalledThemeWithLicense($licenseKey = null)
    {
        $this->validator->sanitize($this->theme_id, 'int');

        $theme = $this->themeDao->findTheme($this->theme_id, $this->sanitize);
        if (!$theme) {
            return ['success' => false, 'message' => 'Theme not found.'];
        }

        $themeConfig = ($this->licenseService instanceof ThemeLicenseService)
            ? $this->licenseService->readThemeConfig($theme['theme_directory'])
            : [];

        $themeType = 'free';
        if (isset($theme['theme_type']) && $theme['theme_type'] === 'premium') {
            $themeType = 'premium';
        } elseif (isset($themeConfig['info']['theme_type']) && $themeConfig['info']['theme_type'] === 'premium') {
            $themeType = 'premium';
        }

        // Free themes do not require a license key
        if ($themeType !== 'premium') {
            $this->themeDao->saveLicense($this->theme_id, [
                'license_key' => null,
                'license_status' => null,
                'license_expires_at' => null,
                'theme_type' => 'free',
            ]);
            $this->themeDao->activateTheme($this->theme_id, $this->sanitize);
            return ['success' => true, 'message' => 'Theme activated.'];
        }

        // Premium themes require a license key
        if (empty($licenseKey)) {
            return ['success' => false, 'message' => 'This premium theme requires a license key. Please enter your license key to activate it.'];
        }

        if (!($this->licenseService instanceof ThemeLicenseService)) {
            return ['success' => false, 'message' => 'License validation is not available.'];
        }

        $productSecret = isset($themeConfig['info']['license_secret']) ? (string)$themeConfig['info']['license_secret'] : '';
        $productId = isset($theme['license_product_id']) ? (int)$theme['license_product_id'] : 0;

        if ($productId === 0 && isset($themeConfig['info']['product_id'])) {
            $productId = (int)$themeConfig['info']['product_id'];
        }

        if ($productId === 0) {
            return ['success' => false, 'message' => 'This premium theme is missing its product identifier.'];
        }

        if (empty($productSecret)) {
            return ['success' => false, 'message' => 'This premium theme is missing its license secret.'];
        }

        $domain = (isset($_SERVER['HTTP_HOST'])) ? preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']) : 'localhost';

        $activation = $this->licenseService->activate((string)$licenseKey, $domain, $productSecret, $productId);

        if (empty($activation['success'])) {
            return ['success' => false, 'message' => $activation['message']];
        }

        $expiresAt = null;
        $activationData = $activation['data'] ?? [];
        if (isset($activationData['data']['attributes']['expires_at']) && !empty($activationData['data']['attributes']['expires_at'])) {
            $expiresAt = $activationData['data']['attributes']['expires_at'];
        } elseif (isset($activationData['data']['expires_at']) && !empty($activationData['data']['expires_at'])) {
            $expiresAt = $activationData['data']['expires_at'];
        } elseif (isset($activationData['expires_at']) && !empty($activationData['expires_at'])) {
            $expiresAt = $activationData['expires_at'];
        }

        $this->themeDao->saveLicense($this->theme_id, [
            'license_key' => (string)$licenseKey,
            'license_status' => 'active',
            'license_expires_at' => $expiresAt,
            'theme_type' => 'premium',
        ]);

        // Record the activation in tbl_theme_licenses
        $remoteLicenseId = null;
        if (isset($activationData['data']['attributes']['license_id'])) {
            $remoteLicenseId = (int)$activationData['data']['attributes']['license_id'];
        } elseif (isset($activationData['data']['license_id'])) {
            $remoteLicenseId = (int)$activationData['data']['license_id'];
        } elseif (isset($activationData['license_id'])) {
            $remoteLicenseId = (int)$activationData['license_id'];
        }

        $this->themeDao->insertThemeLicense([
            'theme_id' => $this->theme_id,
            'license_key' => (string)$licenseKey,
            'license_status' => 'active',
            'domain' => $domain,
            'remote_license_id' => $remoteLicenseId,
            'expires_at' => $expiresAt,
        ]);

        $this->themeDao->activateTheme($this->theme_id, $this->sanitize);

        return ['success' => true, 'message' => 'Premium theme activated with a valid license.'];
    }

    /**
     * Deactivate an installed theme
     *
     */
    public function deactivateInstalledTheme()
    {
        $this->validator->sanitize($this->theme_id, 'int');

        $theme = $this->themeDao->findTheme($this->theme_id, $this->sanitize);

        // Best-effort: notify MyBlog so the license's activation slot is released,
        // but never block the local (DB) deactivation on network/server failure.
        $this->deactivateThemeLicense($theme);

        return $this->themeDao->deactivateTheme($this->theme_id, $this->sanitize);
    }

    /**
     * Notify the MyBlog license server that a premium theme is being
     * deactivated, releasing one activation slot for the license key.
     *
     * Runs best-effort: failures are swallowed so the admin can still
     * deactivate the theme locally. Only premium themes with a stored
     * license key and product identifier trigger a remote call.
     *
     * @param array|null $theme
     * @return void
     */
    private function deactivateThemeLicense($theme)
    {
        if (!is_array($theme)) {
            return;
        }

        $isPremium = isset($theme['theme_type']) && $theme['theme_type'] === 'premium';
        $licenseKey = isset($theme['license_key']) ? (string)$theme['license_key'] : '';

        if (!$isPremium || $licenseKey === '') {
            return;
        }

        if (!($this->licenseService instanceof ThemeLicenseService)) {
            return;
        }

        try {
            $themeConfig = $this->licenseService->readThemeConfig($theme['theme_directory'] ?? '');
            $productSecret = isset($themeConfig['info']['license_secret'])
                ? (string)$themeConfig['info']['license_secret']
                : '';

            $productId = isset($theme['license_product_id']) ? (int)$theme['license_product_id'] : 0;
            if ($productId === 0 && isset($themeConfig['info']['product_id'])) {
                $productId = (int)$themeConfig['info']['product_id'];
            }

            if ($productSecret === '' || $productId === 0) {
                return;
            }

            $this->licenseService->deactivate($licenseKey, $productId, $productSecret);
        } catch (\Throwable $e) {
            // Swallow — local deactivation always succeeds.
        }
    }

    /**
     * Remove a theme
     *
     * @return bool
     */
    public function removeTheme()
    {
        $this->validator->sanitize($this->theme_id, 'int');

        $data_theme = $this->themeDao->findTheme($this->theme_id, $this->sanitize);
        if (!$data_theme) {
            $_SESSION['error'] = "themeNotFound";
            direct_page('index.php?load=templates&error=themeNotFound', 404);
        }

        $path_theme = __DIR__ . '/../../' . APP_THEME . $data_theme['theme_directory'] . DIRECTORY_SEPARATOR;

        if ($data_theme['theme_directory'] !== '') {
            delete_directory($path_theme);
        }

        return $this->themeDao->deleteTheme($this->theme_id, $this->sanitize);
    }

    /**
     * isThemeExists
     *
     * @param string $theme_title
     * @return boolean
     */
    public function isThemeExists($theme_title)
    {
        return $this->themeDao->themeExists($theme_title);
    }

    /**
     * totalThemes
     *
     * @param array $data
     * @return integer|numeric|null
     */
    public function totalThemes(array $data = []): ?int
    {
        return $this->themeDao->totalThemeRecords($data);
    }
}
