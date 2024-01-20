<?php

/**
 * Represents a basic view for displaying JSON encoded data.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\View;

use Framework\View\ViewInterface;

class JsonView implements ViewInterface {
    protected array $view = [];

    /**
     * Set view contents.
     *
     * @param array $view
     * @return ViewInterface
     */
    public function setView(array $viewArray): ViewInterface {
        $this->view = $viewArray;
        return $this;
    }

    /**
     * Return json encoded view.
     *
     * @return string
     */
    public function getView(): string {
        return json_encode($this->view);
    }
}
