<?php

namespace Scriptlog\Dao;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Theme class extends Dao
 *
 *
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

use Scriptlog\Core\Dao;
use Scriptlog\Core\Registry;

class ThemeDao extends Dao
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * findThemes function
     * Retrieve all themes records
     * ordered by ID (default)
     *
     * @method mixed findThemes()
     * @param integer $orderBy
     * @return mixed
     *
     */
    public function findThemes($orderBy = "ID")
    {
        $sql = "SELECT ID, theme_title, theme_desc, theme_designer, theme_directory, 
            theme_status, theme_type, license_key, license_status, license_expires_at 
            FROM tbl_themes ORDER BY '$orderBy' DESC";

        $this->setSQL($sql);

        $themes = $this->findAll([]);

        return (empty($themes)) ?: $themes;
    }

    /**
     * findTheme function
     * Retrieve single theme record
     * based on ID
     *
     * @method mixed findTheme()
     * @param integer $id
     * @param object $sanitize
     * @return mixed
     *
     */
    public function findTheme($id, $sanitize)
    {

        $sql = "SELECT ID, theme_title, theme_desc, theme_designer, 
                theme_directory, theme_status, theme_type, license_key, 
                license_status, license_expires_at 
            FROM tbl_themes WHERE ID = ?";

        $idsanitized = $this->filteringId($sanitize, $id, 'sql');

        $this->setSQL($sql);

        $themeDetail = $this->findRow([$idsanitized]);

        return (empty($themeDetail)) ?: $themeDetail;
    }

    /**
     * addTheme function
     * Insert new theme record into theme table
     *
     * @method mixed addTheme()
     * @param array $bind
     *
     */
    public function insertTheme($bind)
    {
        $this->create("tbl_themes", [
          'theme_title' => $bind['theme_title'],
          'theme_desc' => $bind['theme_desc'],
          'theme_designer' => $bind['theme_designer'],
          'theme_directory' => $bind['theme_directory'],
          'theme_type' => $bind['theme_type'] ?? 'free'
        ]);
    }

    /**
     * updateTheme function
     * update an existing theme record
     *
     * @method mixed updateTheme()
     * @param integer $id
     * @param array $bind
     *
     */
    public function updateTheme($sanitize, $bind, $ID)
    {

        $cleanId = $this->filteringId($sanitize, $ID, 'sql');
        $this->modify("tbl_themes", [
           'theme_title' => $bind['theme_title'],
           'theme_desc' => $bind['theme_desc'],
           'theme_designer' => $bind['theme_designer'],
           'theme_directory' => $bind['theme_directory']
         ], ['ID' => (int)$cleanId]);
    }

    /**
     * deleteTheme function
     * Remove theme record from theme table
     *
     * @method mixed deleteTheme()
     * @param integer $id
     * @param object $sanitize
     *
     */
    public function deleteTheme($id, $sanitize)
    {
        $cleanId = $this->filteringId($sanitize, $id, 'sql');
        $this->deleteRecord("tbl_themes", ['ID' => (int)$cleanId]);
    }

    /**
     * Check theme function
     * checking ID theme
     *
     * @method mixed checkThemeId()
     * @param integer $id
     * @param object $sanitize
     * @return numeric
     *
     */
    public function checkThemeId($id, $sanitize)
    {
        $idsanitized = $this->filteringId($sanitize, $id, 'sql');
        $sql = "SELECT ID FROM tbl_themes WHERE ID = ?";
        $this->setSQL($sql);
        $stmt = $this->checkCountValue([$idsanitized]);
        return $stmt > 0;
    }

    /**
     * Is theme active or not
     *
     * @param string $theme_title
     * @return boolen|string
     *
     */
    public function isThemeActived($theme_title)
    {

        if ($this->themeExists($theme_title) == true) {
            $sql = "SELECT theme_status FROM tbl_themes WHERE theme_title = ?";
            $this->setSQL($sql);
            $is_actived = $this->findColumn([$theme_title]);

            return (empty($is_actived)) ?: $is_actived;
        }

        return false;
    }

    /**
     * Activate theme function
     *
     * @method mixed activateTheme()
     * @param integer $id
     * @param object $sanitize
     *
     */
    public function activateTheme($id, $sanitize)
    {
        $idsanitized = $this->filteringId($sanitize, $id, 'sql');

        $this->modify("tbl_themes", ['theme_status' => 'Y'], ['ID' => (int)$idsanitized]);

        if (Registry::isKeySet('dbc')) {
            $dbc = Registry::get('dbc');
            $dbc->dbQuery("UPDATE tbl_themes SET theme_status = 'N' WHERE ID != ?", [(int)$idsanitized]);
        }
    }

    /**
     * Deactivate theme function
     *
     * @method mixed deactivateTheme()
     * @param integer $id
     * @param object $sanitize
     *
     */
    public function deactivateTheme($id, $sanitize)
    {
        $idsanitized = $this->filteringId($sanitize, $id, 'sql');

        $this->modify("tbl_themes", [
          'theme_status' => 'N'
        ], ['ID' => (int)$idsanitized]);

        $activeThemes = $this->findActiveThemes();
        if (empty($activeThemes)) {
            $blogTheme = $this->findThemeByDirectory('blog');
            if (!empty($blogTheme)) {
                $this->modify("tbl_themes", [
                    'theme_status' => 'Y'
                ], ['ID' => (int)$blogTheme['ID']]);
            }
        }
    }

    /**
     * Find theme by directory name
     *
     * @method mixed findThemeByDirectory()
     * @param string $directory
     * @return array|null
     *
     */
    public function findThemeByDirectory($directory)
    {

        $sql = "SELECT ID, theme_title, theme_desc, theme_designer, 
                theme_directory, theme_status, theme_type, license_key, 
                license_status, license_expires_at 
            FROM tbl_themes WHERE theme_directory = ?";

        $this->setSQL($sql);

        $result = $this->findRow([$directory]);

        return (!empty($result)) ? $result : [];
    }

    /**
     * Find theme type (free or premium) for a given theme ID.
     *
     * @method mixed getThemeType()
     * @param integer $id
     * @return string|boolean
     */
    public function getThemeType($id)
    {
        $sql = "SELECT theme_type FROM tbl_themes WHERE ID = ?";
        $this->setSQL($sql);
        return $this->findColumn([(int)$id]);
    }

    /**
     * Save license information for a theme.
     *
     * @method mixed saveLicense()
     * @param integer $id
     * @param array $license
     */
    public function saveLicense($id, array $license)
    {
        $bind = [
            'license_key' => $license['license_key'],
            'license_status' => $license['license_status'],
            'license_expires_at' => isset($license['license_expires_at']) ? $license['license_expires_at'] : null,
        ];

        if (isset($license['theme_type']) && in_array($license['theme_type'], ['free', 'premium'], true)) {
            $bind['theme_type'] = $license['theme_type'];
        }

        $this->modify("tbl_themes", $bind, ['ID' => (int)$id]);
    }

    /**
     * Update just the license status for a theme.
     *
     * @method mixed setLicenseStatus()
     * @param integer $id
     * @param string $status
     */
    public function setLicenseStatus($id, $status)
    {
        $this->modify("tbl_themes", [
            'license_status' => $status
        ], ['ID' => (int)$id]);
    }

    /**
     * Find active themes
     *
     * @method array findActiveThemes()
     * @return array
     *
     */
    public function findActiveThemes(): array
    {
        $sql = "SELECT ID, theme_title, theme_directory, theme_status, theme_type, 
            license_key, license_status, license_expires_at 
            FROM tbl_themes WHERE theme_status = 'Y'";

        $this->setSQL($sql);

        return $this->findAll([]);
    }

    /**
     * Total theme record function
     *
     * @method totalThemeRecords()
     * @param array $data
     * @return numeric|integer|null
     *
     */
    public function totalThemeRecords(array $data = array()): ?int
    {
        $sql = "SELECT ID FROM tbl_themes";
        $this->setSQL($sql);
        return $this->checkCountValue($data);
    }

    /**
     * Theme exists function
     * is theme exists or not
     *
     * @method themeExists()
     * @param string $theme_title
     * @return boolean
     *
     */
    public function themeExists($theme_title)
    {
        $sql = "SELECT COUNT(ID) FROM tbl_themes WHERE theme_title = ?";
        $this->setSQL($sql);
        $stmt = $this->findColumn([$theme_title]);

return ($stmt === 1) ? true : false;
    }

    /**
     * Insert a theme license activation record.
     *
     * @param array $bind
     */
    public function insertThemeLicense(array $bind)
    {
        $this->create("tbl_theme_licenses", [
            'theme_id' => $bind['theme_id'],
            'license_key' => $bind['license_key'],
            'license_status' => $bind['license_status'] ?? 'active',
            'domain' => $bind['domain'],
            'remote_license_id' => $bind['remote_license_id'] ?? null,
            'expires_at' => $bind['expires_at'] ?? null
        ]);
    }

    /**
     * Find a theme license activation by key and domain.
     *
     * @param string $licenseKey
     * @param string $domain
     * @return array|false
     */
    public function findThemeLicense($licenseKey, $domain)
    {
        $sql = "SELECT ID, theme_id, license_key, license_status, domain, 
                remote_license_id, activated_at, expires_at 
            FROM tbl_theme_licenses 
            WHERE license_key = ? AND domain = ? LIMIT 1";
        $this->setSQL($sql);
        $row = $this->findRow([$licenseKey, $domain]);
        return (!empty($row)) ? $row : false;
    }

    /**
     * Update a theme license activation record.
     *
     * @param int $id
     * @param array $bind
     */
    public function updateThemeLicense($id, array $bind)
    {
        $this->modify("tbl_theme_licenses", $bind, ['ID' => (int)$id]);
    }

    /**
     * Get all license activations for a given theme.
     *
     * @param int $themeId
     * @return array
     */
    public function findThemeLicensesByThemeId($themeId): array
    {
        $sql = "SELECT ID, theme_id, license_key, license_status, domain, 
                remote_license_id, activated_at, expires_at 
            FROM tbl_theme_licenses WHERE theme_id = ?";
        $this->setSQL($sql);
        return $this->findAll([(int)$themeId]);
    }

    /**
     * Total theme record function
     *
     * @method loadTheme()
     * @return mixed
     */
    public function loadTheme($theme_status)
    {
        $sql = "SELECT ID, theme_directory, theme_status, theme_type, license_key, 
            license_status, license_expires_at 
          FROM tbl_themes WHERE theme_status = ?";

        $this->setSQL($sql);

        $activeTheme = $this->findRow([$theme_status]);

        if (empty($activeTheme)) {
            $sqlFallback = "SELECT ID, theme_directory, theme_status, theme_type, license_key, 
              license_status, license_expires_at 
              FROM tbl_themes WHERE theme_directory = 'blog'";

            $this->setSQL($sqlFallback);
            $activeTheme = $this->findRow([]);
        }

        return $activeTheme;
    }
}
