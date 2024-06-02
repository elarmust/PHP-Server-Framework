<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Model\EventListeners;

use Framework\Model\Exception\ModelException;
use Framework\Event\EventListenerInterface;
use Framework\Logger\Logger;

class ModelCreate implements EventListenerInterface {
    public function __construct(private Logger $logger) {
    }

    public function __invoke(object $event): void {
        $model = $event->getModel();
        $data = $event->getData();
        $properties = $model->getProperties();

        // Initialize the data array with the default data.
        $createData = [];
        foreach ($properties as $key => $value) {
            if ($key === 'created_at') {
                $data['created_at'] = date('Y-m-d H:i:s');
                $createData[$key] = $data['created_at'];
            } elseif ($model->isPropertyPersistent($key)) {
                $createData[$key] = $data[$key] ?? $model->getDefaultValue($key);
            }
        }

        // Insert data to database and get the id.
        $autoIncrementId = $model->getDatabase()->insert($model->getTableName(), $createData);
        if ($autoIncrementId === false) {
            throw new ModelException('Failed to create model in database!');
        }

        // Insert will return 0 if the id does not use auto increment.
        // In that case, use the id from the data or if that is not set, use the inserted id.
        $data['id'] = $autoIncrementId ?: $createData['id'] ?? $autoIncrementId;

        // Set the model to new model with the data.
        $event->setModel($model->withData($data));
    }
}
