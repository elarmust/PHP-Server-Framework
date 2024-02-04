<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Database\Migrations;

use Framework\Database\MigrationInterface;
use Framework\Database\Database;
use Framework\Framework;

class M1685553947CreateMigrationTable implements MigrationInterface {
    public function __construct(private Framework $framework) {
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
        return [$this->framework->getDatabase()];
    }
}
