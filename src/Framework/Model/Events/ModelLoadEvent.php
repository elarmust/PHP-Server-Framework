<?php

/**
 * @copyright  Elar Must
 */

namespace Framework\Model\Events;

use Framework\Model\ModelInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class ModelLoadEvent implements StoppableEventInterface {
    private bool $stopped = false;

    /**
     * @param ModelInterface $model The model associated with the event.
     * @param string|int $id The ID associated with the event.
     * @param bool $includeArchived Whether to include archived models.
     */
    public function __construct(private ModelInterface $model, private string|int $id, private bool $includeArchived) {
    }

    /**
     * Get the model associated with the event.
     *
     * @return ModelInterface The model associated with the event.
     */
    public function getModel(): ModelInterface {
        return $this->model;
    }

    /**
     * Get the value of includeArchived.
     *
     * @return bool The value of includeArchived.
     */
    public function getIncludeArchived(): bool {
        return $this->includeArchived;
    }

    /**
     * Set the value of includeArchived.
     *
     * @param bool $includeArchived The new value of includeArchived.
     *
     * @return void
     */
    public function setIncludeArchived(bool $includeArchived): void {
        $this->includeArchived = $includeArchived;
    }

    /**
     * Set the model associated with the event.
     *
     * @param ModelInterface $model The model to set.
     *
     * @return void
     */
    public function setModel(ModelInterface $model): void {
        $this->model = $model;
    }

    /**
     * Get the ID associated with the event.
     *
     * @return string|int The ID associated with the event.
     */
    public function getId(): string|int {
        return $this->id;
    }

    /**
     * Set the ID associated with the event.
     *
     * @param string|int $id The ID to set.
     *
     * @return void
     */
    public function setId(string|int $id): void {
        $this->id = $id;
    }

    /**
     * Check if the event propagation is stopped.
     *
     * @return bool True if the event propagation is stopped, false otherwise.
     */
    public function isPropagationStopped(): bool {
        return $this->stopped;
    }

    /**
     * Stops the propagation of the event.
     *
     * @return void
     */
    public function stopPropagation(): void {
        $this->stopped = true;
    }
}
