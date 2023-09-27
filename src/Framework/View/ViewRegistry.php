<?php

/**
 * The ViewRegistry class provides a central repository for managing reusable views.
 * 
 * Copyright © WereWolf Labs OÜ.
 */

namespace Framework\View;

use InvalidArgumentException;

class ViewRegistry {
    private array $views = [];

    /**
     * Register a new view
     * 
     * @param ViewInterface $view
     * @return void
     */
    public function registerView(string $name, ViewInterface $view): void {
        $this->views[$name] = $view;
    }

    /**
     * Unregister a view
     *
     * @param string $viewName
     * @return void
     */
    public function unregisterView(string $viewName): void {
        unset($this->views[$viewName]);
    }

    /**
     * List existing views.
     *
     * @return array
     */
    public function listViews(): array {
        return array_keys($this->views);
    }

    /**
     * Get base view by name
     * 
     * @param string $viewName
     * @throws InvalidArgumentException
     * @return ViewInterface Cloned view
     */
    public function getView(string $viewName): ViewInterface {
        if (!isset($this->views[$viewName])) {
            throw new InvalidArgumentException('View \'' . $viewName . '\' does not exist!');
        }

        return clone $this->views[$viewName];
    }
}
