<?php

namespace Framework\Vault;

use RuntimeException;
use Framework\Vault\Table;

/**
 * A central table storage.
 */
class Vault {
    private static array $tables = [];

    /**
     * Add a new table to the vault.
     * 
     * @param string $name Table name
     * @param int $rowCount Row count
     *
     * @return Table
     */
    public static function addTable(Table $table): void {
        self::$tables[$table->getName()] = $table;
    }

    /**
     * Get table.
     * 
     * @param string $name Table name
     *
     * @throws RuntimeException If table does not exist.
     * @return Table
     */
    public static function getTable(string $name): Table {
        if (!isset(self::$tables[$name])) {
            throw new RuntimeException('Table ' . $name . ' does not exist.');
        }

        return self::$tables[$name];
    }

    /**
     * Destroy a table.
     *
     * @param string $name The name of the table to destroy.
     * @return void
     */
    public static function destroy(string $name): void {
        if (isset(self::$tables[$name])) {
            self::$tables[$name]->destroy();
            unset(self::$tables[$name]);
        }
    }

    public function __isset($name): bool {
        return isset(self::$tables[$name]);
    }
}
