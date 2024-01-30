<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Model\EventListeners;

use Framework\Model\Exception\ModelException;
use Framework\Event\EventListenerInterface;
use Framework\Logger\Logger;

class ModelSet implements EventListenerInterface {
    public function __construct(private Logger $logger) {
    }

    public function __invoke(object $event): void {
        $model = $event->getModel();
        $data = $event->getData();

        foreach ($data as $key => $value) {
            // Readonly values cannot be set on existing models.
            if ($model->isPropertyReadonly($key) && $model->id() !== null) {
                throw new ModelException('Cannot set readonly key: ' . $key);
            }
        }

        $event->setData(array_merge($model->getData(), $data));
    }
}
