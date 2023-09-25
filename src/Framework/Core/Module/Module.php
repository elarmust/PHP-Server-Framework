<?php

/**
 * Class representing module data
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Core\Module;

class Module {
    private ?string $name = null;
    private ?string $vendor = null;
    private ?string $version = null;
    private ?string $path = null;
    private ?string $classPath = null;
    private array $dependencies = [];

    public function __construct(string $name, string $vendor, string $version, string $path, string $classPath, array $dependencies) {
        $this->name = $name;
        $this->vendor = $vendor;
        $this->version = $version;
        $this->path = $path;
        $this->classPath = $classPath;
        $this->dependencies = $dependencies;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getVendor(): string {
        return $this->vendor;
    }

    public function getVersion(): string {
        return $this->version;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getClassPath(): string {
        return $this->classPath;
    }

    public function getDependencies(): array {
        return $this->dependencies;
    }
}