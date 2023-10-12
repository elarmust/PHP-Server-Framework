<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Module;

use Framework\Framework;

abstract class AbstractModule implements ModuleInterface {
    private Framework $framework;
    private string $name;
    private string $path;
    protected array $loadBefore = [];
    protected array $loadAfter = [];

    public final function init(
        Framework $framework,
        string $name,
        string $path
    ) {
        $this->framework = $framework;
        $this->name = $name;
        $this->path = $path;
    }

    /*
     * This method will be called when module is loaded.
     * This should contain code needed to set up module functionalities.
     */
    abstract public function Load();

    /*
     * This method will be called when module is unloaded.
     * This should contain code needed to disable module functionalities.
     */
    abstract public function Unload();

    public final function getFramework(): Framework {
        return $this->framework;
    }

    public final function getName(): string {
        return $this->name;
    }

    public final function getPath(): string {
        return $this->path;
    }

    public function getVersion(): string {
        return '1.0.0';
    }

    public final function loadAfter(): array {
        return $this->loadAfter;
    }

    public final function loadBefore(): array {
        return $this->loadBefore;
    }

    public function getDescription(): string {
        return '';
    }
}
