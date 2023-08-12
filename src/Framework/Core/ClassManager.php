<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Core;

use Exception;
use ReflectionClass;
use ReflectionException;

class ClassManager {
    static array $objectInstances = [];

    /**
     * Get and create class instances.
     * Returns a singletonized class
     *
     * @param string $classPath // Class path.
     * @param array $params // Parameters to pass to the class.
     * @param string $name // Instance name. Useful for having multiple instances of the same class.
     * @param bool $overWrite // Overwrite existing instance with same ID
     * @return Object
     * @throws Exception
     */
    public function getClassInstance(string $classPath, array $params = [], string $name = 'default', bool $overWrite = false): Object {
        if (!class_exists($classPath)) {
            throw new Exception('Class ' . $classPath . ' not found!');
        }

        if (!isset(self::$objectInstances[$name][$classPath]) || $overWrite) {
            self::$objectInstances[$name][$classPath] = $this->getTransientClass($classPath, $params);
        }

        return self::$objectInstances[$name][$classPath];
    }

    public function setClassInstance(Object $class, string $name = 'default') {
        self::$objectInstances[$name][$class::class] = $class;
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
        if ($reflection->getConstructor() === null) {
            return [];
        }

        $classParams = $reflection->getConstructor()->getParameters();
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
            if (class_exists($classParam) && !in_array($classParam, $paramClasses)) {
                $objectParams[$x] = $this->getClassInstance($classParam);
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

    /**
     * A convenient wrapper for prepareArguments()
     * Returns a transient class
     *
     * @param string $classPath // Class path.
     * @param array $params // Parameters to pass to the object.
     * @return Object
     * @throws ReflectionException
     */
    public function getTransientClass(string $classPath, array $params = []): Object {
        return new $classPath(...$this->prepareArguments($classPath, $params));
    }
}