<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Model\EventListeners;

use Framework\Model\Exception\ModelException;
use Framework\Event\EventListenerInterface;
use Framework\Logger\Logger;

class ModelRestore implements EventListenerInterface {
    public function __construct(private Logger $logger) {
    }

    public function __invoke(object $event): void {
        $model = $event->getModel();

        if (!in_array('deleted_at', $model->getDataKeys())) {
            return;
        }

        $status = $model->getDatabase()->update($model->getTableName(), ['deleted_at' => null], ['id' => $model->id()]);
        if (!$status) {
            throw new ModelException('Failed to restore model from database!');
        }

        $model->deleted_at = null;
    }
}
