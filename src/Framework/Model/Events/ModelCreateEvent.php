<?php

/**
 * @copyright  Elar Must
 */

namespace Framework\Model\Events;

use Framework\Model\ModelInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class ModelCreateEvent implements StoppableEventInterface {
    private bool $stopped = false;

    /**
     * @param ModelInterface $model The model associated with the event.
     */
    public function __construct(private ModelInterface $model, private array $data) {
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
     * Get the data associated with the event.
     *
     * @return array The data associated with the event.
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * Set the data associated with the event.
     *
     * @param array $data The data to set.
     *
     * @return void
     */
    public function setData(array $data): void {
        $this->data = $data;
    }

    /**
     * Check if event propagation is stopped.
     *
     * @return bool True if event propagation is stopped, false otherwise.
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
