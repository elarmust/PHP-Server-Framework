<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Container;

use ReflectionClass;
use ReflectionException;
use Psr\Container\ContainerInterface;
use InvalidArgumentException;

class ClassContainer implements ContainerInterface {
    /**
     * @var array $objectInstances An array to store the instances of objects.
     */
    protected array $objectInstances = [];

    /**
     * Get and instantiate classes
     * Returns a reference to the object instance.
     *
     * @param string $className Class path.
     * @param array $args Parameters to pass to the class.
     * @param string $alias Instance alias.
     * @param bool $useCache Use a cached or new instance.
     *
     * @throws InvalidArgumentException
     * @return Object
     */
    public function get(string $className, array $args = [], string $alias = 'default', bool $useCache = true): object {
        if (isset($this->objectInstances[$className][$alias]) && $useCache) {
            return $this->objectInstances[$className][$alias];
        }

        if (!$this->has($className)) {
            throw new InvalidArgumentException('Class ' . $className . ' could not be found!');
        }

        $return = new $className(...$this->prepareFunctionArguments($className, parameters: $args));
        if (!isset($this->objectInstances[$className][$alias]) && $useCache) {
            $this->objectInstances[$className][$alias] = $return;
        }

        return $return;
    }

    /**
     * Checks if a class exists.
     *
     * @param string $className The name of the class to check.
     *
     * @return bool Returns true if the class exists, false otherwise.
     */
    public function has(string $className): bool {
        return class_exists($className);
    }

    /**
     * Sets an object instance in the container.
     *
     * @param object $class The object instance to set.
     * @param string $alias The alias for the object instance, defaults to 'default'.
     *
     * @return void
     */
    public function set(object $class, $alias = 'default'): void {
        $this->objectInstances[$class::class][$alias] = $class;
    }

    /**
     * Checks if an object instance has been initialized in the container.
     *
     * @param string $className The name of the class.
     * @param string $alias The alias for the object instance, defaults to 'default'.
     *
     * @return bool Returns true if the object instance has been initialized, false otherwise.
     */
    public function isInitialized(string $className, $alias = 'default'): bool {
        return isset($this->objectInstances[$className][$alias]);
    }

    /**
     * Get prepared function arguments
     *
     * @param string $classPath Class path.
     * @param string $functionName Function name, defaults to __construct.
     * @param array $parameters Parameters to pass to the object.
     *
     * @throws ReflectionException
     * @return array
     */
    public function prepareFunctionArguments(string $classPath, string $functionName = '__construct', array $parameters = []): array {
        $objectParams = $parameters;
        $reflection = new ReflectionClass($classPath);
        $function = $reflection->hasMethod($functionName) ? $reflection->getMethod($functionName) : null;

        if ($function === null || !$function->isPublic()) {
            return [];
        }

        $functionParameters = $function->getParameters();
        $x = 0;
        $functionParameterTypeNames = [];
        $paramClasses = [];

        foreach ($functionParameters as $classParam) {
            $functionParameterTypeNames[] = $classParam->getType()->getName();
        }

        foreach ($parameters as $paramClass) {
            if (gettype($paramClass) == 'object' && in_array(get_class($paramClass), $functionParameterTypeNames)) {
                $paramClasses[] = get_class($paramClass);
            }
        }

        $finalParamCount = count($parameters);
        foreach ($functionParameterTypeNames as $classParam) {
            if ($this->has($classParam) && !in_array($classParam, $paramClasses)) {
                $objectParams[$x] = $this->get($classParam);
                $finalParamCount++;
            } else {
                $objectParams[$x] = array_shift($parameters);
            }

            $x++;
        }

        $keysToKeep = array_slice(array_keys($objectParams), 0, $finalParamCount);
        $objectParams = array_intersect_key($objectParams, array_flip($keysToKeep));

        return $objectParams;
    }
}
