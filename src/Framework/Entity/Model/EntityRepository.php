<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Model;

use InvalidArgumentException;
use Framework\Database\Database;
use Framework\Entity\Model\EntityInterface;
use Framework\Entity\Model\EntityRepositoryInterface;

class EntityRepository implements EntityRepositoryInterface {
    private Database $database;
    private EntityInterface $entityBase;

    public function __construct(Database $datbase, EntityInterface $entity) {
        $this->database = $datbase;
        $this->entityBase = $entity;
    }

    public function create($user) {
        // TODO
    }

    public function findByAttributeValue(string $attribute, null|int|string $value, int $limit = 1, int $start = 0): array {
        $type = $this->entityBase->getType();
        if (!isset($this->entityBase->getAttributesMeta()[$attribute])) {
            throw new InvalidArgumentException('Entity ' . $type . ' does not have an attribute named ' . $attribute . '!');
        }

        $query = $this->database->query('
            SELECT
                id
            FROM
                entities_' . $type . '
            WHERE
                ' . $attribute . ' = ?
            LIMIT ' . $start . ', ' . $limit
        , [$value]);
        $entities = [];
        if (!is_array($query)) {
            return $entities;
        }

        foreach ($query as $entry) {
            $entities[] = $this->load($entry['id']);
        }

        return $entities;
    }

    public function load(int $id): EntityInterface {
        $entity = clone $this->entityBase;
        $entity->load($id);
        return $entity;
    }

    public function update($user): void {
        // TODO
    }

    public function delete(Entity $entity): void {
        $entity->delete();
    }
}
