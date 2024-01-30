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
        $data['id'] = $model->getDatabase()->insert($model->getTableName(), $createData);
        if ($data['id'] === false) {
            throw new ModelException('Failed to create model in database!');
        }

        // Set the model to new model with the data.
        $event->setModel($model->withData($data));
    }
}
