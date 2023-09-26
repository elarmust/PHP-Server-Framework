<?php

/**
 * 
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Exceptions;

use Throwable;

class EntityNotFoundException extends \Exception {
    private mixed $entityId;

    public function __construct($entityId, $code = 0, Throwable $previous = null) {
        $this->entityId = $entityId;
        parent::__construct('Could not find entity with id ' . $entityId . '!', $code, $previous);
    }

    public function getEntityId(): ?int {
        return $this->entityId;
    }
}
