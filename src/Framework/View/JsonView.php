<?php

/**
 * Json Views.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\View;

use Framework\View\View;

class JsonView extends View {
    /**
     * @param string $viewName
     */
    public function __construct(string $viewName) {
        return parent::__construct($viewName);
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
