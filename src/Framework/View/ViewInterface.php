<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\View;

interface ViewInterface {
    /**
     * Returns a view string.
     * 
     * @return string
     */
    public function getView(): string;
}
