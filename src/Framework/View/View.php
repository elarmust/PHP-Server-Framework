<?php

/**
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\View;

use Framework\View\ViewInterface;

class View implements ViewInterface {
    protected string $name;
    protected mixed $view = '';

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
     * @param array $variables = []
     * @return string
     */
    public function render(array $variables = []): string {
        if (!$this->view) {
            return '';
        }

        ob_start();
        extract($variables);
        eval('?>' . $this->view . '<?php');
        return ob_get_clean();
    }
}
