<?php

namespace WPlug;

use Symfony\Component\HttpFoundation\Request;
use WPlug\TwigService;

/**
 * Application class
 */
class Application
{
    const VERSION = '0.0.1';

    /**
     * Instance for singleton
     */
    private static $instance;

    /**
     * List of WPlug plugins
     */
    private $wplugPlugins;

    /**
     * Current request
     */
    private $request;

    /**
     * Twig service
     */
    private $twigService;

    private function __construct()
    {
        $this->wplugPlugins = array();
        $this->twigService = new TwigService();
    }

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new Application();
        }
        return self::$instance;
    }

    /**
     * Renders a template
     */
    public function renderView($template, $ctx) {
        return $this->twigService->render($template, $ctx);
    }

    /**
     * Returns the current request
     */
    public function getRequest() {
        if ($this->request === null) {
            $this->request = Request::createFromGlobals();
        }

        return $this->request;
    }

    /**
     * Returns all plugins
     */
    public function getPlugins() {
        // $pluginData = get_plugin_data( __FILE__ );
        $activePlugins = get_option('active_plugins');
        $plugins = get_plugins();
        return $plugins;
    }

    /**
     * Returns the configuration object of a plugin
     * Must be a WPlug Plugin
     */
    public function getPluginConfig($pluginName) {
        $plugin = $this->wplugPlugins[$pluginName];
        if ($plugin === null) {
            return null;
        }
        return $plugin->getConfig();
    }

    /**
     * Registers a plugin with this application object,
     * so the plugin can be recognised as a WPlug plugin
     */
    public function registerPlugin($wplugPlugin) {
        $name = $wplugPlugin->getName();
        $this->wplugPlugins[$name] = $wplugPlugin;

        // Register with twig
        $viewsDirPath = $wplugPlugin->getViewsDirPath();
        $this->twigService->addPath($viewsDirPath, $name);
    }


    /**
     * Returns the template renderer Twig
     */
    public function getTemplateRenderer() {

    }

    /**
     * Returns the version of this WPlug application object
     */
    public function getVersion() {
        return self::VERSION;
    }

    /**
     * Returns the current version of Wordpress
     */
    public function getWordpressVersion() {
        global $wp_version;
        return $wp_version;
    }

    /**
     * Returns the database
     */
    public function getDatabase() {

    }

    /**
     * Returns the current user
     */
    public function getCurrentUser() {
        return wp_get_current_user();
    }

    /**
     * Returns the wp-content directory path
     */
    public function getContentDir() {
        return WP_CONTENT_DIR;
    }

    /**
     * Returns the wp-content/plugins directory
     */
    public function getPluginsDir() {
        return WP_PLUGIN_DIR;
    }

    /**
     * Returns the upload directory
     */
    public function getUploadsDir() {
        return wp_upload_dir();
    }

}