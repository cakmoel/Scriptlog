<?php

namespace Scriptlog\Service;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class PluginService
 *
 * @category  Service class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

use Scriptlog\Core\FormValidator;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\PluginDao;

final class PluginService
{
    /**
     * Plugin's ID
     *
     * @var integer
     */
    private $plugin_id;

    /**
     * Plugin's name
     *
     * @var string
     */
    private $name;

    /**
     * Plugin's link
     *
     * @var string
     */
    private $link;

    /**
     * Plugins's Directory
     *
     * @var string
     *
     */
    private $directory;

    /**
     * Plugin's description
     *
     * @var string
     */
    private $description;

    /**
     * Plugin's status
     *
     * @var string
     */
    private $status;

    /**
     * Plugin's level
     *
     * @var string
     */
    private $level;

    /**
     * Sort
     *
     * @var string
     */
    private $sort;

    /**
     * Plugin manifest digest (sha256 over plugin.ini + all PHP files)
     *
     * @var string|null
     */
    private $manifest;

    /**
     * pluginDao
     *
     * @var object
     *
     */
    private $pluginDao;

    /**
     * $validator
     *
     * @var object
     *
     */
    private $validator;

    private $sanitize;

    /**
     * Constructor
     *
     * @param PluginDao $pluginDao
     * @param FormValidator $validator
     * @param Sanitize $sanitize
     *
     */
    public function __construct(PluginDao $pluginDao, FormValidator $validator, Sanitize $sanitize)
    {
        $this->pluginDao = $pluginDao;
        $this->validator = $validator;
        $this->sanitize = $sanitize;
    }

    public function setPluginId($pluginId)
    {
        $this->plugin_id = $pluginId;
    }

    public function setPluginName($name)
    {
        $this->name = prevent_injection($name);
    }

    public function setPluginLink($link)
    {
        $this->link = $link;
    }

    /**
     * Set the plugin directory.
     *
     * Reduced to its basename at write time so a crafted plugin.ini value
     * (e.g. '../../evil') can never escape the plugin directory. Mirrors
     * the theme handling in ThemeController; read paths already basename().
     * See plan/RCE_REMEDIATION_PLAN.md (R5).
     *
     * @param string $directory Raw plugin directory value
     * @return void
     */
    public function setPluginDirectory($directory)
    {
        $this->directory = basename((string)$directory);
    }

    public function setPluginDescription($description)
    {
        $this->description = prevent_injection($description);
    }

    public function setPluginStatus($status)
    {
        $this->status = $status;
    }

    public function setPluginLevel($level)
    {
        $this->level = $level;
    }

    public function setPluginSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * Set the plugin manifest digest.
     *
     * Only a well-formed sha256 hex digest is accepted; anything else is
     * dropped so a malformed value can never be persisted as if it were a
     * trustworthy manifest (plugin_manifest_verify() fails closed on NULL).
     *
     * @param mixed $manifest Manifest digest computed by plugin_manifest_hash()
     * @return void
     */
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @codeCoverageIgnore
     */
    public function setPluginManifest($manifest)
    {
        if (!function_exists('plugin_manifest_is_hash') && file_exists(__DIR__ . '/../utility/plugin-manifest.php')) {
            require_once __DIR__ . '/../utility/plugin-manifest.php';
        }

        $this->manifest = (function_exists('plugin_manifest_is_hash') && plugin_manifest_is_hash($manifest))
            ? strtolower($manifest)
            : null;
    }

    public function grabPlugins($orderBy = 'ID')
    {
        return $this->pluginDao->getPlugins($orderBy);
    }

    public function grabPlugin($id)
    {
        return $this->pluginDao->getPlugin($id, $this->sanitize);
    }

    public function addPlugin()
    {
        $this->validator->sanitize($this->name, 'string');
        $this->validator->sanitize($this->link, 'url');
        $this->validator->sanitize($this->description, 'string');

        if (empty($this->link)) {
            $this->link = "#";
        }

        return $this->pluginDao->insertPlugin([
          'plugin_name' => $this->name,
          'plugin_link' => $this->link,
          'plugin_directory' => $this->directory,
          'plugin_desc' => $this->description,
          'plugin_level' => $this->level,
          'plugin_manifest' => $this->manifest
        ]);
    }

    public function modifyPlugin()
    {
    }

