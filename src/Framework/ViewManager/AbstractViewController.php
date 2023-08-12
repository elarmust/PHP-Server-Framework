<?php

/**
 * Basic view controller.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\ViewManager;

abstract class AbstractViewController {
    /**
     * Run view controller.
     * This will be called before view file is loaded.
     * 
     * @return void
     */
    public function run(): void {
    }

    /**
     * Load view file, parse the contents and return the output.
     * 
     * @param string $fileName
     * @return string
     */
    public final function evalView(View $view): string {
        if (!$view->getViewBase()) {
            return '';
        }

        ob_start();
        eval('?>' . $view->getViewBase() . '<?php');
        return ob_get_clean();
    }
}