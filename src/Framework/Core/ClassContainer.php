<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Core;

use ReflectionClass;
use ReflectionException;
use Psr\Container\ContainerInterface;
use Framework\Core\Exception\NotFoundException;

class ClassContainer implements ContainerInterface {
    protected array $objectInstances = [];

    /**
     * Get and instantiate classes
     *
     * @param string $className // Class path.
     * @param array $args // Parameters to pass to the class.
     * @param string $alias // Instance alias.
     * @param bool $cache // Use cache cache for results.
     * @return Object
     * @throws NotFoundException
     */
    public function get(string $className, array $args = [], string $alias = 'default', bool $cache = true): object {
        if (isset($this->objectInstances[$className][$alias]) && $cache) {
            return $this->objectInstances[$className][$alias];
        }

        if (!$this->has($className)) {
            throw new NotFoundException('Class ' . $className . ' could not be found!');
        }

        $return = new $className(...$this->prepareArguments($className, $args));
        if (!isset($this->objectInstances[$className][$alias]) && $cache) {
            $this->objectInstances[$className][$alias] = $return;
        }

        return $return;
    }

    public function has(string $className): bool {
        return class_exists($className);
    }

    public function set(object $class, $alias = 'default'): void {
        $this->objectInstances[$class::class][$alias] = $class;
    }

    public function isInitialized(string $className, $alias = 'default'): bool {
        return isset($this->objectInstances[$className][$alias]);
    }

    /**
     * Get prepared class arguments
     *
     * @param string $classPath // Class path.
     * @param array $params // Parameters to pass to the object.
     * @return array
     * @throws ReflectionException
     */
    public function prepareArguments(string $classPath, array $params = []): array {
        $objectParams = $params;
        $reflection = new ReflectionClass($classPath);
        $constructor = $reflection->getConstructor();
        if ($constructor === null || !$constructor->isPublic()) {
            return [];
        }

        $classParams = $constructor->getParameters();
        $x = 0;
        $classParamTypeNames = [];
        $paramClasses = [];

        foreach ($classParams as $classParam) {
            $classParamTypeNames[] = $classParam->getType()->getName();
        }

        foreach ($params as $paramClass) {
            if (gettype($paramClass) == 'object' && in_array(get_class($paramClass), $classParamTypeNames)) {
                $paramClasses[] = get_class($paramClass);
            }
        }

        $finalParamCount = count($params);
        foreach ($classParamTypeNames as $classParam) {
            if ($this->has($classParam) && !in_array($classParam, $paramClasses)) {
                $objectParams[$x] = $this->get($classParam);
                $finalParamCount++;
            } else {
                $objectParams[$x] = array_shift($params);
            }

            $x++;
        }

        $keysToKeep = array_slice(array_keys($objectParams), 0, $finalParamCount);
        $objectParams = array_intersect_key($objectParams, array_flip($keysToKeep));

        return $objectParams;
    }
}