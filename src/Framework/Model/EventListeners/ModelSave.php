<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Model\EventListeners;

use Framework\Model\Exception\ModelException;
use Framework\Event\EventListenerInterface;
use Framework\Logger\Logger;

class ModelSave implements EventListenerInterface {
    public function __construct(private Logger $logger) {
    }

    public function __invoke(object $event): void {
        $model = $event->getModel();
        $properties = $model->getProperties();

        // Set saved_at, if it has a default value.
        if ($model->getProperties(['saved_at'])) {
            $model->saved_at = date('Y-m-d H:i:s');
        }

        $save = [];
        $modelData = $model->getData();
        foreach ($properties as $key => $value) {
            // Save persistent data.
            if ($model->isPropertyPersistent($key)) {
                $save[$key] = $modelData[$key] ?? $model->getDefaultValue($key);
            }
        }

        if ($save) {
            $status = $model->getDatabase()->update($model->getTableName(), $save, ['id' => $model->id()]);
            if (!$status) {
                throw new ModelException('Failed to save model to database!');
            }
        }
    }
}
