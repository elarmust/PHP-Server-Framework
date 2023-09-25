<?php

/**
 * Varchar attribute data type
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Database\DataTypes;

use \InvalidArgumentException;

class DataTypeDateTime implements DataTypeInterface {
    private null|string $defaultValue = null;
    private bool $notnull = true;

    public function __construct(null|string $defaultDataValue = 'CURRENT_TIMESTAMP', bool $notnull = false) {
        if ($defaultDataValue === null && $notnull) {
            throw new InvalidArgumentException('Default value may not be null when null values are not allowed!');
        }

        $this->defaultValue = $defaultDataValue;
        $this->notnull = $notnull;
    }

    public function dataType(): string {
        return 'datetime';
    }

    public function dataLength(): int {
        return 0;
    }

    public function defaultValue(): null|string {
        return $this->defaultValue;
    }

    public function notNull(): bool {
        return $this->notnull;
    }
}