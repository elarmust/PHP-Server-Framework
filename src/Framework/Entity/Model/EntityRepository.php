<?php

/**
 * This class provides methods for creating, finding, loading, updating, and deleting entities.
 *
 * @copyright WereWolf Labs OÜ.
 */

namespace Framework\Entity\Model;

use InvalidArgumentException;
use Framework\Database\Database;
use Framework\Entity\Model\EntityInterface;
use Framework\Entity\Model\EntityRepositoryInterface;

class EntityRepository implements EntityRepositoryInterface {
    private Database $database;
    private EntityInterface $entityBase;

    /**
     * @param Database $database The database connection to use for database operations.
     * @param EntityInterface $entity The base entity that this repository works with.
     */
    public function __construct(Database $datbase, EntityInterface $entity) {
        $this->database = $datbase;
        $this->entityBase = $entity;
    }

    /**
     * Create a new entity in the database.
     *
     * @param EntityInterface $entity The entity data to create.
     * 
     * @return void
     * @todo Implement the create method.
     */
    public function create(array $entityData): EntityInterface {
        // TODO
        return $this->entityBase;
    }

    /**
     * Find entities by a specific attribute value.
     *
     * @param string $attribute The attribute name to search by.
     * @param null|int|string $value The value to search for.
     * @param int $limit The maximum number of entities to retrieve.
     * @param int $start The starting index for retrieving entities.
     * 
     * @throws InvalidArgumentException If the specified attribute does not exist in the entity.
     * @return array<EntityInterface> An array of entities matching the search criteria.
     */
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

    /**
     * Load an entity from the database by its ID.
     *
     * @param int $id The ID of the entity to load.
     * 
     * @return EntityInterface
     * @todo Implement the create method.
     */
    public function load(int $id): EntityInterface {
        $entity = clone $this->entityBase;
        $entity->load($id);
        return $entity;
    }

    /**
     * Update an entity in the database.
     *
     * @param EntityInterface $entity The entity to update.
     * 
     * @return EntityInterface
     */
    public function update(EntityInterface $entity): EntityInterface {
        $entity->save();
        return $this->entityBase;
    }

    /**
     * Delete an entity from the database.
     *
     * @param EntityInterface $entity The entity to delete.
     * 
     * @return void
     */
    public function delete(EntityInterface $entity): void {
        $entity->delete();
    }
}
