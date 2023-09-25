<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Database;

interface MigrationInterface {
    // Up migration
    public function up(Database $database);

    // Down migration
    public function down(Database $database);

    // Migration version
    public function version(): string;

    // Array of database object for which migrations should be run for
    public function getDatabases(): array;
}