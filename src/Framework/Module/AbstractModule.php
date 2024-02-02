<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Module;

use Framework\Framework;

abstract class AbstractModule implements ModuleInterface {
    /**
     * @var Framework Framework instance.
     */
    private Framework $framework;

    /**
     * @var string Name of the module.
     */
    private string $name;

    /**
     * @var string Path to the module.
     */
    private string $path;

    /**
     * @var array An array of modules that should be loaded before this module.
     */
    protected array $loadBefore = [];

    /**
     * @var array An array of modules that should be loaded after this module.
     */
    protected array $loadAfter = [];

    /**
     * Constructor for internal use.
     *
     * @param Framework $framework Framework instance.
     * @param string $name Name of the module.
     * @param string $path Path to the module.
     */
    final public function __construct(
        Framework $framework,
        string $name,
        string $path
    ) {
        $this->framework = $framework;
        $this->name = $name;
        $this->path = $path;
    }

    /**
     * Initialize module before workers start.
     * This is called only once when server starts.
     * This should be used to initialize data shared between workers.
     * 
     * Not mandatory to implement.
     *
     * @param Framework $framework Framework instance.
     */
    public function beforeWorkers(Framework $framework): void {
    }

    /**
     * Initialize module when worker starts.
     * This is called once for every worker.
     *
     * @param Framework $framework Framework instance unique to the worker.
     */
    abstract public function onWorkerStart(Framework $framework): void;

    /**
     * Perform cleanup when regular worker stops.
     * This is called once for every worker.
     * 
     * Not mandatory to implement.
     *
     * @param Framework $framework Framework instance unique to the worker.
     */
    public function onWorkerStop(Framework $framework): void {
    }

    /**
     * Returns the framework instance associated with this module.
     *
     * @return Framework The framework instance.
     */
    final public function getFramework(): Framework {
        return $this->framework;
    }

    /**
     * Get the name of the module.
     *
     * @return string The name of the module.
     */
    final public function getName(): string {
        return $this->name;
    }

    /**
     * Get the path of the module.
     *
     * @return string The path of the module.
     */
    final public function getPath(): string {
        return $this->path;
    }

    /**
     * Get the version of the module.
     *
     * @return string The version of the module.
     */
    public function getVersion(): string {
        return '1.0.0';
    }

    /**
     * Get the list of modules that should be loaded after this module.
     *
     * @return array The list of modules to load after.
     */
    final public function loadAfter(): array {
        return $this->loadAfter;
    }

    /**
     * Get the list of modules that should be loaded before this module.
     *
     * @return array The list of modules to load before.
     */
    final public function loadBefore(): array {
        return $this->loadBefore;
    }

    /**
     * Get the description of the module.
     *
     * @return string The description of the module.
     */
    public function getDescription(): string {
        return '';
    }
}
