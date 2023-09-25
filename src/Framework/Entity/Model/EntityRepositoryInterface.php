<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Model;

interface EntityRepositoryInterface {
    public function create(array $entityData);
    public function load(int $id);
    public function update(Entity $entity);
    public function delete(Entity $entity);
}
