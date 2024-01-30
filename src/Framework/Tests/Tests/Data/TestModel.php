<?php

namespace Framework\Tests\Tests\Data;

use Framework\Event\EventDispatcher;
use Framework\Database\Database;
use Framework\Logger\Logger;
use Framework\Model\Model;

class TestModel extends Model {
    public function __construct (Database $database, Logger $logger, EventDispatcher $eventDispatcher) {
        parent::__construct($database, $logger, $eventDispatcher);
    }
}
