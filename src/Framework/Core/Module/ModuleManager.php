<?php

/**
 * Class for managing modules.
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Core\Module;

use Exception;
use Psr\Log\LogLevel;
use Framework\Logger\Logger;
use Framework\Core\ClassContainer;

class ModuleManager {
    private ClassContainer $classContainer;
    private Logger $logger;
    private array $modules = [];

    public function __construct(ClassContainer $classContainer, Logger $logger) {
        $this->classContainer = $classContainer;
        $this->logger = $logger;
        $moduleConfigsUnordered = [];

        // Load other module configurations into array
        foreach (['modules', 'vendor'] as $path) {
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
                    if (!file_exists($modulePath . '/module.php') || !file_exists($modulePath . '/Enable.php')) {
                        continue;
                    }

                    require_once $modulePath . '/module.php';
                    if (!isset($moduleInfo['name'])) {
                        throw new Exception('Module ' . $vendor . '_' . $module . ' is missing a name!');
                    }

                    if (!isset($moduleInfo['description'])) {
                        throw new Exception('Module ' . $vendor . '_' . $module . ' is missing description!');
                    }

                    if (!isset($moduleInfo['version'])) {
                        throw new Exception('Module ' . $vendor . '_' . $module . ' is missing version!');
                    }

                    $moduleData['name'] = $moduleInfo['name'];
                    $moduleData['vendor'] = $vendor;
                    $moduleData['version'] = $moduleInfo['version'];
                    $moduleData['path'] = $modulePath;
                    $moduleData['classPath'] = $vendor . '\\' . $module;

                    $moduleData['dependencies'] = array_values(array_unique($moduleInfo['dependencies'] ?? []));
                    $moduleConfigsUnordered[$moduleData['classPath']] = $moduleData;
                }
            }
        }

        foreach ($moduleConfigsUnordered as $module => $moduleData) {
            $arrayToBeSorted[$module] = $moduleData['dependencies'] ?? [];
        }

        foreach ($this->orderModules($arrayToBeSorted ?? []) as $module) {
            $this->modules[$module] = new Module(...$moduleConfigsUnordered[$module]);
        }
    }

    public function loadModule(Module $moduleName) {
        $enableClass = $moduleName->getClassPath() . '\\Enable';
        $moduleEnable = $this->classContainer->get($enableClass, cache: false);
        $moduleEnable->onEnable();
    }

    public function unloadModule(Module $moduleName) {
        $enableClass = $moduleName->getClassPath() . '\\Enable';
        $moduleEnable = $this->classContainer->get($enableClass, cache: false);
        $moduleEnable->onDisable();
    }

    public function getModules(): array {
        return $this->modules;
    }

    public function getModule(string $moduleName): ?Module {
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
