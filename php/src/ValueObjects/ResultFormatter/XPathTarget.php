<?php

namespace Kinintel\ValueObjects\ResultFormatter;

class XPathTarget {
    public function __construct(
        public string $name,
        public string $xpath,
        public ?string $attribute = null,
    ) {
    }
}