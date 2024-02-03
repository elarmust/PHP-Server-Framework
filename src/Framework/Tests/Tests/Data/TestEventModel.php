<?php

namespace Framework\Tests\Tests\Data;

use Framework\Model\EventModel;
use Framework\Container\ClassContainer;

class TestEventModel extends EventModel {
    public function __construct (ClassContainer $classContainer) {
        parent::__construct(...$classContainer->prepareFunctionArguments(parent::class));
    }
}
