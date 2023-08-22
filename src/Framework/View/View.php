<?php

/**
 * Object for view data
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\View;

class View {
    private string $name;
    private string $viewBase;
    private string $view;

    /**
     * @param string $viewName
     * @param string $viewBase
     */
    public function __construct(string $viewName, string $viewBase = '') {
        $this->name = $viewName;
        $this->viewBase = $viewBase;
        $this->view = $this->viewBase;
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
