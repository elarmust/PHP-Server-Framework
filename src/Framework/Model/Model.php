<?php

/**
 * Represents an model object that interacts with the database.
 *
 * @copyright Elar Must.
 */

namespace Framework\Model;

use Framework\Model\Exception\ModelException;
use Framework\Database\Database;
use Framework\Logger\Logger;

abstract class Model implements ModelInterface {
    protected null|string $tableName = null;
    protected array $defaultProperties = [
        'id' => [
            'default' => null,
            'readonly' => true
        ]
    ];
    protected array $properties = [];
    protected array $data = [];

    /**
     * @param Database $database
     * @param Logger $logger
     */
    public function __construct(
        private Database $database,
        private Logger $logger
    ) {
        // Set properties = defaultProperties + properties.
        $this->properties = array_replace($this->defaultProperties, $this->properties);
        // Clean up bad properties and data.
        $this->cleanupPropertiesAndData();
    }

    /**
     * Load the model by its ID.
     *
     * @param string|int $modelId The ID of the model to load.
     * @param bool $includeArchived Whether or not to include archived entries.
     *
     * @return ModelInterface
     */
    public function load(string|int $modelId, bool $includeArchived = false): ModelInterface {
        $model = clone $this;

        // Load the model from database.
        if ($model->getProperties(['deleted_at']) && !$includeArchived) {
            // If the model has archival functionality enabled and the includeArchived flag is set to false, retrieve only the non-archived model.
            $data = $model->getDatabase()->query('SELECT * FROM ' . $model->getTableName() . ' WHERE id = ? AND deleted_at IS NULL', [$modelId]);
        } else {
            // If the model has archival functionality disabled or the includeArchived flag is set to true, retrieve the model without any restrictions.
            $data = $model->getDatabase()->select($model->getTableName(), where: ['id' => $modelId]);
        }

        // Throw an exception if the model was not found.
        if (!$data) {
            throw new ModelException('Model with id ' . $modelId . ' not found');
        }

        return $model->withData($data[0]);
    }

    /**
     * Creates a new record in the database with the given data.
     *
     * @param array $data = [] Data to be inserted into the database.
     *
     * @return ModelInterface Newly created model instance.
     */
    public function create(array $data = []): ModelInterface {
        $model = $this->withData([]);
        $properties = $model->getProperties();

        // Initialize the data array with the default data.
        $createData = [];
        foreach ($properties as $key => $value) {
            if ($key === 'created_at') {
                $data['created_at'] = date('Y-m-d H:i:s');
                $createData[$key] = $data['created_at'];
            } elseif ($model->isPropertyPersistent($key)) {
                $createData[$key] = $data[$key] ?? $model->getDefaultValue($key);
            }
        }

        // Insert data to database and get the id.
        $data['id'] = $model->getDatabase()->insert($model->getTableName(), $createData);
        if ($data['id'] === false) {
            throw new ModelException('Failed to create model in database!');
        }

        // Set the model to new model with the data.
        return $model->withData($data);
    }

    /**
     * Set model data
     *
     * @param array $data An associative array of data keys and values.
     *
     * @throws ModelException If the model has not been instantiated.
     * @return ModelInterface
     */
    public function setData(array $data): ModelInterface {
        foreach ($data as $key => $value) {
            // Readonly values cannot be set on existing models.
            if ($this->isPropertyReadonly($key) && $this->id() !== null) {
                throw new ModelException('Cannot set readonly key: ' . $key);
            }
        }

        $this->data = array_merge($this->getData(), $data);
        return $this;
    }

    /**
     * Save the model data to the database.
     *
     * @throws ModelException If the model has not been instantiated.
     * @return ModelInterface
     */
    public function save(): ModelInterface {
        if ($this->id() === null) {
            throw new ModelException('Cannot save non-instanciated model.');
        }

        // Set saved_at, if it has a default value.
        if ($this->getProperties(['saved_at'])) {
            $this->saved_at = date('Y-m-d H:i:s');
        }

        $save = [];
        $modelData = $this->getData();
        foreach ($this->getProperties() as $key => $value) {
            // Save persistent data.
            if ($this->isPropertyPersistent($key)) {
                $save[$key] = $modelData[$key] ?? $this->getDefaultValue($key);
            }
        }

        if ($save) {
            $status = $this->getDatabase()->update($this->getTableName(), $save, ['id' => $this->id()]);
            if (!$status) {
                throw new ModelException('Failed to save model to database!');
            }
        }

        return $this;
    }

