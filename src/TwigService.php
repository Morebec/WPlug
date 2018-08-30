<?php

namespace WPlug;

/**
 * Twig integration
 */
class TwigService
{
    /**
     * Template loader
     */
    private $loader;

    /**
     * Twig env
     */
    private $twig;

    public function __construct() {
        $DUMMY_MAIN_NAMESPACE_PATH = '.';
        $this->loader = new \Twig_Loader_Filesystem($DUMMY_MAIN_NAMESPACE_PATH);
        $this->twig = new \Twig_Environment($this->loader, array(
            // 'cache' => '/path/to/compilation_cache',
        ));
    }

    /**
     * Adds a path to the loader with a specific namespace
     */
    public function addPath($path, $namespace) {
        $this->loader->addPath($path, $namespace);
    }

    public function addFilter($twigFilter)
    {
        $this->twig->addFilter($twigFilter);
    }

    /**
     * Renders a template with a specific ctx
     */
    public function render($template, $ctx = array()) {
        return $this->twig->render($template, $ctx);
    }
}
