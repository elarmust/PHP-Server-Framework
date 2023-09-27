<?php

/**
 * Varchar attribute data type
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Database\DataTypes;

use \InvalidArgumentException;

class DataTypeVarchar implements DataTypeInterface {
    private int $dataLength = 1;
    private null|string $defaultValue = null;
    private bool $notnull = true;

    public function __construct(int $length = 1, null|string $defaultDataValue = null, bool $notnull = false) {
        if ($length < 1 || !$length) {
            throw new InvalidArgumentException('Invalid data length!');
        }

        if ($defaultDataValue === null && $notnull) {
            throw new InvalidArgumentException('Default value may not be null, if null values are not allowed!');
        }

        $this->dataLength = $length;
        $this->defaultValue = $defaultDataValue;
        $this->notnull = $notnull;
    }

    public function dataType(): string {
        return 'varchar';
    }

    public function dataLength(): int {
        return $this->dataLength;
    }

    public function defaultValue(): null|string {
        return $this->defaultValue;
    }

    public function notNull(): bool {
        return $this->notnull;
    }
}