    /**
     * Delete the model from the database.
     *
     * @throws ModelException If the model has not been instanciated.
     * @return ModelInterface
     */
    public function delete(): ModelInterface {
        if ($this->id() === null) {
            throw new ModelException('Cannot delete non-instanciated model.');
        }

        if ($this->getProperties(['deleted_at'])) {
            $deleteDate = date('Y-m-d H:i:s');
            $this->deleted_at = $deleteDate;
            $this->getDatabase()->update($this->getTableName(), ['deleted_at' => $deleteDate], ['id' => $this->id()]);
        } else {
            $status = $this->getDatabase()->delete($this->getTableName(), ['id' => $this->id()]);
            if (!$status) {
                throw new ModelException('Failed to delete model from database!');
            }
        }

        return $this;
    }

    /**
     * Restores the model by updating the 'deleted_at' column to null.
     *
     * @throws ModelException If the model has not been instanciated.
     * @return ModelInterface The restored model.
     */
    public function restore(): ModelInterface {
        if ($this->id() === null) {
            throw new ModelException('Cannot restore non-instanciated model.');
        }

        if (!in_array('deleted_at', $this->getDataKeys())) {
            return $this;
        }

        $status = $this->getDatabase()->update($this->getTableName(), ['deleted_at' => null], ['id' => $this->id()]);
        if (!$status) {
            throw new ModelException('Failed to restore model from database!');
        }

        $this->deleted_at = null;

        return $this;
    }

    /**
     * Checks if a record with the given ID exists in the database.
     *
     * @param int|string $id The ID of the record to check.
     * @return bool True if the record exists, false otherwise.
     */
    public function exists(int|string $id): bool {
        if ($this->database->select($this->getTableName(), ['id'], ['id' => $id])) {
            return true;
        }

        return false;
    }

    /**
     * Get the ID of the model.
     *
     * @return null|string|int The ID of the model, or null if it hasn't been assigned an ID yet.
     */
    public function id(): null|string|int {
        return $this->data['id'];
    }

