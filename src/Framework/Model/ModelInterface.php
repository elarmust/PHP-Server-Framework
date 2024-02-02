<?php

/**
 * An interface that defines common methods for loading, saving, deleting, and retrieving data for models.
 *
 * @copyright Elar Must.
 */

namespace Framework\Model;

interface ModelInterface {
    /**
     * Load data for the model from the database.
     *
     * @param string|int $modelId The ID of the model to load.
     *
     * @return ModelInterface
     */
    public function load(string|int $modelId): ModelInterface;

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
}
