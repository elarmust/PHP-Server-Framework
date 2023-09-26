<?php

/**
 * 
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Exceptions;

use Throwable;

class EntityTypeNotFoundException extends \Exception {
    private mixed $entityTypeName;

    public function __construct($entityTypeName, $code = 0, Throwable $previous = null) {
        $this->entityTypeName = $entityTypeName;
        parent::__construct('Could not find entity type with id ' . $entityTypeName . '!', $code, $previous);
    }

    public function getEntityTypeName(): ?int {
        return $this->entityTypeName;
    }
}
