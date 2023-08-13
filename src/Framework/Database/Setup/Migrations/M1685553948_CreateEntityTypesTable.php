<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Database\Setup\Migrations;

use Framework\Core\ClassContainer;
use Framework\Database\Database;
use Framework\Configuration\Configuration;
use Framework\Database\MigrationInterface;

class M1685553948_CreateEntityTypesTable implements MigrationInterface {
    private Configuration $configuration;
    private ClassContainer $classContainer;

    public function __construct(ClassContainer $classContainer, Configuration $configuration) {
        $this->classContainer = $classContainer;
        $this->configuration = $configuration;
        $configuration->loadConfiguration(BASE_PATH . '/config.json', 'json');
    }

    public function up(Database $database) {
        $database->query("
            CREATE TABLE `entity_types` (
                `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `entity_type` VARCHAR(32) NOT NULL
            )
        ");
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
        $databaseInfo = $this->configuration->getConfig('databases.main');
        $database = $this->classContainer->get(Database::class, [$databaseInfo['host'], $databaseInfo['port'], $databaseInfo['database'], $databaseInfo['username'], $databaseInfo['password']], cache: false);
        return [$database];
    }
}