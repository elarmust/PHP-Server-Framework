<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Model\EventListeners;

use Framework\Model\Exception\ModelException;
use Framework\Event\EventListenerInterface;
use Framework\Logger\Logger;

class ModelDelete implements EventListenerInterface {
    public function __construct(private Logger $logger) {
    }

    public function __invoke(object $event): void {
        $model = $event->getModel();

        if ($model->getProperties(['deleted_at'])) {
            $deleteDate = date('Y-m-d H:i:s');
            $model->deleted_at = $deleteDate;
            $model->getDatabase()->update($model->getTableName(), ['deleted_at' => $deleteDate], ['id' => $model->id()]);
        } else {
            $status = $model->getDatabase()->delete($model->getTableName(), ['id' => $model->id()]);
            if (!$status) {
                throw new ModelException('Failed to delete model from database!');
            }
        }
    }
}
