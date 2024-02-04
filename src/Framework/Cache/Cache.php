<?php

namespace Framework\Cache;

use RuntimeException;
use Framework\Cache\Table;

/**
 * A central table storage.
 */
class Cache {
    private static array $tables = [];

    /**
     * Add a new table to the cache.
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
     * Checks if a table exists in the cache.
     *
     * @param string $name The name of the table to check.
     * @return bool Returns true if the table exists, false otherwise.
     */
    public static function tableExists(string $name): bool {
        return isset(self::$tables[$name]);
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
