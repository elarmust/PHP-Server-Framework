<?php

/**
 * Html Views.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\View;

use Framework\View\View;
use Framework\View\HtmlEditor;

class HtmlView extends View {
    use HtmlEditor;

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
        if (!$this->view) {
            return '';
        }

        ob_start();
        eval('?>' . $this->view . '<?php');
        return ob_get_clean();
    }
}
