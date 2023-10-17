<?php

/**
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Database\Setup\Migrations;

use Framework\Container\ClassContainer;
use Framework\Database\Database;
use Framework\Configuration\Configuration;
use Framework\Database\MigrationInterface;

class M1685553949_CreateCronHistoryTable implements MigrationInterface {
    public function __construct(private ClassContainer $classContainer, private Configuration $configuration) {
        $configuration->loadConfiguration(BASE_PATH . '/config.json', 'json');
    }

    public function up(Database $database) {
        $database->query('
            CREATE TABLE `cron_history` (
                `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `cron_job` VARCHAR(64) NOT NULL,
                `start_time` DATETIME NOT NULL,
                `end_time` DATETIME DEFAULT NULL
            )
        ');
    }

    public function down(Database $database) {
        $database->query('DROP TABLE `cron_history`');
    }

    public function version(): string {
        return '1.0.0';
    }

    public function getDatabases(): array {
        $databaseInfo = $this->configuration->getConfig('databases.main');
        $database = $this->classContainer->get(Database::class, [$databaseInfo['host'], $databaseInfo['port'], $databaseInfo['database'], $databaseInfo['username'], $databaseInfo['password']], singleton: false);
        return [$database];
    }
}
