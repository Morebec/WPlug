<?php 

namespace WPlug;

/**
 * Database wrapper
 */
class Database
{
    
    function __construct()
    {
        # code...
    }

    public function getName()
    {
        return DB_NAME;
    }

    public function getHost()
    {
        return DB_HOST;
    }    
}
