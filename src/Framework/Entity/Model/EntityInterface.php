<?php

/**
 * An interface that defines common methods for loading, saving, deleting, and retrieving data for entities.
 *
 * @copyright WereWolf Labs OÜ.
 */

namespace Framework\Entity\Model;

interface EntityInterface {
    /**
     * Load data for the entity from the database.
     *
     * @param int $entityId The ID of the entity to load.
     * 
     * @return void
     */
    public function load(int $entityId): void;

    /**
     * Save the entity's data to the database.
     * 
     * @return void
     */
    public function save(): void;

    /**
     * Delete the entity from the database.
     * 
     * @return void
     */
    public function delete(): void;

    /**
     * Set data for multiple entity attributes.
     *
     * @param array $attributesValue An associative array of attribute names and their values.
     * 
     * @return void
     */
    public function setData(array $attributesValue): void;

    /**
     * Get the ID of the entity.
     *
     * @return int|null The ID of the entity, or null if it hasn't been assigned an ID yet.
     */
    public function getId(): ?int;

    /**
     * Get data for specific entity fields.
     *
     * @param array $fields An array of field names to retrieve data for.
     * 
     * @return array An associative array of field names and their values.
     */
    public function getData(array $fields = []): array;

    /**
     * Get an array of all available entity fields.
     *
     * @return array An array of entity field names.
     */
    public function getFields(): array;
}
