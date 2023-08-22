<?php

/**
 * HTML view manager.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\View;

use Psr\Log\LogLevel;
use Framework\Logger\Logger;
use InvalidArgumentException;
use Framework\View\View;
use Framework\Core\ClassContainer;

class ViewRegistry {
    private Logger $logger;
    private array $views = [];

    /**
     * @param ClassContainer $classContainer
     * @param Logger $logger
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Register a new view
     * 
     * @param View $view
     * @return void
     */
    public function registerView(View $view): void {
        $this->views[$view->getName()] = $view;
    }

    /**
     * Unregister a view
     *
     * @param string $viewName
     * @return void
     */
    public function unregisterView(string $viewName): void {
        if (!isset($this->views[$viewName])) {
            $this->logger->log(LogLevel::NOTICE, 'Unregistering nonexistent view: \'' . $viewName . '\'', identifier: 'framework');
            return;
        }

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
     * @return View
     */
    public function getView(string $viewName): View {
        if (!isset($this->views[$viewName])) {
            throw new InvalidArgumentException('View \'' . $viewName . '\' does not exist!');
        }

        return $this->views[$viewName];
    }
}
