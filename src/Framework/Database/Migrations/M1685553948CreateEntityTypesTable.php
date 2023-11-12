<?php

/**
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Database\Migrations;

use Framework\Database\MigrationInterface;
use Framework\Container\ClassContainer;
use Framework\Database\Database;
use Framework\Framework;

class M1685553948CreateEntityTypesTable implements MigrationInterface {
    public function __construct(private ClassContainer $classContainer, private Framework $framework) {
    }

    public function up(Database $database) {
        $database->query('
            CREATE TABLE `entity_types` (
                `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `entity_type` VARCHAR(32) NOT NULL
            )
        ');
    }

    public function down(Database $database) {
        $entities = $database->select('entity_types');
        foreach ($entities as $entity) {
            $database->query('DROP TABLE entity_' . $entity['entity_type'] . '_attributes');
            $database->query('DROP TABLE entities_' . $entity['entity_type']);
        }

        $database->query('DROP TABLE entity_types');
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
