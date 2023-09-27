<?php

/**
 * 
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Exceptions;

use Throwable;

class EntityAttributeNotFoundException extends \Exception {
    private mixed $attributeName;

    public function __construct($attributeName, $code = 0, Throwable $previous = null) {
        $this->attributeName = $attributeName;
        parent::__construct('Could not find entity attribute with name \'' . $attributeName . '\'!', $code, $previous);
    }

    public function getAttributeName(): ?int {
        return $this->attributeName;
    }
}
