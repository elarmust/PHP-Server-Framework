<?php

/**
 * Int attribute data type
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Database\DataTypes;

use \InvalidArgumentException;

class DataTypeInt implements DataTypeInterface {
    private int $dataLength = 1;
    private null|int $defaultValue = null;
    private bool $notnull = true;

    public function __construct(int $length = 1, null|int $defaultDataValue = null, bool $notnull = false) {
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
        return 'int';
    }

    public function dataLength(): int {
        return $this->dataLength;
    }

    public function defaultValue(): null|int {
        return $this->defaultValue;
    }

    public function notNull(): bool {
        return $this->notnull;
    }
}