    /**
     * Get data for specific model fields.
     *
     * @param array $fields An array of field names to retrieve data for.
     *
     * @return array
     */
    public function getData(array $keys = []): array {
        if (!$keys) {
            return $this->data;
        }

        $invalidKeys = array_diff($keys, array_keys($this->data));
        if ($invalidKeys) {
            throw new ModelException('Invalid keys: ' . implode(', ', $invalidKeys));
        }

        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * Get an array of all available model data keys.
     *
     * @return array An array of model data keys.
     */
    public function getDataKeys(): array {
        return array_keys($this->data);
    }

    /**
     * Get model properties.
     *
     * @return array Model properties
     */
    public function getProperties(array $keys = []): array {
        if (!$keys) {
            return $this->properties;
        }
    
        return array_intersect_key($this->properties, array_flip($keys));
    }

    /**
     * Set a properties for the model.
     *
     * @param array $properties An associative array of data properties to set.
     *
     * @return ModelInterface
     */
    public function modifyProperties(array $properties): ModelInterface {
        $this->properties = array_replace_recursive($this->properties, $properties);

        $this->cleanupPropertiesAndData();

        return $this;
    }

    /**
     * Remove a properties from the model.
     *
     * @param array $properties An array of model properties to remove.
     *
     * @return ModelInterface
     */
    public function removeProperties(array $properties): ModelInterface {
        $this->properties = $this->arrayRemoveRecursive($properties, $this->properties);
        return $this;
    }

    /**
     * Recursively removes elements from an array based on another array.
     *
     * This method removes elements from the given array ($array2) based on the keys present in the $array1.
     * It recursively traverses the $array1 and removes corresponding elements from $array2.
     *
     * @param array $array1 Array containing keys to be removed.
     * @param array $array2 Array from which elements will be removed.
     *
     * @return array The modified array with removed keys.
     */
    protected function arrayRemoveRecursive(array $array1, array $array2): array {
        foreach ($array1 as $removeKey => $subRemoveKeys) {
            if (is_array($subRemoveKeys)) {
                $array2[$removeKey] = $this->arrayRemoveRecursive($subRemoveKeys, $array2[$removeKey]);
            } else if (isset($array2[$removeKey])) {
                unset($array2[$removeKey]);
            } else {
                unset($array2[$subRemoveKeys]);
            }
        }

        return $array2;
    }

    /**
     * Updates the default values of the model's data array.
     * Also performs cleanup.
     *
     * @return void
     */
    protected function cleanupPropertiesAndData(): void {
        foreach ($this->getProperties() as $key => $values) {
            // If the key is numeric, then property name is it's value and its values are empty.
            if (is_numeric($key) && is_string($values)) {
                $this->properties[$values] = [];
                unset($this->properties[$key]);
                $key = $values;
                $values = [];
            }

            if (!array_key_exists($key, $this->data) && $this->isDataProperty($key)) {
                $this->data[$key] = $this->getDefaultValue($key);
            }
        }

        $this->data = array_intersect_key($this->data, $this->getProperties());
    }

    public function isDataProperty(string $propertyName): bool {
        $properties = $this->getProperties([$propertyName]);
        if (!$properties) {
            return false;
        }

        $property = $properties[$propertyName];
        if (($property['notData'] ?? false) === true) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a property is persistent.
     *
     * @param string $propertyName The name of the property.
     * @return bool Returns true if the property is persistent, false otherwise.
     */
    public function isPropertyPersistent(string $propertyName): bool {
        if (!$this->isDataProperty($propertyName)) {
            return false;
        }

        $property = $this->getProperties([$propertyName])[$propertyName];
        if (($property['persistent'] ?? true) === false) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a property is readonly.
     *
     * @param string $propertyName The name of the property to check.
     * @return bool Returns true if the property is readonly, false otherwise.
     */
    public function isPropertyReadonly(string $propertyName): bool {
        $properties = $this->getProperties([$propertyName]);
        if (!$properties) {
            return false;
        }

        $property = $properties[$propertyName];
        if (($property['readonly'] ?? false) === false) {
            return false;
        }

        return true;
    }

    /**
     * Retrieves the default value for a given property.
     *
     * @param string $propertyName The name of the property.
     *
     * @return mixed The default value of the property, or null if not found.
     */
    public function getDefaultValue(string $propertyName): mixed {
        return $this->getProperties([$propertyName])[$propertyName]['default'] ?? null;
    }

    /**
     * Returns the table name associated with the model.
     *
     * @return string Associated DB table name.
     */
    public function getTableName(): string {
        $className = strtolower(substr(strrchr(get_called_class(), '\\'), 1));

        return strtolower(($this->tableName ?? 'models_' . $className));
    }

    /**
     * Returns the database object associated with the model.
     * Allows to extend the model with custom database functionality.
     *
     * @return Database The database object associated with the model.
     */
    public function getDatabase(): Database {
        return $this->database;
    }

    /**
     * Creates a new instance of the model with the given data.
     * The new instance will be a clone of the current instance with the updated data.
     *
     * @param array $data The data to set on the model.
     * @return ModelInterface The new instance of the model with the updated data.
     */
    public function withData(array $data): ModelInterface {
        $model = clone $this;
        $model->data = $data;
        $model->cleanupPropertiesAndData();
        return $model;
    }

    /**
     * Get the value of the data key.
     *
     * @param string $name The name of the data key.
     *
     * @throws ModelException If the property does not exist.
     * @return mixed The value of the property.
     */
    public function __get($name): mixed {
        return $this->getData([$name])[$name];
    }

    /**
     * Sets the value of a model data key.
     *
     * @param string $name The name of the data key.
     * @param mixed $value The value to be set.
     *
     * @return void
     */
    public function __set($name, $value): void {
        $this->setData([$name => $value]);
    }

    /**
     * Checks if a data key is set.
     *
     * @param string $name The name of the data key to check.
     * @return bool Returns true if the data key is set, false otherwise.
     */
    public function __isset($name): bool {
        return array_key_exists($name, $this->data);
    }

    /**
     * Unsets a model's data key.
     *
     * @param string $name The name of the data key to unset.
     * @return void
     */
    public function __unset($name): void {
        unset($this->data[$name]);
    }

    /**
     * Retursn mode's data array.
     *
     * @return array The model data as an array.
     */
    public function __toArray(): array {
        return $this->getData();
    }

    /**
     * Returns a JSON representation of the model data.
     *
     * @return string The JSON representation of the model data.
     */
    public function __toString(): string {
        return json_encode($this->data);
    }
}
