<?php

namespace Framework\Http\Session;

use Framework\Model\Model;
use Framework\Container\ClassContainer;
use Framework\Database\Database;

class SessionModel extends Model {
    protected array $properties = [
        'id',
        'data',
        'timestamp'
    ];

    public function __construct (ClassContainer $classContainer, Database $database) {
        parent::__construct(...$classContainer->prepareFunctionArguments(parent::class, parameters: [$database]));
    }
}
