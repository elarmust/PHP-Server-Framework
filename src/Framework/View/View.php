<?php

/**
 * Represents a basic view for rendering static and dynamic content.
 *
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\View;

use Framework\View\ViewInterface;

class View implements ViewInterface {
    protected string $viewFile = '';

    /**
     * Set view contents.
     *
     * @param string $viewFile
     * @return ViewInterface
     */
    public function setView(string $viewFile): ViewInterface {
        $this->viewFile = $viewFile;
        return $this;
    }

    /**
     * Return a rendered view string.
     *
     * @param array $variables = []
     * @return string
     */
    public function getView(array $variables = []): string {
        if (!$this->viewFile) {
            return '';
        }

        ob_start();
        extract($variables);
        include $this->viewFile;
        return ob_get_clean();
    }
}
