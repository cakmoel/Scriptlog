<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * HelloWorld Plugin
 *
 * Sample plugin demonstrating the plugin system structure
 *
 * @category Plugin
 * @author Your Name
 * @license MIT
 * @version 1.0.0
 *
 */
class HelloWorldPlugin
{
    /**
     * Plugin directory
     */
    private $pluginDir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pluginDir = dirname(__FILE__);
        $this->registerHooks();
    }

    /**
     * Register plugin hooks using clip() system
     */
    private function registerHooks()
    {
        clip('clip_Hello World', null, function ($content = '') {
            return $this->frontendDisplay($content);
        });

        clip('clip_Hello World_admin', null, function () {
            return $this->adminPage();
        });
    }

    /**
     * Initialize plugin
     * Called when plugin is activated
     */
    public function activate()
    {
        return true;
    }

    /**
     * Deactivate plugin
     * Called when plugin is deactivated
     */
    public function deactivate()
    {
        return true;
    }

    /**
     * Uninstall plugin
     * Called when plugin is deleted
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * Plugin admin page
     * Render plugin settings page in admin panel
     */
    public function adminPage()
    {
        return '<div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Hello World Plugin</h3>
            </div>
            <div class="box-body">
                <p>Welcome to the Hello World plugin!</p>
                <p>This is a sample plugin that demonstrates the plugin system.</p>
                <p>Status: <span class="label label-success">Active</span></p>
            </div>
        </div>';
    }

    /**
     * Frontend display
     * Display content on frontend
     */
    public function frontendDisplay($content = '')
    {
        return $content . '<div class="hello-world-plugin" style="padding: 15px; margin: 20px 0; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
            <h4>Hello World Plugin</h4>
            <p>This content is rendered by the Hello World plugin!</p>
        </div>';
    }

    /**
     * Get plugin info
     *
     * @return array
     */
    public function getInfo()
    {
        $iniFile = $this->pluginDir . DIRECTORY_SEPARATOR . 'plugin.ini';

        if (file_exists($iniFile)) {
            return parse_ini_file($iniFile);
        }

        return [];
    }
}
