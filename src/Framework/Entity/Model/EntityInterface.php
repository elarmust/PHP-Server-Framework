<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Model;

interface EntityInterface {
    public function load(int $entityId): void;

    public function save(): void;

    public function delete(): void;

    public function setData(array $attributesValue): void;

    public function getId(): ?int;

    public function getData(array $fields = []): array;

    public function getFields(): array;
}
