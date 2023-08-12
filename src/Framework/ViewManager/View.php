<?php

/**
 * Object for view data
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\ViewManager;

class View {
    private string $name;
    private ?string $viewController;
    private string $viewBase = '';
    private string $view = '';

    /**
     * @param string $viewName
     * @param null|string $viewSourceFile
     * @param null|string $viewController
     */
    public function __construct(string $viewName, string $viewBase = '', ?string $viewController = null) {
        $this->name = $viewName;
        $this->viewController = $viewController;
        $this->viewBase = $viewBase;
    }

    /**
     * Get view controller.
     * 
     * @return string
     */
    public function getController(): string {
        return $this->viewController;
    }

    /**
     * Set view base.
     * 
     * @return string
     */
    public function setViewBase(string $viewString): void {
        $this->viewBase = $viewString;
    }

    /**
     * Get view base.
     * 
     * @return string
     */
    public function getViewBase(): string {
        return $this->viewBase;
    }

    /**
     * Get view name.
     * 
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Set view contents
     * 
     * @param string $view
     * @return void
     */
    public function setView(string $view): void {
        $this->view = $view;
    }

    /**
     * Returns view string.
     * 
     * @return string
     */
    public function getView(): string {
        return $this->view;
    }
}
