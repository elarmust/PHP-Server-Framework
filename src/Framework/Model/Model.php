<?php

/**
 * Represents an model object that interacts with the database.
 *
 * @copyright Elar Must.
 */

namespace Framework\Model;

use Framework\Model\Exception\ModelException;
use Framework\Model\Events\ModelRestoreEvent;
use Framework\Model\Events\ModelCreateEvent;
use Framework\Model\Events\ModelDeleteEvent;
use Framework\Model\Events\ModelLoadEvent;
use Framework\Model\Events\ModelSaveEvent;
use Framework\Model\Events\ModelSetEvent;
use Framework\Event\EventDispatcher;
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
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        private Database $database,
        private Logger $logger,
        private EventDispatcher $eventDispatcher
    ) {
        // Set properties = defaultProperties + properties.
        $this->properties = array_replace($this->defaultProperties, $this->properties);
        // Clean up bad properties and data.
        $this->cleanupPropertiesAndData();
    }

    /**
     * Load the model by its ID.
     *
     * @param int $modelId The ID of the model to load.
     * @param bool $includeArchived Whether or not to include archived entries.
     *
     * @return ModelInterface
     */
    public function load(int $modelId, bool $includeArchived = false): ModelInterface {
        $modelInstance = $this->withData([]);
        return $this->eventDispatcher->dispatch(new ModelLoadEvent($modelInstance, $modelId, $includeArchived))->getModel();
    }

    /**
     * Creates a new record in the database with the given data.
     *
     * @param array $data = [] Data to be inserted into the database.
     *
     * @return ModelInterface Newly created model instance.
     */
    public function create(array $data = []): ModelInterface {
        $model = clone $this;

        // Set the data on the model.
        $model->data = $model->eventDispatcher->dispatch(new ModelSetEvent($model, $data))->getData();

        // Create the model.
        return $model->eventDispatcher->dispatch(new ModelCreateEvent($model, $model->getData()))->getModel();
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
        if ($this->id() === null) {
            throw new ModelException('Cannot save non-instanciated model.');
        }

        $this->data = $this->eventDispatcher->dispatch(new ModelSetEvent($this, $data))->getData();
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

        $this->eventDispatcher->dispatch(new ModelSaveEvent($this));
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

        $this->eventDispatcher->dispatch(new ModelDeleteEvent($this));
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

        $this->eventDispatcher->dispatch(new ModelRestoreEvent($this));
        return $this;
    }

    /**
     * Get the ID of the model.
     *
     * @return int|null The ID of the model, or null if it hasn't been assigned an ID yet.
     */
    public function id(): ?int {
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
    private function arrayRemoveRecursive(array $array1, array $array2): array {
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
    private function cleanupPropertiesAndData(): void {
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
     * Returns the event dispatcher associated with the model.
     * Allows to extend the model with custom event listeners.
     *
     * @return EventDispatcher The event dispatcher.
     */
    public function getEventDispatcher(): EventDispatcher {
        return $this->eventDispatcher;
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
    public function __get($name) {
        if (!array_key_exists($name, $this->data)) {
            throw new ModelException('Invalid key: ' . $name);
        }

        return $this->data[$name];
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
