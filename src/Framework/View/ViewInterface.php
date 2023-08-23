<?php

/**
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\View;

interface ViewInterface {

    /**
     * Get view name.
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Set the unrendered view contents.
     * 
     * @param mixed $view
     * @return void
     */
    public function setView(mixed $view): void;

    /**
     * Returns an unrendered view string\array.
     * 
     * @return mixed
     */
    public function getView(): mixed;

    /**
     * Returns a rendered view string.
     * 
     * @return string
     */
    public function render(): string;
}
