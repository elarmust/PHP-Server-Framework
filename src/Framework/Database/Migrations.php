<?php

/**
 * Data migrations
 *
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Database;

use Throwable;
use Psr\Log\LogLevel;
use Framework\Logger\Logger;
use InvalidArgumentException;
use Framework\Container\ClassContainer;
use Framework\Module\ModuleRegistry;

class Migrations {
    private array $migrations = [];

    public function __construct(
        private ClassContainer $classContainer,
        private ModuleRegistry $moduleRegistry,
        private Logger $logger
    ) {
        $this->loadMigrations();
    }

    public function loadMigrations() {
        $modulesList = $this->moduleRegistry->getAllModules();

        foreach ($modulesList as $module) {
            $modulePath = $module->getPath();

            if (!file_exists($modulePath . '/Setup/Migrations')) {
                continue;
            }

            $moduleMigrations = array_diff(scandir($modulePath . '/Setup/Migrations'), ['..', '.']);
            foreach ($moduleMigrations as $moduleMigration) {
                $migrationPath = $module->getName() . '\\Setup\\Migrations\\' . $moduleMigration;
                $name = str_replace('.php', '', $migrationPath);

                $migration = $this->classContainer->get($name, singleton: false);
                $this->migrations[strtolower($module->getName())][$migration->version()][str_replace('.php', '', $moduleMigration)] = $migration;
            }
        }

        // Framework internal migrations
        $intermalMigrations = array_diff(scandir(BASE_PATH . '/src/Framework/Database/Setup/Migrations'), ['..', '.']);
        foreach ($intermalMigrations as $internalMigration) {
            $internalMigration = str_replace('.php', '', $internalMigration);
            $migrationPath = 'Framework\\Database\\Setup\\Migrations\\' . $internalMigration;

            $migration = $this->classContainer->get($migrationPath, singleton: false);
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
     *
     * @throws InvalidArgumentException
     * @return bool Returns true, if the migration was run or false if the migration could not be run.
     */
    public function runMigration(MigrationInterface $migration, Database $database, bool $up = true): bool {
        if ($up) {
            if (!$this->migrationPackageHasBeenRun($migration, $database)) {
                try {
                    $migration->up($database);
                } catch (Throwable $e) {
                    $this->logger->log(LogLevel::ERROR, $e->getMessage(), identifier: 'framework');
                    $this->logger->log(LogLevel::ERROR, $e->getTraceAsString(), identifier: 'framework');
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
                $this->logger->log(LogLevel::ERROR, $e->getMessage(), identifier: 'framework');
                $this->logger->log(LogLevel::ERROR, $e->getTraceAsString(), identifier: 'framework');
            }

            $database->delete('migrations', ['migration' => $migration::class, 'version' => $migration->version()]);

            return true;
        }

        return false;
    }
}
