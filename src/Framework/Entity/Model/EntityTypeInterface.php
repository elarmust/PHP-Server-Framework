<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Model;

use Framework\Database\DataTypes\DataTypeInterface;

interface EntityTypeInterface {
    public function loadType(): void;

    public function createType(): void;

    public function deleteType(): void;

    public function addAttribute(string $attributeName, DataTypeInterface $dataType, string $getClass = null, string $setClass = null, string $inputListClass = null): void;

    public function deleteAttribute(string $attributeName): void;

    public function getType(): ?string;

    public function getTypeId(): ?string;

    public function getAttributes(): array;

    public function getAttributesMeta(): array;
}