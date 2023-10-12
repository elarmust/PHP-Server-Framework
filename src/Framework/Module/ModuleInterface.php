<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Module;

use Framework\Framework;

interface ModuleInterface {
    public function init(
        Framework $framework,
        string $name,
        string $path
    );

    /*
     * This method will be called when module is loaded.
     * This should contain code needed to set up module functionalities.
     */
    public function Load();

    /*
     * This method will be called when module is unloaded.
     * This should contain code needed to disable module functionalities.
     */
    public function Unload();

    public function getFramework(): Framework;

    public function getName(): string;

    public function getPath(): string;

    public function getVersion(): string;

    public function loadBefore(): array;

    public function loadAfter(): array;

    public function getDescription(): string;
}
