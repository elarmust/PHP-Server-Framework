<?php

/**
 * 
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Exceptions;

use Throwable;

class EntityTypeAlreadyExistsException extends \Exception {
    private mixed $entityTypeName;

    public function __construct($entityTypeName, $code = 0, Throwable $previous = null) {
        $this->entityTypeName = $entityTypeName;
        parent::__construct('Entity type with name \'' . $entityTypeName . '\' already exists!', $code, $previous);
    }

    public function getEntityTypeName(): ?int {
        return $this->entityTypeName;
    }
}
