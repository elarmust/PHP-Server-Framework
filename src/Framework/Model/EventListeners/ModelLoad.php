<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Model\EventListeners;

use Framework\Logger\Logger;
use Framework\Event\EventListenerInterface;
use Framework\Model\Exception\ModelException;

class ModelLoad implements EventListenerInterface {
    public function __construct(private Logger $logger) {
    }

    public function __invoke(object $event): void {
        $model = $event->getModel();
        $modelId = $event->getId();

        // Load the model from database.
        if ($model->getProperties(['deleted_at']) && !$event->getIncludeArchived()) {
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

        $event->setModel($model->withData($data[0]));
    }
}
