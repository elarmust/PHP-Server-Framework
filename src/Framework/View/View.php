<?php

/**
 * Represents a basic view for rendering static and dynamic content.
 *
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\View;

use Framework\View\ViewInterface;

class View implements ViewInterface {
    protected mixed $content = '';

    /**
     * Set view contents.
     *
     * @param string $view
     * @return ViewInterface
     */
    public function setView(string $view): ViewInterface {
        $this->content = $view;
        return $this;
    }

    /**
     * Return a rendered view string.
     *
     * @param array $variables = []
     * @return string
     */
    public function getView(array $variables = []): string {
        if (!$this->content) {
            return '';
        }

        ob_start();
        extract($variables);
        eval('?>' . $this->content . '<?php');
        return ob_get_clean();
    }
}
