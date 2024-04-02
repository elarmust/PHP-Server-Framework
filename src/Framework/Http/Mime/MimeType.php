<?php

namespace Framework\Http\Mime;

class MimeType {
    public function __construct(public string $mimeType, public array $extensions) {
    }
}
