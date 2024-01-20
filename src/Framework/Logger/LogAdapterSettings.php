<?php

/**
 * Trait for managing log adapter settings.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Logger;

trait LogAdapterSettings {
    protected $options = [];

    public function set(array $value): void {
        $this->options = $value;
    }

    public function setValue($key, $value): void {
        $this->options[$key] = $value;
    }

    public function getValue($key, $default = null): mixed {
        return $this->options[$key] ?? $default;
    }
}