    public function activateInstalledPlugin()
    {

        $this->validator->sanitize($this->plugin_id, 'int');

        if (file_exists(__DIR__ . '/../utility/plugin-manifest.php')) {
            require_once __DIR__ . '/../utility/plugin-manifest.php';
        }

        if (file_exists(__DIR__ . '/../utility/plugin-schema-validator.php')) {
            require_once __DIR__ . '/../utility/plugin-schema-validator.php';
        }

        if (file_exists(__DIR__ . '/../utility/plugin-audit-log.php')) {
            require_once __DIR__ . '/../utility/plugin-audit-log.php';
        }

        $data_plugin = $this->pluginDao->getPlugin($this->plugin_id, $this->sanitize);
        if (!$data_plugin) {
            direct_page('index.php?load=plugins&error=pluginNotFound', 404);
        }

        /**
         * Fail closed before any plugin code is loaded: the files on disk must
         * still match the digest recorded at install time. A missing or
         * mismatched manifest means the plugin directory was modified after
         * installation (e.g. code injected through a writable upload dir), so
         * activation is refused instead of executing it.
         */
        if (!$this->isPluginManifestValid($data_plugin)) {
            if (function_exists('plugin_audit_log')) {
                plugin_audit_log('plugin_manifest_rejected', array(
                    'plugin_id' => $this->plugin_id,
                    'plugin_name' => isset($data_plugin['plugin_name']) ? $data_plugin['plugin_name'] : '',
                    'plugin_directory' => isset($data_plugin['plugin_directory']) ? $data_plugin['plugin_directory'] : '',
                    'plugin_manifest' => isset($data_plugin['plugin_manifest']) ? $data_plugin['plugin_manifest'] : null,
                    'outcome' => 'activation_refused'
                ));
            }
            $_SESSION['error'] = 'pluginManifestInvalid';
            direct_page('index.php?load=plugins&error=pluginManifestInvalid', 404);
        }

        $plugin_path = __DIR__ . '/../../' . APP_PLUGIN . basename($data_plugin['plugin_directory']);
        $sql_path = $plugin_path . '/schema.sql';

        if (file_exists($sql_path)) {
            $sql_contents = file_get_contents($sql_path);
            if ($sql_contents === false) {
                direct_page('index.php?load=plugins&error=tableNotFound', 404);
            }

            // Split by semicolon but be careful about strings - simple split is used
            // with strict validation per statement
            $statements = explode(';', $sql_contents);
            $pluginSlug = basename($data_plugin['plugin_directory']);
            $pluginSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $pluginSlug);

            foreach ($statements as $sql) {
                $sql = trim($sql);

                if ($sql === '') {
                    continue;
                }
                if (strpos($sql, '--') === 0) {
                    continue;
                }
                if (preg_match('/^\/\*/', $sql)) {
                    continue;
                }

                if (function_exists('validate_plugin_schema_sql')) {
                    if (!validate_plugin_schema_sql($sql, $pluginSlug)) {
                        if (function_exists('plugin_audit_log')) {
                            plugin_audit_log('plugin_schema_rejected', array(
                                'plugin_id' => $this->plugin_id,
                                'plugin_name' => isset($data_plugin['plugin_name']) ? $data_plugin['plugin_name'] : '',
                                'plugin_directory' => $data_plugin['plugin_directory'],
                                'statement' => substr($sql, 0, 200)
                            ));
                        }
                        direct_page('index.php?load=plugins&error=tableNotFound', 404);
                    }
                }

                $result = false;
                if (method_exists($this->pluginDao, 'executeValidatedSchemaStatement')) {
                    $result = $this->pluginDao->executeValidatedSchemaStatement($sql);
                } else {
                    // Fallback - but still require validation passed
                    $this->pluginDao->setSQL($sql);
                    // Use execute if available
                    if (method_exists($this->pluginDao, 'execute')) {
                        $this->pluginDao->execute([]);
                        $result = true;
                    }
                }

                if (!$result) {
                    if (function_exists('plugin_audit_log')) {
                        plugin_audit_log('plugin_schema_failed', array(
                            'plugin_id' => $this->plugin_id,
                            'plugin_name' => isset($data_plugin['plugin_name']) ? $data_plugin['plugin_name'] : '',
                            'plugin_directory' => $data_plugin['plugin_directory']
                        ));
                    }
                    direct_page('index.php?load=plugins&error=tableNotFound', 404);
                }
            }
        }

        if (function_exists('plugin_audit_log')) {
            plugin_audit_log('plugin_enable_before_load', array(
                'plugin_id' => $this->plugin_id,
                'plugin_name' => isset($data_plugin['plugin_name']) ? $data_plugin['plugin_name'] : '',
                'plugin_directory' => $data_plugin['plugin_directory'],
                'plugin_manifest' => isset($data_plugin['plugin_manifest']) ? $data_plugin['plugin_manifest'] : null
            ));
        }

        enable_plugin($plugin_path);

        if (function_exists('plugin_audit_log')) {
            plugin_audit_log('plugin_activated', array(
                'plugin_id' => $this->plugin_id,
                'plugin_name' => isset($data_plugin['plugin_name']) ? $data_plugin['plugin_name'] : '',
                'plugin_directory' => $data_plugin['plugin_directory'],
                'plugin_manifest' => isset($data_plugin['plugin_manifest']) ? $data_plugin['plugin_manifest'] : null,
                'outcome' => 'activated'
            ));
        }

