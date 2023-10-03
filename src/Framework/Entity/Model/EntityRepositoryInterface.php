<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Model;

use Framework\Entity\Model\EntityInterface;

interface EntityRepositoryInterface {
    public function create(array $entityData): EntityInterface;
    public function load(int $id): EntityInterface;
    public function update(EntityInterface $entity): EntityInterface;
    public function delete(EntityInterface $entity);
}
