<?php

namespace Framework\Entity\Exceptions;

use Throwable;

class EntityAlreadyExistsException extends \Exception {
    private mixed $entityId;

    public function __construct($entityId, $code = 0, Throwable $previous = null) {
        $this->entityId = $entityId;
        parent::__construct('Entity with id ' . $entityId . ' already exists!', $code, $previous);
    }

    public function getEntityId(): ?int {
        return $this->entityId;
    }
}
