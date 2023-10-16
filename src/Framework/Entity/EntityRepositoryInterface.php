<?php

/**
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Entity;

use Framework\Entity\EntityInterface;

interface EntityRepositoryInterface {
    public function create(array $entityData): EntityInterface;
    public function load(int $id): EntityInterface;
    public function update(EntityInterface $entity): EntityInterface;
    public function delete(EntityInterface $entity);
}
