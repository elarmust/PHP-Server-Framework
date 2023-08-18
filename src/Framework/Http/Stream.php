<?php

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface {
    private $stream;

    public function __construct($stream) {
        $this->stream = $stream;
    }

    public function __toString(): string {
        return stream_get_contents($this->stream);
    }

    public function close(): void {
        fclose($this->stream);
    }

    public function detach() {
        $this->stream = null;
    }

    public function getSize(): null|int {
        return fstat($this->stream)['size'];
    }

    public function tell(): int {
        return ftell($this->stream);
    }

    public function eof(): bool {
        return feof($this->stream);
    }

    public function isSeekable(): bool {
        return $this->getMetadata('seekable');
    }

    public function seek($offset, $whence = SEEK_SET): void {
        fseek($this->stream, $offset, $whence);
    }

    public function rewind(): void {
        rewind($this->stream);
    }

    public function isWritable(): bool {
        $mode = $this->getMetadata('mode');
        return strpos($mode, 'w') !== false || strpos($mode, 'a') !== false || strpos($mode, 'x') !== false || strpos($mode, 'c') !== false;
    }

    public function write($string): int {
        return fwrite($this->stream, $string);
    }

    public function isReadable(): bool {
        $mode = $this->getMetadata('mode');
        return strpos($mode, 'r') !== false;
    }

    public function read($length): string {
        return fread($this->stream, $length);
    }

    public function getContents(): string {
        return stream_get_contents($this->stream);
    }

    public function getMetadata($key = null) {
        $meta = stream_get_meta_data($this->stream);
        if ($key === null) {
            return $meta;
        }
        return $meta[$key] ?? null;
    }
}
