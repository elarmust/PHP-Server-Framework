<?php

/**
 * Json Views.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\View;

use Framework\View\ViewInterface;

class JsonView implements ViewInterface {
    protected string $name;
    protected array $view = [];

    /**
     * @param string $viewName
     */
    public function __construct(string $viewName) {
        $this->name = $viewName;
        return $this;
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
     * Set view contents.
     * 
     * @param mixed $view
     * @return void
     */
    public function setView(mixed $view): void {
        $this->view = $view;
    }

    /**
     * Returns view string.
     * 
     * @return mixed
     */
    public function getView(): mixed {
        return $this->view;
    }

    /**
     * Render the view.
     * 
     * @return string
     */
    public function render(): string {
        return json_encode($this->view);
    }
}
