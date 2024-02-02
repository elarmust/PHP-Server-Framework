<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Database\Migrations;

use Framework\Database\MigrationInterface;
use Framework\Container\ClassContainer;
use Framework\Database\Database;
use Framework\Framework;
use Framework\Http\Session\SessionModel;

class M1706894162SessionStorage implements MigrationInterface {
    public function __construct(private ClassContainer $classContainer, private Framework $framework, private SessionModel $sessionModel) {
    }

    public function up(Database $database) {
        $database->query('
            CREATE TABLE `' . $this->sessionModel->getTableName() . '` (
                `id` VARCHAR(32) PRIMARY KEY,
                `data` TEXT NOT NULL,
                `timestamp` INT NOT NULL
            )
        ');
    }

    public function down(Database $database) {
        $database->query('DROP TABLE `' . $this->sessionModel->getTableName() . '`');
    }

    public function version(): string {
        return '1.0.0';
    }

    public function getDatabases(): array {
        $dbName = $this->framework->getConfiguration()->getConfig('session.sessionColdStorage.mysqlDb') ?: 'default';
        $databaseInfo = $this->framework->getConfiguration()->getConfig('databases.' . $dbName);
        $database = $this->classContainer->get(Database::class, [$databaseInfo['host'], $databaseInfo['port'], $databaseInfo['database'], $databaseInfo['username'], $databaseInfo['password']], useCache: false);
        return [$database];
    }
}
