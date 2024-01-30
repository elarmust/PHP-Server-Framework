<?php

/**
 * @copyright  Elar Must
 */

namespace Framework\Model\Events;

use Framework\Model\ModelInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class ModelSaveEvent implements StoppableEventInterface {
    private bool $stopped = false;

    /**
     * @param ModelInterface $model The model associated with the event.
     */
    public function __construct(private ModelInterface &$model) {
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
     * Check if event propagation is stopped.
     *
     * @return bool True if event propagation is stopped, false otherwise.
     */
    public function isPropagationStopped(): bool {
        return $this->stopped;
    }
}
