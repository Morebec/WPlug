<?php

namespace WPlug;

use Symfony\Component\Yaml\Yaml;
use WPlug\ArrayUtils;
use WPlug\Application;
use WPlug\AdminMenuBuilder;

/**
 * Plugin
 */
class Plugin
{
    /**
     * YAML Plugin Config
     */
    private $configFilePath;

    /**
     * Configuration data
     */
    private $config;

    /**
     * Name of the plugin
     */
    private $name;

    function __construct()
    {

        // LOAD FLAT FILE CONFIG
        $config = $this->loadConfig();

        // Register plugin with application object
        $this->getApplication()->registerPlugin($this);

        // Add actions
        foreach ($this->getSubscribedActions() as $action => $callbacks) {
            if(!is_array($callbacks)) $callbacks = array($callbacks);
            foreach ($callbacks as $callback) {
                add_action($action, array($this, $callback));
            }
        }

        // Add Filters
        foreach ($this->getSubscribedFilters() as $filter => $callbacks) {
            if(!is_array($callbacks)) $callbacks = array($callbacks);
            foreach ($callbacks as $callback) {
                add_filter($filter, array($this, $callback));
            }
        }

        // De/Activation & Un/Install hooks
        register_activation_hook($this->getPath(), array($this, 'onActivate'));
        register_deactivation_hook($this->getPath(), array($this, 'onDeactivate'));
        register_uninstall_hook($this->getPath(), array('$this', 'onUninstall'));


        // Build Menu
        if ($this->isAdmin()) {
            add_action('admin_menu', array($this, 'buildAdminMenus'));
        }

        // styles
        add_action('admin_init', array($this, 'enqueueAdminStyles'));
        add_action('admin_init', array($this, 'enqueueAdminScripts'));
    }

    public function getAdminStyles() {
        return array();
    }

    public function getAdminScripts()
    {
        return array();
    }

    public function getFrontEndStyles()
    {
        return array();
    }

    public function getFrontEndScripts()
    {
        return array();
    }

    public function enqueueAdminStyles()
    {
        $styles = $this->getAdminStyles();
        $this->enqueueStyles($styles);
    }

    public function enqueueAdminScripts()
    {
        $scripts = $this->getAdminScripts();
        $this->enqueueScripts($scripts);
    }

    public function enqueueFrontEndStyles()
    {
        $styles = $this->getFrontEndStyles();
        $this->enqueueStyles($styles);
    }

    public function enqueueFrontEndScripts()
    {
        $scripts = $this->getFrontEndScripts();
        $this->enqueueScripts($scripts);
    }

    private function enqueueStyles($styles)
    {
        $assetsDirUrl = $this->getAssetsDirUrl();
        $namespace = $this->getNamespace();
        foreach ($styles as $style) {
            wp_enqueue_style(
                $namespace . '_' . $style['name'], 
                $assetsDirUrl . '/' . $style['src'] 
            );
        }
    }

    private function enqueueScripts($scripts) {
        $assetsDir = $this->getAssetsDirUrl();
        foreach ($scripts as $script) {
            wp_enqueue_script(
                $script['name'], 
                $assetsDirUrl . '/' . $script['src'] 
            );
        }
    }

    public function buildAdminMenus() {
        $menus = $this->getAdminMenus();
        if ($menus) {
            foreach ($menus as $menu) {
                add_menu_page(
                    $menu['pageTitle'],                                              /* $page_title, */
                    $menu['title'],                                                  /* $menu_title, */
                    ArrayUtils::getOrDefault($menu, 'capability', 'manage_options'), /* $capability, */
                    ArrayUtils::getOrDefault($menu, 'slug', $this->getName()),       /* $menu_slug, */
                    array($this, ArrayUtils::getOrDefault($menu, 'function', '')),   /* function */
                    ArrayUtils::getOrDefault($menu, 'icon', ''),                     /* menu_icon */
                    ArrayUtils::getOrDefault($menu, 'position', null)                /* menu_positiojn */
                );
            }
        }
    }

    /**
     * Renders a template
     */
    public function renderView($viewPath, $ctx = array()) {
       return $this->getApplication()->renderView(
        '@' . $this->getNamespace() . '/' . $viewPath, $ctx);
    }

    /**
     * Loads the configuration of the plugin
     */
    public function loadConfig()
    {
        $config = Yaml::parse($this->getConfigFilePath());
        $config = $config ? $config : [];

        // Generate constants from config files
        $PREFIX = strtoupper(ArrayUtils::getOrDefault($config, 'namespace', ''));

        if (array_key_exists('version', $config)) {
            define($PREFIX . 'VERSION', $config['version']);
        }

        // dynamic constants
        if (array_key_exists('constants', $config)) {
            foreach ($config['constants'] as $key => $value) {
                define($PREFIX . strtoupper($key), $value);
            }
        }

        $this->config = $config;
        return $this->config;
    }

