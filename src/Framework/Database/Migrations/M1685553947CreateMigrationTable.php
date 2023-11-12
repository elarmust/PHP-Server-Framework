<?php

/**
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Database\Migrations;

use Framework\Database\MigrationInterface;
use Framework\Container\ClassContainer;
use Framework\Database\Database;
use Framework\Framework;

class M1685553947CreateMigrationTable implements MigrationInterface {
    public function __construct(private ClassContainer $classContainer, private Framework $framework) {
    }

    public function up(Database $database) {
        $database->query('
            CREATE TABLE `migrations` (
                `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `migration` VARCHAR(256) NOT NULL,
                `version` VARCHAR(32) NOT NULL
            )
        ');
    }

    public function down(Database $database) {
        $database->query('DROP TABLE `migrations`');
    }

    public function version(): string {
        return '1.0.0';
    }

    public function getDatabases(): array {
        $databaseInfo = $this->framework->getConfiguration()->getConfig('databases.main');
        $database = $this->classContainer->get(Database::class, [$databaseInfo['host'], $databaseInfo['port'], $databaseInfo['database'], $databaseInfo['username'], $databaseInfo['password']], singleton: false);
        return [$database];
    }
}
