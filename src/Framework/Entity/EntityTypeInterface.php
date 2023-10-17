<?php

/**
 * Interface for managing entity types and their attributes.
 *
 * @copyright @ WW Byte OÜ.
 */

namespace Framework\Entity;

use Framework\Database\DataTypes\DataTypeInterface;

interface EntityTypeInterface {
    /**
     * Load entity type.
     *
     * @return void
     */
    public function loadType(): void;

    /**
     * Create a new entity type.
     *
     * @return void
     */
    public function createType(): void;

    /**
     * Delete the entity type and its attributes.
     *
     * @return void
     */
    public function deleteType(): void;

    /**
     * Add an attribute to the entity type.
     *
     * @param string $attributeName The name of the attribute to add.
     * @param DataTypeInterface $dataType The data type of the attribute.
     * @param string|null $getClass The class used for preprocessing the retrieved value.
     * @param string|null $setClass The class used for postprocessing the value before saving.
     * @param string|null $inputListClass The class used for retrieving a list of accepted values.
     *
     * @return void
     */
    public function addAttribute(string $attributeName, DataTypeInterface $dataType, string $getClass = null, string $setClass = null, string $inputListClass = null): void;

    /**
     * Delete an attribute from the entity type.
     *
     * @param string $attributeName The name of the attribute to delete.
     *
     * @return void
     */
    public function deleteAttribute(string $attributeName): void;

    /**
     * Get the name of the entity type.
     *
     * @return string|null The name of the entity type.
     */
    public function getType(): ?string;

    /**
     * Get the ID of the entity type.
     *
     * @return int|null The ID of the entity type.
     */
    public function getTypeId(): ?int;

    /**
     * Get the attributes associated with the entity type.
     *
     * @return array The attributes as an associative array.
     */
    public function getAttributes(): array;

    /**
     * Get metadata for the entity type attributes.
     *
     * @return array The attribute metadata as an associative array.
     */
    public function getAttributesMeta(): array;
}
