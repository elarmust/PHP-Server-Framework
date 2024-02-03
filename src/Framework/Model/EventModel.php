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

abstract class EventModel extends Model implements ModelInterface {
    /**
     * @param Database $database
     * @param Logger $logger
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        Database $database,
        Logger $logger,
        private EventDispatcher $eventDispatcher
    ) {
        parent::__construct($database, $logger);
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
     * Returns the event dispatcher associated with the model.
     * Allows to extend the model with custom event listeners.
     *
     * @return EventDispatcher The event dispatcher.
     */
    public function getEventDispatcher(): EventDispatcher {
        return $this->eventDispatcher;
    }
}
