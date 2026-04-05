<?php

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
class PluginService
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
     * @param object $pluginDao
     * @param object $validator
     * @param object $sanitize
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

    public function setPluginDirectory($directory)
    {
        $this->directory = $directory;
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
          'plugin_level' => $this->level
        ]);
    }

    public function modifyPlugin()
    {
    }

    public function activateInstalledPlugin()
    {

        $activation = false;

        $this->validator->sanitize($this->plugin_id, 'int');

        if (!($data_plugin = $this->pluginDao->getPlugin($this->plugin_id, $this->sanitize))) {
            $activation = false;
            direct_page('index.php?load=plugins&error=pluginNotFound', 404);
        }

        $plugin_path = __DIR__ . '/../../' . APP_PLUGIN . basename($data_plugin['plugin_directory']);
        $sql_path = $plugin_path . '/schema.sql';

        if (file_exists($sql_path)) {
            $sql_contents = file_get_contents($sql_path);
            $sql_contents = explode(";", $sql_contents);

            foreach ($sql_contents as $sql) {
                $sql = trim($sql);

                if (empty($sql) || strpos($sql, '--') === 0) {
                    continue;
                }

                $result = '';
                $result = $this->pluginDao->setSQL($sql);

                if (!$result) {
                    $activation = false;
                    direct_page('index.php?load=plugins&error=tableNotFound', 404);
                }
            }
        }

        enable_plugin($plugin_path);

        $this->pluginDao->activatePlugin($this->plugin_id, $this->sanitize);

        return $activation = true;
    }

    public function deactivateInstalledPlugin()
    {
        $this->validator->sanitize($this->plugin_id, 'int');

        if (!$data_plugin = $this->pluginDao->getPlugin($this->plugin_id, $this->sanitize)) {
            direct_page('index.php?load=plugins&error=pluginNotFound', 404);
        }

        return $this->pluginDao->deactivatePlugin($this->plugin_id, $this->sanitize);
    }

    public function removePlugin()
    {

        $this->validator->sanitize($this->plugin_id, 'int');

        if (!$data_plugin = $this->pluginDao->getPlugin($this->plugin_id, $this->sanitize)) {
            direct_page('index.php?load=plugins&error=pluginNotFound', 404);
        }

        $plugin_dir = str_replace(' ', '', $data_plugin['plugin_directory']);

        $plugin_link = $data_plugin['plugin_link'];

        if ($plugin_link != '#') {
            if (is_readable(__DIR__ . '/../../' . APP_PLUGIN . $plugin_dir)) {
                delete_directory(__DIR__ . '/../../' . APP_PLUGIN . $plugin_dir);

                unlink(__DIR__ . '/../../' . APP_PLUGIN . basename($plugin_dir . 'php'));
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
