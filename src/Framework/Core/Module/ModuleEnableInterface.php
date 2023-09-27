<?php

/**
 * This class will be called when module is enabled
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Core\Module;

interface ModuleEnableInterface {
    /*
     * This method will be called when module is enabled.
     * This should contain code needed to set up module functionalities.
     */
    public function onEnable();

    /*
     * This method will be called when module is disabled.
     * This should contain code needed to disable module functionalities.
     */
    public function onDisable();
}
