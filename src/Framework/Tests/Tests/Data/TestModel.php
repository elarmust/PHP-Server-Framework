<?php

namespace Framework\Tests\Tests\Data;

use Framework\Model\Model;
use Framework\Container\ClassContainer;

class TestModel extends Model {
    public function __construct (ClassContainer $classContainer) {
        parent::__construct(...$classContainer->prepareFunctionArguments(parent::class));
    }
}
