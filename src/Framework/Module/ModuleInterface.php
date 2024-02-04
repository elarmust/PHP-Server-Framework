<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Module;

use Framework\Framework;

interface ModuleInterface {
    /**
     * Constructor for the module.
     *
     * @param Framework $framework Framework instance.
     * @param string $name Name of the module.
     * @param string $path Path to the module.
     */
    public function __construct(
        Framework $framework,
        string $name,
        string $path
    );

    /**
     * Initialize module before workers start.
     * This is called only once when server starts.
     * This should be used to initialize data shared between workers.
     * Use resources like database connections with caution here,
     * as they will be copied to every worker and will cause conflicts.
     *
     * @param Framework $framework Framework instance.
     */
    public function beforeWorkers(Framework $framework): void;

    /**
     * Initialize module when worker starts.
     * This is called once for every worker.
     *
     * @param Framework $framework Framework instance unique to the worker.
     */
    public function onWorkerStart(Framework $framework): void;

    /**
     * Perform cleanup when regular worker stops.
     * This is called once for every worker.
     *
     * @param Framework $framework Framework instance unique to the worker.
     */
    public function onWorkerStop(Framework $framework): void;

    /**
     * Get the Framework instance.
     *
     * @return Framework Framework instance.
     */
    public function getFramework(): Framework;

    /**
     * Get the name of the module.
     *
     * @return string The name of the module.
     */
    public function getName(): string;

    /**
     * Get the path to the module.
     *
     * @return string Module name.
     */
    public function getPath(): string;

    /**
     * Get the version of the module.
     *
     * @return string Module version.
     */
    public function getVersion(): string;

    /**
     * Get the list of modules that should be loaded before this module.
     *
     * @return array A list of modules to load before.
     */
    public function loadBefore(): array;

    /**
     * Get the list of modules that should be loaded after this module.
     *
     * @return array A list of modules to load after.
     */
    public function loadAfter(): array;

    /**
     * Get the description of the module.
     *
     * @return string Module description.
     */
    public function getDescription(): string;
}
