<?php 

namespace WPlug;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;

/**
 * Database wrapper
 */
class Database
{
    /**
     * Eloquent capsule
     * @var [type]
     */
    private $capsule;

    /**
     * App instance
     * @var [type]
     */
    private $app;

    function __construct($app)
    {
        $this->app = $app;
        $this->capsule = new Manager();

        $this->capsule->addConnection([
            'driver' => $this->getDriver(),
            'host' => $this->getHost(),
            'database' => $this->getHost(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'charset' => $this->getCharset(),
            'collation' => $this->getCollate(),
            'prefix' => ''
        ]);

        // $this->capsule->setEventDispatcher(new Dispatcher(new Container()));
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    public function getDriver()
    {
        $app = $this->getApp();

        $plugins = $app->getPlugins();

        if (!defined('USE_MYSQL') || !USE_MYSQL) {
            if ($plugins['sqlite-integration/sqlite-integration.php']) {
                return 'sqlite';

            }
        }
          
        return 'mysql';
    }

    public function getName()
    {
        return DB_NAME;
    }

    public function getHost()
    {
        if ($this->getDriver() == 'sqlite') {
            return FQDB;
        }
        
        return DB_HOST;
    }    

    public function getUsername()
    {
        return DB_USER;
    }

    public function getPassword()
    {
        return DB_PASSWORD;
    }

    public function getCharset()
    {
        return DB_CHARSET;
    }

    public function getCollate()
    {
        return DB_COLLATE; // 'utf8_unicode_ci';
    }

    public function getApp()
    {
        return $this->app;
    }
}
