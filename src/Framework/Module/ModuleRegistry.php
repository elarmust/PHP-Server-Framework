<?php

/**
 * Class for managing modules.
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Module;

use Psr\Log\LogLevel;
use RuntimeException;
use Framework\Framework;
use Framework\Logger\Logger;
use InvalidArgumentException;
use Framework\Core\ClassContainer;
use Framework\Module\AbstractModule;

class ModuleRegistry {
    private Framework $framework;
    private ClassContainer $classContainer;
    private Logger $logger;
    private array $modules = [];
    private array $loadedModules = [];
    public const MODULE_PATHS = ['Modules', 'Vendor'];

    public function __construct(Framework $framework, ClassContainer $classContainer, Logger $logger) {
        $this->framework = $framework;
        $this->classContainer = $classContainer;
        $this->logger = $logger;
    }

    public function init() {
        
    }

    /**
     * Attempts to find and return list of available modules.
     * 
     * @throws RuntimeException
     * @return array An array of available module names. module name => [path, namespace]
     */
    public function findModules(): array {
        $modulesFound = [];
        // Load other module configurations into array
        foreach ($this::MODULE_PATHS as $path) {
            // Ignore files.
            if (!is_dir(BASE_PATH . '/' . $path)) {
                continue;
            }

            $vendors = array_diff(scandir(BASE_PATH . '/' . $path), ['..', '.']);
            foreach ($vendors as $vendor) {
                // Ignore files.
                if (!is_dir(BASE_PATH . '/' . $path . '/' . $vendor)) {
                    continue;
                }

                $modules = array_diff(scandir(BASE_PATH . '/' . $path . '/' . $vendor), ['..', '.']);
                foreach ($modules as $module) {
                    $modulePath = BASE_PATH . '/' . $path . '/' . $vendor . '/' . $module;
                    // Ignore folders with no module configuration file or load file.
                    if (!file_exists($modulePath . '/' . $module . '.php')) {
                        continue;
                    }

                    $moduleName = $vendor . '\\' . $module;
                    $moduleClass = $moduleName . '\\' . $module;
                    if (!class_exists($moduleClass) || !in_array(ModuleInterface::class, class_implements($moduleClass))) {
                        throw new InvalidArgumentException($moduleName . '\\' . $module . ' must implement ' . ModuleInterface::class . '!');
                    }

                    if (isset($modulesFound[$moduleName])) {
                        throw new RuntimeException('Ambiguous module ' . $moduleName . ' in ' . $modulePath . '! ' . $moduleName . ' already exists in ' . $modulesFound[$moduleName][0] . '.');
                    }

                    $modulesFound[$moduleName] = $modulePath;
                }
            }
        }

        $this->modules = array_merge($this->modules, $modulesFound);
        return $modulesFound;

        foreach ($moduleConfigsUnordered as $module => $moduleData) {
            $arrayToBeSorted[$module] = $moduleData['dependencies'] ?? [];
        }

        foreach ($this->orderModules($arrayToBeSorted ?? []) as $module) {
            $this->modules[$module] = new AbstractModule(...$moduleConfigsUnordered[$module]);
        }
    }

    public function loadModule(string $modulePath): ModuleInterface {
        $pathParts = explode('/', $modulePath);
        if (count($pathParts) < 2) {
            throw new InvalidArgumentException('Module path ' . $modulePath . ' appears to be invalid!');
        }

        $lastKey = array_key_last($pathParts);
        $vendor = $pathParts[$lastKey - 1];
        $module = $pathParts[$lastKey];
        $moduleName = $vendor . '\\' . $module;
        $moduleClass = $moduleName . '\\' . $module;
        if (!class_exists($moduleClass)) {
            throw new InvalidArgumentException('Module class ' . $moduleClass . ' could not be located!');
        }

        $module = $this->classContainer->get($moduleClass, [$this->framework, $moduleName, $modulePath]);
        $module->load();
        $this->loadedModules[$moduleName] = $module;
        return $this->loadedModules[$moduleName];
    }

    public function unloadModule(ModuleInterface $moduleName) {
        $enableClass = $moduleName->getClassPath() . '\\Module';
        $moduleEnable = $this->classContainer->get($enableClass, cache: false);
        $moduleEnable->onDisable();
    }

    public function getModules(): array {
        return $this->loadedModules;
    }

    public function getAllModules() {
        return $this->modules;
    }

    public function getModule(string $moduleName): ?ModuleInterface {
        return $this->modules[$moduleName] ?? null;
    }

    private function orderModules(array $graph): array {
        $inDegree = [];
        $sorted = [];

        // Initialize in-degree for each vertex
        foreach ($graph as $vertex => $adjList) {
            $inDegree[$vertex] = 0;
        }

        // Calculate in-degree for each vertex
        foreach ($graph as $vertex => $adjList) {
            foreach ($adjList as $adjVertex) {
                if (array_key_exists($adjVertex, $inDegree)) {
                    $inDegree[$adjVertex]++;
                } else {
                    unset($graph[$vertex][array_search($adjVertex, $graph[$vertex])]);
                    $this->logger->log(LogLevel::WARNING, 'Module \'' . $vertex . '\' has invalid dependency \'' . $adjVertex . '\'', identifier: 'framework');
                }
            }
        }

        // Initialize queue with vertices that have no incoming edges
        $queue = array_filter(array_keys($inDegree), function ($vertex) use ($inDegree) {
            return $inDegree[$vertex] == 0;
        });

        // Perform topological sort
        while (!empty($queue)) {
            $vertex = array_shift($queue);
            $sorted[] = $vertex;

            foreach ($graph[$vertex] as $adjVertex) {
                $inDegree[$adjVertex]--;
                if ($inDegree[$adjVertex] == 0) {
                    $queue[] = $adjVertex;
                }
            }
        }

        // Check for circular dependency
        if (count($sorted) != count($graph)) {
            foreach ($graph as $node => $vertexes) {
                if (!in_array($node, $sorted)) {
                    $this->logger->log(LogLevel::WARNING, 'Module \'' . $node . '\' has circular dependency!', identifier: 'framework');
                }
            }
        }

        return array_reverse($sorted);
    }
}
