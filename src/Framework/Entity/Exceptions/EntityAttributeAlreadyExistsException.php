<?php

namespace Framework\Entity\Exceptions;

class EntityAttributeAlreadyExistsException extends \Exception {
    private mixed $attributeName;

    public function __construct($attributeName, $code = 0, Throwable $previous = null) {
        $this->attributeName = $attributeName;
        parent::__construct('Entity attribute with name ' . $attributeName . ' already exists!', $code, $previous);
    }

    public function getAttributeName(): ?int {
        return $this->attributeName;
    }
}