    /**
     * Returns the configs of the plugin
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * Returns this plugins config file path
     */
    public function getConfigFilePath()
    {
        if (!$this->configFilePath) {
            $this->configFilePath = explode(".", $this->getPath())[0] . '.yaml';
        }

        return $this->configFilePath;
    }

    public function getSubscribedActions() {
        return array();
    }

    public function getSubscribedFilters() {
        return array();
    }

    /**
     * Options are key => value pairs
     */
    public function getSubscribedOptions() {
        return array();
    }

    /**
     * Returns an option's value
     */
    public function getOptionValue($optionName) {
        get_option($optionName);
    }

    /**
     * Sets the value of an option
     */
    public function setOptionValue($optionName, $value) {
        update_option($optionName, $value);
    }

    /**
     * Deletes an option with specific name
     */
    public function deleteOption($optionName) {
        delete_option($optionName);
    }

    /**
     * Deletes all options subscribed by this plugin
     */
    public function deleteOptions() {
        foreach ($this->getSubscribedOptions() as $optionName => $value) {
            $this->deleteOption($optionName);
        }
    }

    /**
     * Returns the admin menus hierarchy for this menu
     */
    public function getAdminMenus()
    {
        return array();
    }

    /**
     * Called when the plugin is activated
     */
    public function onActivate() {
        // Create options
        foreach ($this->getSubscribedOptions() as $option => $value) {
            add_option($option, $value);
        }
    }

    /**
     * Called when the plugin is deactivated
     */
    public function onDeactivate() {
        $this->deleteOptions();
    }

    /**
     * Called when the plugin is uninstalled
     */
    public function onUninstall() {
    }

    /**
     * Returns the application object
     */
    public function getApplication() {
        return Application::instance();
    }

    /**
     * Returns the name of the plugin as lower case
     */
    public function getName() {
        return pathinfo($this->getPath())['filename'];
    }

    /**
     * Returns the namespace of the plugin. 
     * This is used for many things such as the redering of template files
     * or the loading of files. By default will upper case the name of the plugin 
     * and replace all hyphens by underscores.
     * 
     * @return string namespace
     */
    public function getNamespace()
    {
        $defaultStrategy = strtoupper(str_replace("‐", ",", $this->getName()));
        $config = $this->getConfig();
        return ArrayUtils::getOrDefault($config, 'namespace', $defaultStrategy);
    }

    /**
     * Returns the version number of the plugin
     */
    public function getVersion() {
        $pluginData = get_plugin_data($this->getPath());
        return $pluginData['Version'];
    }

    /**
     * Returns the directory of the plugin
     */
    public function getDirectory() {
        return pathinfo($this->getPath())['dirname'];
    }

    /**
     * Returns the name of the directgory containing this plugin
     * @return string name of the drectory
     */
    public function getDirectoryName()
    {
        return pathinfo($this->getDirectory())['basename'];
    }

    /**
     * Returns the full path to the plugin PHP file
     */
    public function getPath() {
        $reflection = new \ReflectionClass(get_class($this));
        return $reflection->getFileName();
    }

    /**
     * Returns the plugin's full URL
     * @return string url
     */
    public function getUrl()
    {
        return plugins_url($this->getDirectoryName());
    }

    /**
     * Returns the full path to the directory containing views
     */
    public function getViewsDirPath() {
        $config = $this->getConfig();
        $dir = ArrayUtils::getOrDefault($config, 'views_directory', 'views');
        return $this->getDirectory() . DIRECTORY_SEPARATOR . $dir;
    }

    /**
     * Returns the simple name of the assets directory
     * @return string name
     */
    public function getAssetsDirName()
    {
        $config = $this->getConfig();
        return ArrayUtils::getOrDefault($config, 'assets_directory', 'assets');
    }

    /**
     * Returns the Assets directory path
     * @return string full path
     */
    public function getAssetsDirPath() {
        return $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getAssetsDirName();
    }

    /**
     * Returns the Url of the assets directory path
     * @return [type] [description]
     */
    public function getAssetsDirUrl() {
        return $this->getUrl() . '/' . $this->getAssetsDirName();
    }

    /**
     * Indicates if the current page is an admin page
     */
    public function isAdmin() {
        return is_admin();
    }
}
