<?php

/**
 * @copyright  Elar Must
 */

namespace Framework\Model\Events;

use Framework\Model\ModelInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class ModelSetEvent implements StoppableEventInterface {
    private bool $stopped = false;

    /**
     * @param ModelInterface $model The model associated with the event.
     */
    public function __construct(private ModelInterface &$model, private array $data) {
    }

    /**
     * Gets the model associated with the event.
     *
     * @return ModelInterface The model associated with the event.
     */
    public function getModel(): ModelInterface {
        return $this->model;
    }

    /**
     * Gets the data associated with the event.
     *
     * @return array The data associated with the event.
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * Sets the data associated with the event.
     *
     * @param array $data The data to set.
     *
     * @return void
     */
    public function setData(array $data): void {
        $this->data = $data;
    }

    /**
     * Checks if the event propagation is stopped.
     *
     * @return bool True if the event propagation is stopped, false otherwise.
     */
    public function isPropagationStopped(): bool {
        return $this->stopped;
    }
}
