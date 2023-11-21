<?php

namespace Kinintel\ValueObjects\Util\Analysis\TextAnalysis;

class TextChunk {
    public function __construct(
        private string $text,
        private int $pointer,
        private int $length
    ) {
    }

    public function getText(): string {
        return $this->text;
    }

    public function getPointer(): int {
        return $this->pointer;
    }

    public function getLength(): int {
        return $this->length;
    }


}