        $this->pluginDao->activatePlugin($this->plugin_id, $this->sanitize);

        return true;
    }

    /**
     * Check that the installed plugin files still match the recorded manifest.
     *
     * The digest is computed over `plugin.ini` plus every PHP file in the
     * plugin directory (see plugin_manifest_hash()), so any post-install edit,
     * addition or deletion invalidates it. A missing/blank/escaping
     * `plugin_directory` and an absent manifest all fail closed.
     *
     * Public so the activation guard can be exercised without triggering the
     * redirect that activateInstalledPlugin() performs on refusal.
     *
     * @param array $pluginData Plugin row from tbl_plugin (plugin_directory, plugin_manifest)
     * @return bool True when the on-disk files are byte-identical to the manifest
     */
    public function isPluginManifestValid(array $pluginData)
    {
        if (!function_exists('plugin_manifest_verify')) {
            if (file_exists(__DIR__ . '/../utility/plugin-manifest.php')) {
                require_once __DIR__ . '/../utility/plugin-manifest.php';
            }
        }

        if (!function_exists('plugin_manifest_verify')) {
            return false;
        }

        $directory = isset($pluginData['plugin_directory']) ? basename((string)$pluginData['plugin_directory']) : '';

        if ($directory === '' || $directory === '.' || $directory === '..') {
            return false;
        }

        $expected = isset($pluginData['plugin_manifest']) ? $pluginData['plugin_manifest'] : null;

        return plugin_manifest_verify(__DIR__ . '/../../' . APP_PLUGIN . $directory, $expected);
    }

    public function deactivateInstalledPlugin()
    {
        $this->validator->sanitize($this->plugin_id, 'int');

        $data_plugin = $this->pluginDao->getPlugin($this->plugin_id, $this->sanitize);
        if (!$data_plugin) {
            direct_page('index.php?load=plugins&error=pluginNotFound', 404);
        }

        if (file_exists(__DIR__ . '/../utility/plugin-audit-log.php')) {
            require_once __DIR__ . '/../utility/plugin-audit-log.php';
            if (function_exists('plugin_audit_log')) {
                plugin_audit_log('plugin_deactivated', array(
                    'plugin_id' => $this->plugin_id,
                    'plugin_name' => isset($data_plugin['plugin_name']) ? $data_plugin['plugin_name'] : '',
                    'plugin_directory' => isset($data_plugin['plugin_directory']) ? $data_plugin['plugin_directory'] : ''
                ));
            }
        }

        return $this->pluginDao->deactivatePlugin($this->plugin_id, $this->sanitize);
    }

    public function removePlugin()
    {

        $this->validator->sanitize($this->plugin_id, 'int');

        $data_plugin = $this->pluginDao->getPlugin($this->plugin_id, $this->sanitize);
        if (!$data_plugin) {
            direct_page('index.php?load=plugins&error=pluginNotFound', 404);
        }

        if (file_exists(__DIR__ . '/../utility/plugin-audit-log.php')) {
            require_once __DIR__ . '/../utility/plugin-audit-log.php';
            if (function_exists('plugin_audit_log')) {
                plugin_audit_log('plugin_deleted', array(
                    'plugin_id' => $this->plugin_id,
                    'plugin_name' => isset($data_plugin['plugin_name']) ? $data_plugin['plugin_name'] : '',
                    'plugin_directory' => isset($data_plugin['plugin_directory']) ? $data_plugin['plugin_directory'] : ''
                ));
            }
        }

        $plugin_dir = str_replace(' ', '', $data_plugin['plugin_directory']);
        $plugin_dir = basename($plugin_dir);

        $plugin_link = isset($data_plugin['plugin_link']) ? $data_plugin['plugin_link'] : '#';

        if ($plugin_link != '#' && $plugin_dir !== '' && $plugin_dir !== '.' && $plugin_dir !== '..') {
            if (is_readable(__DIR__ . '/../../' . APP_PLUGIN . $plugin_dir)) {
                delete_directory(__DIR__ . '/../../' . APP_PLUGIN . $plugin_dir);
            }
        }

        return $this->pluginDao->deletePlugin($this->plugin_id, $this->sanitize);
    }

    /**
     * checking plugin exists or not
     *
     * @param string $plugin_name
     */
    public function isPluginExists($plugin_name)
    {
        return $this->pluginDao->pluginExists($plugin_name);
    }

    /**
     * Plugin level drop down
     *
     * @param string $selected
     * @return string
     *
     */
    public function pluginLevelDropDown($selected = "")
    {
        return $this->pluginDao->dropDownPluginLevel($selected);
    }

    /**
     * Total plugin records
     *
     * @param array $data
     * @return integer
     */
    public function totalPlugins(array $data = []): ?int
    {
        return $this->pluginDao->totalPluginRecords($data);
    }
}
