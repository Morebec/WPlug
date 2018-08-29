<?php 

namespace WPlug;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Database Migrator
 */
class DatabaseMigrator
{
    /** @var string list of migrations to execute */
    private $migrations;

    /** @var string name of the migration table */
    private $migrationTableName;

    function __construct()
    {
        $this->migrations = [];
        $this->migrationTableName = 'wplug_database_migrations';
    }

    public function createMigrationTable()
    {
        if (!Capsule::schema()->hasTable($this->getMigrationTableName())) {    
            Capsule::schema()->create($this->getMigrationTableName(), function ($table) {
               $table->increments('id');
               $table->string('name');
               $table->string('plugin_name');
               $table->string('status');
               $table->timestamps();
            });
        }
    }

    /**
     * Migrates the database according to the specified set of migrations
     */
    public function migrate()
    {
        $this->createMigrationTable();

        $migrations = $this->getMigrations();
        foreach ($migrations as $migration) {
            // Check if migration is in database with status success
            // If not (not in db or not a status of success) --> run it
            $entry = MigrationEntry::where('name', $migration->getName())
                                   ->where('plugin_name', $migration->getPluginName())
                                   ->first()
            ;
            

            if (!$entry || $entry->status == 'failed') {
                $this->runMigration($migration, $entry);
            } else {
                // skip
            }            
        }
    }

    /**
     * Runs a migration
     * @return string status of migration run (success|faile)d
     */
    public function runMigration($migration, $entry)
    {
        // Run  migration
        $migration->up();

        // Update migration entry
        if (!$entry) {
            $entry = new MigrationEntry();
        }

        $entry->name = $migration->getName();
        $entry->plugin_name = $migration->getPluginName();
        $entry->status = 'success';
        $entry->save();
    }

    /**
     * Returns the list of migrations
     * @return array migrations
     */
    public function getMigrations()
    {
        return $this->migrations;
    }

    /**
     * Returns the MIgration table name
     * @return string migration table name
     */
    public function getMigrationTableName()
    {
        return $this->migrationTableName;
    }

    public function setMigrations($migrations)
    {
        $this->migrations = $migrations;
        return $this;
    }
}
