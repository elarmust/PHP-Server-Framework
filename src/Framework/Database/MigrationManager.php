<?php

/**
 * Data migrations manager
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Database;

use InvalidArgumentException;
use Framework\Logger\Logger;
use Framework\Core\ClassContainer;
use Framework\Core\Module\ModuleManager;
use Throwable;

class MigrationManager {
    private ClassContainer $classContainer;
    private ModuleManager $moduleManager;
    private Logger $logger;
    private array $migrations = [];

    public function __construct(ClassContainer $classContainer, ModuleManager $moduleManager, Logger $logger) {
        $this->classContainer = $classContainer;
        $this->moduleManager = $moduleManager;
        $this->logger = $logger;
        $this->loadMigrations();
    }

    public function loadMigrations() {
        $modulesList = $this->moduleManager->getModules();

        foreach ($modulesList as $module) {
            $modulePath = $module->getPath();

            if (!file_exists($modulePath . '/Setup/Migrations')) {
                continue;
            }

            $moduleMigrations = array_diff(scandir($modulePath . '/Setup/Migrations'), ['..', '.']);
            foreach ($moduleMigrations as $moduleMigration) {
                $migrationPath = $module->getClassPath() . '\\Setup\\Migrations\\' . $moduleMigration;
                $name = str_replace('.php', '', $migrationPath);

                $migration = $this->classContainer->get($name, cache: false);
                $this->migrations[strtolower($module->getClassPath())][$migration->version()][str_replace('.php', '', $moduleMigration)] = $migration;
            }
        }

        // Framework internal migrations
        $intermalMigrations = array_diff(scandir(BASE_PATH . '/src/Framework/Database/Setup/Migrations'), ['..', '.']);
        foreach ($intermalMigrations as $internalMigration) {
            $internalMigration = str_replace('.php', '', $internalMigration);
            $migrationPath = 'Framework\\Database\\Setup\\Migrations\\' . $internalMigration;

            $migration = $this->classContainer->get($migrationPath, cache: false);
            $this->migrations['framework'][$migration->version()][$internalMigration] = $migration;
        }
    }

    public function migrationExists(string $moduleName, ?string $version = null): bool {
        if ($version && !isset($this->migrations[strtolower($moduleName)][$version])) {
            return false;
        }

        if (!isset($this->migrations[strtolower($moduleName)])) {
            return false;
        }

        return true;
    }

    /**
     * Get migration objects
     * 
     * @param string $migrationName Which migrations should be returned.
     * @return array Returns an array of migration objects or an empty array.
     */
    public function getMigrations(?string $migrationName = null): array {
        if ($migrationName) {
            $migrationName = strtolower($migrationName);
            return [$migrationName => $this->migrations[$migrationName] ?? []];
        }

        return $this->migrations;
    }

    public function migrationPackageHasBeenRun(MigrationInterface $migration, Database $database): bool {
        $res = $database->query('
            SELECT
                *
            FROM information_schema.tables
            WHERE
                table_schema = ? AND
                table_name = ?
        ', [$database->getName(), 'migrations']);

        if (!$res) {
            return false;
        }

        $res = $database->select('migrations', null, ['migration' => $migration::class]);

        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Run module migration
     * 
     * @param MigrationInterface $migration Migration object.
     * @param Database $database Migration database.
     * @param bool $up Migrate up or down.
     * @return bool Returns true, if the migration was run or false if the migration could not be run.
     * @throws InvalidArgumentException
     */
    public function runMigration(MigrationInterface $migration, Database $database, bool $up = true): bool {
        if ($up) {
            if (!$this->migrationPackageHasBeenRun($migration, $database)) {                
                try {
                    $migration->up($database);
                } catch (Throwable $e) {
                    $this->logger->log(Logger::LOG_ERR, $e->getMessage(), 'framework');
                    $this->logger->log(Logger::LOG_ERR, $e->getTraceAsString(), 'framework');
                }

                $database->insert('migrations', ['migration' => $migration::class, 'version' => $migration->version()]);
                return true;
            }

            return false;
        }

        if ($this->migrationPackageHasBeenRun($migration, $database)) {
            try {
                $migration->down($database);
            } catch (Throwable $e) {
                $this->logger->log(Logger::LOG_ERR, $e->getMessage(), 'framework');
                $this->logger->log(Logger::LOG_ERR, $e->getTraceAsString(), 'framework');
            }

            $database->delete('migrations', ['migration' => $migration::class, 'version' => $migration->version()]);

            return true;
        }

        return false;
    }
}