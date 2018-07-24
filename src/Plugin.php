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
    private $configFielPath;

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
        //
        // Register plugin with application object
        $this->getApplication()->registerPlugin($this);

        // LOAD FLAT FILE CONFIG
        $config = $this->loadConfig();

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
        add_action($action, array( $this, $callback ));
        register_activation_hook($this->getPath(), array($this, 'onActivate'));
        register_deactivation_hook($this->getPath(), array($this, 'onDeactivate'));
        register_uninstall_hook($this->getPath(), array($this, 'onUninstall'));


        // Build Menu
        if ($this->isAdmin()) {
            add_action('admin_menu', array($this, 'buildAdminMenus'));
        }
    }

    public function buildAdminMenus() {
        $menus = $this->getAdminMenus();
        if ($menus) {
            $builder = new AdminMenuBuilder();
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
       return $this->getApplication()->renderView($viewPath, $ctx);
    }

    /**
     * Loads the configuration of the plugin
     */
    public function loadConfig()
    {
        $this->config = Yaml::parseFile($this->getConfigFilePath());
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
     * Returns the name of the plugin
     */
    public function getName() {
        return pathinfo($this->getPath())['filename'];
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
     * Returns the full path to the plugin PHP file
     */
    public function getPath() {
        $reflection = new \ReflectionClass(get_class($this));
        return $reflection->getFileName();
    }

    /**
     * Returns the full path to the directory containing views
     */
    public function getViewsDirPath() {
        return $this->getDirectory() . DIRECTORY_SEPARATOR . 'views';
    }

    /**
     * Indicates if the current page is an admin page
     */
    public function isAdmin() {
        return is_admin();
    }
}
