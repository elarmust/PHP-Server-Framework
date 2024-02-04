<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Database\Migrations;

use Framework\Database\MigrationInterface;
use Framework\Database\Database;
use Framework\Framework;

class M1685553949CreateCronHistoryTable implements MigrationInterface {
    public function __construct(private Framework $framework) {
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
        return [$this->framework->getDatabase()];
    }
}
