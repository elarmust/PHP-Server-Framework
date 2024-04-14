<?php

/**
 * An interface that defines common methods for loading, saving, deleting, and retrieving data for models.
 *
 * @copyright Elar Must.
 */

namespace Framework\Model;

use Framework\Database\Database;

interface ModelInterface {
    /**
     * Load data for the model from the database.
     *
     * @param string|int $modelId The ID of the model to load.
     * @param int $includeArchived Whether to include archived models in the results.
     *
     * @return ModelInterface
     */
    public function load(string|int $modelId, bool $includeArchived = false): ModelInterface;

    /**
     * Save the model's data to the database.
     *
     * @return ModelInterface
     */
    public function save(): ModelInterface;

    /**
     * Create a new model in the database.
     *
     * @return ModelInterface
     */
    public function create(array $data): ModelInterface;

    /**
     * Delete the model from the database.
     *
     * @return ModelInterface
     */
    public function delete(): ModelInterface;

    /**
     * Set data for multiple model data.
     *
     * @param array $data An associative array of data keys and values.
     *
     * @return ModelInterface
     */
    public function setData(array $data): ModelInterface;

    /**
     * Get model data.
     *
     * @param array $keys An array of data keys to retrieve.
     *
     * @return array An associative array of data keys and values.
     */
    public function getData(array $keys = []): array;

    /**
     * Returns the ID of the model.
     *
     * @return null|string|int The ID of the model, or null if it doesn't have an ID.
     */
    public function id(): null|string|int;

    /**
     * Returns the name of the table associated with the model.
     *
     * @return string Table name.
     */
    public function getTableName(): string;

    /**
     * Returns the database object associated with the model.
     * Allows to extend the model with custom database functionality.
     *
     * @return Database The database object associated with the model.
     */
    public function getDatabase(): Database;

    /**
     * Get model properties.
     *
     * @return array Model properties
     */
    public function getProperties(array $keys = []): array;

    /**
     * Checks if a property is persistent.
     *
     * @param string $propertyName The name of the property.
     *
     * @return bool Returns true if the property is persistent, false otherwise.
     */
    public function isPropertyPersistent(string $key): bool;

    /**
     * Checks if a property is persistent.
     *
     * @param string $propertyName The name of the property.
     * @return bool Returns true if the property is persistent, false otherwise.
     */
    public function isDataProperty(string $key): bool;

    /**
     * Checks if a property is readonly.
     *
     * @param string $propertyName The name of the property to check.
     * @return bool Returns true if the property is readonly, false otherwise.
     */
    public function isPropertyReadonly(string $key): bool;

    /**
     * Retrieves the default value for a given property.
     *
     * @param string $propertyName The name of the property.
     *
     * @return mixed The default value of the property, or null if not found.
     */
    public function getDefaultValue(string $key): mixed;

    /**
     * Creates a new instance of the model with the given data.
     * The new instance will be a clone of the current instance with the updated data.
     *
     * @param array $data The data to set on the model.
     *
     * @return ModelInterface The new instance of the model with the updated data.
     */
    public function withData(array $data): ModelInterface;

    /**
     * Restores the model by updating the 'deleted_at' column to null.
     *
     * @throws ModelException If the model has not been instanciated.
     * @return ModelInterface The restored model.
     */
    public function restore(): ModelInterface;

    /**
     * Remove a properties from the model.
     *
     * @param array $properties An array of model properties to remove.
     *
     * @return ModelInterface
     */
    public function removeProperties(array $keys): ModelInterface;

    /**
     * Get the value of the data key.
     *
     * @param string $name The name of the data key.
     *
     * @throws ModelException If the property does not exist.
     * @return mixed The value of the property.
     */
    public function __get($name): mixed;

    /**
     * Sets the value of a model data key.
     *
     * @param string $name The name of the data key.
     * @param mixed $value The value to be set.
     *
     * @return void
     */
    public function __set($name, $value): void;

    /**
     * Checks if a data key is set.
     *
     * @param string $name The name of the data key to check.
     * @return bool Returns true if the data key is set, false otherwise.
     */
    public function __isset($name): bool;

    /**
     * Unsets a model's data key.
     *
     * @param string $name The name of the data key to unset.
     * @return void
     */
    public function __unset($name): void;

    /**
     * Retursn mode's data array.
     *
     * @return array The model data as an array.
     */
    public function __toArray(): array;

    /**
     * Returns a JSON representation of the model data.
     *
     * @return string The JSON representation of the model data.
     */
    public function __toString(): string;
}
