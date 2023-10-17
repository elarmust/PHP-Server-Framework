<?php

/**
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Module;

use Framework\Framework;

abstract class AbstractModule implements ModuleInterface {
    private Framework $framework;
    private string $name;
    private string $path;
    protected array $loadBefore = [];
    protected array $loadAfter = [];

    final public function init(
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
    abstract public function load();

    /*
     * This method will be called when module is unloaded.
     * This should contain code needed to disable module functionalities.
     */
    abstract public function unload();

    final public function getFramework(): Framework {
        return $this->framework;
    }

    final public function getName(): string {
        return $this->name;
    }

    final public function getPath(): string {
        return $this->path;
    }

    public function getVersion(): string {
        return '1.0.0';
    }

    final public function loadAfter(): array {
        return $this->loadAfter;
    }

    final public function loadBefore(): array {
        return $this->loadBefore;
    }

    public function getDescription(): string {
        return '';
    }
}
