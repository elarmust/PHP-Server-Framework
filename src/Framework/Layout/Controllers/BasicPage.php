<?php

namespace Framework\Layout\Controllers;

use Framework\ViewManager\AbstractViewController;
use Framework\ViewManager\ViewManager;

class BasicPage extends AbstractViewController {
    private ViewManager $viewManager;

    public function __construct(ViewManager $viewManager) {
        $this->viewManager = $viewManager;
    }
}