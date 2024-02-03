<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Database\Migrations;

use Framework\Framework;
use Framework\Database\Database;
use Framework\Http\Session\Session;
use Framework\Container\ClassContainer;
use Framework\Database\MigrationInterface;

class M1706894162SessionStorage implements MigrationInterface {
    public function __construct(private ClassContainer $classContainer, private Framework $framework, private Session $session) {
    }

    public function up(Database $database) {
        $database->query('
            CREATE TABLE `' . Session::getTableName() . '` (
                `id` VARCHAR(32) PRIMARY KEY,
                `data` TEXT NOT NULL,
                `timestamp` INT NOT NULL
            )
        ');
    }

    public function down(Database $database) {
        $database->query('DROP TABLE `' . Session::getTableName() . '`');
    }

    public function version(): string {
        return '1.0.0';
    }

    public function getDatabases(): array {
        return [$this->session->getDatabase()];
    }
}
