<?php

/**
 * Represents a basic view for rendering static and dynamic content.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\View;

use Framework\View\ViewInterface;
use Throwable;

class View implements ViewInterface {
    protected string $viewFile = '';

    public function __construct(private ViewRegistry $viewRegistry) {
    }

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
     * Import and render a view.
     *
     * @param string $viewName View name.
     * @param array $variables = [] Variables to pass to the view
     *
     * @return string rendered view.
     */
    public function import(string $viewName, array $variables = []): string {
        try {
            return $this->viewRegistry->getView($viewName)->getView($variables);
        } catch (Throwable $e) {
            return '';
        }
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
        try {
            include $this->viewFile;
        } catch (Throwable $e) {
            throw $e;
        } finally {
            $output = ob_get_clean();
        }

        return $output;
    }
}
