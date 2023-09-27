<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Core\Exception;

use Psr\Container\NotFoundExceptionInterface;
use InvalidArgumentException;

class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface {
}
