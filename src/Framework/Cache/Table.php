<?php

namespace Framework\Cache;

use OpenSwoole\Table as SwooleTable;

/**
 * An extension of SwooleTable, with a name property.
 */
class Table extends SwooleTable {
    public function __construct(private string $name, int $rowCount) {
        parent::__construct($rowCount);
    }

    public function getName(): string {
        return $this->name;
    }
}
